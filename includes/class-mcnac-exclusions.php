<?php
/**
 * Exclusions class for MCNAC N8N Chat Advanced.
 *
 * Handles chat exclusion settings by pages, posts, post types, and URL patterns.
 *
 * @package MCNAC_N8N_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MCNAC_Exclusions
 * Handles which pages/posts/URLs should exclude the chat widget.
 */
class MCNAC_Exclusions {

	/**
	 * The option name for exclusions.
	 */
	const OPTION_NAME = 'mcnac_exclusions';

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_exclusions_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_exclusion_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets for the exclusions page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_mcnac-exclusions' !== $hook ) {
			return;
		}

		// Enqueue base admin styles (same as main settings page).
		wp_enqueue_style(
			'mcnac-admin-style',
			MCNAC_URL . 'assets/css/mcnac-admin.css',
			array(),
			MCNAC_VERSION
		);

		// Enqueue exclusions-specific styles.
		wp_enqueue_style(
			'mcnac-exclusions-style',
			MCNAC_URL . 'assets/css/mcnac-exclusions.css',
			array( 'mcnac-admin-style' ),
			MCNAC_VERSION
		);

		// Enqueue exclusions script.
		wp_enqueue_script(
			'mcnac-exclusions-script',
			MCNAC_URL . 'assets/js/mcnac-exclusions.js',
			array( 'jquery' ),
			MCNAC_VERSION,
			true
		);

		// Localize script with translatable strings.
		wp_localize_script(
			'mcnac-exclusions-script',
			'mcnacExclusionsL10n',
			array(
				'multiselectHelp' => __( 'Hold Ctrl (Cmd on Mac) + click to select or deselect multiple items.', 'mcnac-n8n-chat-advanced' ),
			)
		);
	}

	/**
	 * Add submenu page under Settings.
	 */
	public function add_exclusions_submenu() {
		add_submenu_page(
			'options-general.php',
			__( 'MCNAC Chat Exclusions', 'mcnac-n8n-chat-advanced' ),
			__( 'MCNAC Exclusions', 'mcnac-n8n-chat-advanced' ),
			'manage_options',
			'mcnac-exclusions',
			array( $this, 'render_exclusions_page' )
		);
	}

	/**
	 * Register exclusion settings.
	 */
	public function register_exclusion_settings() {
		register_setting(
			'mcnac_exclusions_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_exclusions' ),
			)
		);
	}

	/**
	 * Sanitize exclusions input.
	 *
	 * @param array $input The raw input.
	 * @return array The sanitized input.
	 */
	public function sanitize_exclusions( $input ) {
		$sanitized = array();

		// Excluded Pages (array of IDs).
		if ( isset( $input['excluded_pages'] ) && is_array( $input['excluded_pages'] ) ) {
			$sanitized['excluded_pages'] = array_map( 'absint', $input['excluded_pages'] );
		} else {
			$sanitized['excluded_pages'] = array();
		}

		// Excluded Posts (array of IDs).
		if ( isset( $input['excluded_posts'] ) && is_array( $input['excluded_posts'] ) ) {
			$sanitized['excluded_posts'] = array_map( 'absint', $input['excluded_posts'] );
		} else {
			$sanitized['excluded_posts'] = array();
		}

		// Excluded Post Types (array of slugs).
		if ( isset( $input['excluded_post_types'] ) && is_array( $input['excluded_post_types'] ) ) {
			$sanitized['excluded_post_types'] = array_map( 'sanitize_key', $input['excluded_post_types'] );
		} else {
			$sanitized['excluded_post_types'] = array();
		}

		// Excluded URL patterns (textarea, one per line).
		if ( isset( $input['excluded_url_patterns'] ) ) {
			$patterns = sanitize_textarea_field( $input['excluded_url_patterns'] );
			$sanitized['excluded_url_patterns'] = $patterns;
		} else {
			$sanitized['excluded_url_patterns'] = '';
		}

		// Global exclusion mode: 'exclude' or 'include_only'.
		if ( isset( $input['exclusion_mode'] ) ) {
			$sanitized['exclusion_mode'] = in_array( $input['exclusion_mode'], array( 'exclude', 'include_only' ), true )
				? sanitize_key( $input['exclusion_mode'] )
				: 'exclude';
		} else {
			$sanitized['exclusion_mode'] = 'exclude';
		}

		return $sanitized;
	}

	/**
	 * Check if chat should be hidden on current page.
	 *
	 * @return bool True if chat should be hidden.
	 */
	public static function should_hide_chat() {
		$options = get_option( self::OPTION_NAME, array() );

		// Default values.
		$excluded_pages      = isset( $options['excluded_pages'] ) ? $options['excluded_pages'] : array();
		$excluded_posts      = isset( $options['excluded_posts'] ) ? $options['excluded_posts'] : array();
		$excluded_post_types = isset( $options['excluded_post_types'] ) ? $options['excluded_post_types'] : array();
		$url_patterns        = isset( $options['excluded_url_patterns'] ) ? $options['excluded_url_patterns'] : '';
		$mode                = isset( $options['exclusion_mode'] ) ? $options['exclusion_mode'] : 'exclude';

		// Get current URL.
		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Check URL patterns.
		$url_matched = false;
		if ( ! empty( $url_patterns ) ) {
			$patterns = array_filter( array_map( 'trim', explode( "\n", $url_patterns ) ) );
			foreach ( $patterns as $pattern ) {
				if ( ! empty( $pattern ) && false !== strpos( $current_url, $pattern ) ) {
					$url_matched = true;
					break;
				}
			}
		}

		// Check current page/post.
		$post_matched      = false;
		$post_type_matched = false;

		if ( is_singular() ) {
			$current_id        = get_queried_object_id();
			$current_post_type = get_post_type();

			// Check if current page is excluded.
			if ( is_page() && in_array( $current_id, $excluded_pages, true ) ) {
				$post_matched = true;
			}

			// Check if current post is excluded.
			if ( is_single() && in_array( $current_id, $excluded_posts, true ) ) {
				$post_matched = true;
			}

			// Check if current post type is excluded.
			if ( in_array( $current_post_type, $excluded_post_types, true ) ) {
				$post_type_matched = true;
			}
		}

		// Determine if any exclusion matches.
		$is_excluded = $url_matched || $post_matched || $post_type_matched;

		// Apply mode logic.
		if ( 'include_only' === $mode ) {
			// In "include_only" mode, hide if NOT matched.
			return ! $is_excluded;
		}

		// Default "exclude" mode: hide if matched.
		return $is_excluded;
	}

	/**
	 * Get all public pages for multiselect.
	 *
	 * @return array Array of pages with ID => title.
	 */
	private function get_pages_list() {
		$pages = get_pages( array( 'post_status' => 'publish' ) );
		$list  = array();
		foreach ( $pages as $page ) {
			$list[ $page->ID ] = $page->post_title;
		}
		return $list;
	}

	/**
	 * Get all public posts for multiselect.
	 *
	 * @return array Array of posts with ID => title.
	 */
	private function get_posts_list() {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$list  = array();
		foreach ( $posts as $post ) {
			$list[ $post->ID ] = $post->post_title;
		}
		return $list;
	}

	/**
	 * Get all public post types.
	 *
	 * @return array Array of post types.
	 */
	private function get_post_types_list() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		$list = array();
		foreach ( $post_types as $post_type ) {
			// Exclude attachments.
			if ( 'attachment' === $post_type->name ) {
				continue;
			}
			$list[ $post_type->name ] = $post_type->label;
		}
		return $list;
	}

	/**
	 * Render the exclusions settings page.
	 */
	public function render_exclusions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( self::OPTION_NAME, array() );

		// Current values.
		$excluded_pages      = isset( $options['excluded_pages'] ) ? $options['excluded_pages'] : array();
		$excluded_posts      = isset( $options['excluded_posts'] ) ? $options['excluded_posts'] : array();
		$excluded_post_types = isset( $options['excluded_post_types'] ) ? $options['excluded_post_types'] : array();
		$url_patterns        = isset( $options['excluded_url_patterns'] ) ? $options['excluded_url_patterns'] : '';
		$mode                = isset( $options['exclusion_mode'] ) ? $options['exclusion_mode'] : 'exclude';

		$pages_list      = $this->get_pages_list();
		$posts_list      = $this->get_posts_list();
		$post_types_list = $this->get_post_types_list();
		?>
		<div class="mcnac-wrap mcnac-exclusions-wrap">
			<h1><?php esc_html_e( 'MCNAC Chat Exclusions', 'mcnac-n8n-chat-advanced' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Configure where the chat widget should be hidden or shown.', 'mcnac-n8n-chat-advanced' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( 'mcnac_exclusions_group' ); ?>

				<table class="form-table" role="presentation">
					<!-- Exclusion Mode -->
					<tr>
						<th scope="row">
							<label for="exclusion_mode"><?php esc_html_e( 'Mode', 'mcnac-n8n-chat-advanced' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[exclusion_mode]" id="exclusion_mode">
								<option value="exclude" <?php selected( $mode, 'exclude' ); ?>><?php esc_html_e( 'Exclude (hide chat on selected)', 'mcnac-n8n-chat-advanced' ); ?></option>
								<option value="include_only" <?php selected( $mode, 'include_only' ); ?>><?php esc_html_e( 'Include Only (show chat ONLY on selected)', 'mcnac-n8n-chat-advanced' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose whether to hide the chat on selected items (Exclude) or show ONLY on selected items (Include Only).', 'mcnac-n8n-chat-advanced' ); ?></p>
						</td>
					</tr>

					<!-- Excluded Pages -->
					<tr>
						<th scope="row">
							<label for="excluded_pages"><?php esc_html_e( 'Pages', 'mcnac-n8n-chat-advanced' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_pages][]" id="excluded_pages" class="mcnac-select2" multiple="multiple" style="width: 100%;">
								<?php foreach ( $pages_list as $id => $title ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php echo in_array( $id, $excluded_pages, true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select pages.', 'mcnac-n8n-chat-advanced' ); ?></p>
						</td>
					</tr>

					<!-- Excluded Posts -->
					<tr>
						<th scope="row">
							<label for="excluded_posts"><?php esc_html_e( 'Posts', 'mcnac-n8n-chat-advanced' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_posts][]" id="excluded_posts" class="mcnac-select2" multiple="multiple" style="width: 100%;">
								<?php foreach ( $posts_list as $id => $title ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php echo in_array( $id, $excluded_posts, true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select posts.', 'mcnac-n8n-chat-advanced' ); ?></p>
						</td>
					</tr>

					<!-- Excluded Post Types -->
					<tr>
						<th scope="row">
							<label for="excluded_post_types"><?php esc_html_e( 'Post Types', 'mcnac-n8n-chat-advanced' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_post_types][]" id="excluded_post_types" class="mcnac-select2" multiple="multiple" style="width: 100%;">
								<?php foreach ( $post_types_list as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php echo in_array( $slug, $excluded_post_types, true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select post types. Chat will be hidden on all content of these types.', 'mcnac-n8n-chat-advanced' ); ?></p>
						</td>
					</tr>

					<!-- URL Patterns -->
					<tr>
						<th scope="row">
							<label for="excluded_url_patterns"><?php esc_html_e( 'URL Contains', 'mcnac-n8n-chat-advanced' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_url_patterns]" id="excluded_url_patterns" rows="5" class="large-text"><?php echo esc_textarea( $url_patterns ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter URL patterns, one per line. Chat will be hidden on any URL containing these strings. Example: /checkout, /cart, ?add-to-cart', 'mcnac-n8n-chat-advanced' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<div class="mcnac-contribution-message">
				<p>
					<?php
					printf(
						/* translators: %s: settings page link */
						esc_html__( 'Configure the main chat settings on the %s.', 'mcnac-n8n-chat-advanced' ),
						'<a href="' . esc_url( admin_url( 'options-general.php?page=mcnac-n8n-chat' ) ) . '">' . esc_html__( 'MCNAC N8N Chat settings page', 'mcnac-n8n-chat-advanced' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
