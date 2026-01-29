<?php
/**
 * Settings class for MCNAC N8N Chat Advanced.
 *
 * @package MCNAC_N8N_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MCNAC_Settings
 * Handles the plugin settings page and options.
 */
class MCNAC_Settings {

	/**
	 * The option group name.
	 */
	const OPTION_GROUP = 'mcnac_settings_group';

	/**
	 * The option name.
	 */
	const OPTION_NAME = 'mcnac_settings';

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page
		if ( 'toplevel_page_mcnac-n8n-chat' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style(
			'mcnac-admin-style',
			MCNAC_URL . 'assets/css/mcnac-admin.css',
			array(),
			MCNAC_VERSION
		);
		wp_enqueue_script(
			'mcnac-admin-script',
			MCNAC_URL . 'assets/js/mcnac-admin.js',
			array( 'jquery' ),
			MCNAC_VERSION,
			true
		);
	}

	/**
	 * Add the settings page to the admin menu.
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'MCOD N8N Chat', 'mcnac-n8n-chat-advanced' ),
			__( 'MCOD Chat', 'mcnac-n8n-chat-advanced' ),
			'manage_options',
			'mcnac-n8n-chat',
			array( $this, 'render_settings_page' ),
			'dashicons-format-chat',
			25
		);

		// Add submenu page for General Settings (restores the main menu item as a submenu)
		add_submenu_page(
			'mcnac-n8n-chat',
			__( 'General Settings', 'mcnac-n8n-chat-advanced' ),
			__( 'General Settings', 'mcnac-n8n-chat-advanced' ),
			'manage_options',
			'mcnac-n8n-chat',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'mcnac_general_section',
			'', // Title removed to avoid duplication with page title
			null,
			'mcnac-n8n-chat'
		);

		add_settings_field(
			'webhook_url',
			__( 'Webhook URL', 'mcnac-n8n-chat-advanced' ),
			array( $this, 'render_text_field' ),
			'mcnac-n8n-chat',
			'mcnac_general_section',
			array(
				'id'          => 'webhook_url',
				'description' => __( 'The n8n webhook URL for the chat.', 'mcnac-n8n-chat-advanced' ),
			)
		);

		// show_powered_by field removed.

		add_settings_section(
			'mcnac_appearance_section',
			__( 'Appearance & Text', 'mcnac-n8n-chat-advanced' ),
			null,
			'mcnac-n8n-chat'
		);

		add_settings_field(
			'chat_logo',
			__( 'Chat Logo', 'mcnac-n8n-chat-advanced' ),
			array( $this, 'render_image_field' ),
			'mcnac-n8n-chat',
			'mcnac_appearance_section',
			array(
				'id'          => 'chat_logo',
				'description' => __( 'Upload an image to display in the header (overrides default icon). Recommended: 40x40px.', 'mcnac-n8n-chat-advanced' ),
			)
		);

		$appearance_fields = array(
			'chat_title'      => array(
				'label'   => __( 'Chat Title', 'mcnac-n8n-chat-advanced' ),
				'default' => 'Hi there! 👋',
			),
			'chat_subtitle'   => array(
				'label'   => __( 'Chat Subtitle', 'mcnac-n8n-chat-advanced' ),
				'default' => 'Start a conversation',
			),
			'initial_message' => array(
				'label'   => __( 'Initial Message', 'mcnac-n8n-chat-advanced' ),
				'default' => 'Hi! How can I help you today?',
			),
			'btn_start_text'  => array(
				'label'   => __( 'Start Button Text', 'mcnac-n8n-chat-advanced' ),
				'default' => 'Start Conversation',
			),
			'placeholder'     => array(
				'label'   => __( 'Message Placeholder', 'mcnac-n8n-chat-advanced' ),
				'default' => 'Type your message...',
			),
			'primary_color'   => array(
				'label'   => __( 'Primary Color', 'mcnac-n8n-chat-advanced' ),
				'default' => '#5ea17b',
				'type'    => 'color',
			),
			'header_bg_color'   => array(
				'label'   => __( 'Header Background', 'mcnac-n8n-chat-advanced' ),
				'default' => '#5ea17b',
				'type'    => 'color',
			),
			'header_text_color' => array(
				'label'   => __( 'Header Text Color', 'mcnac-n8n-chat-advanced' ),
				'default' => '#ffffff',
				'type'    => 'color',
			),
			'user_msg_bg_color' => array(
				'label'   => __( 'User Message Background', 'mcnac-n8n-chat-advanced' ),
				'default' => '#5ea17b',
				'type'    => 'color',
			),
			'user_msg_text_color' => array(
				'label'   => __( 'User Message Text', 'mcnac-n8n-chat-advanced' ),
				'default' => '#ffffff',
				'type'    => 'color',
			),
		);

		foreach ( $appearance_fields as $id => $field ) {
			add_settings_field(
				$id,
				$field['label'],
				array( $this, 'render_text_field' ),
				'mcnac-n8n-chat',
				'mcnac_appearance_section',
				array(
					'id'      => $id,
					'default' => $field['default'],
					'type'    => isset( $field['type'] ) ? $field['type'] : 'text',
				)
			);
		}
	}

	/**
	 * Sanitize the settings inputs.
	 *
	 * @param array $input The raw input.
	 * @return array The sanitized input.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['webhook_url'] ) ) {
			$sanitized['webhook_url'] = esc_url_raw( $input['webhook_url'] );
		}

		if ( isset( $input['chat_logo'] ) ) {
			$sanitized['chat_logo'] = esc_url_raw( $input['chat_logo'] );
		}

		// show_powered_by sanitization removed.

		$text_fields = array( 'chat_title', 'chat_subtitle', 'initial_message', 'btn_start_text', 'placeholder' );
		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		$color_fields = array( 'primary_color', 'header_bg_color', 'header_text_color', 'user_msg_bg_color', 'user_msg_text_color' );
		foreach ( $color_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
			}
		}

		return $sanitized;
	}

	/**
	 * Render a text or color field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( $args ) {
		$options = get_option( self::OPTION_NAME );
		$id      = $args['id'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$type    = isset( $args['type'] ) ? $args['type'] : 'text';
		$value   = isset( $options[ $id ] ) ? $options[ $id ] : $default;

		printf(
			'<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $id ),
			esc_attr( $value )
		);
		
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render an image upload field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_image_field( $args ) {
		$options = get_option( self::OPTION_NAME );
		$id      = $args['id'];
		$value   = isset( $options[ $id ] ) ? $options[ $id ] : '';

		echo '<div class="mcnac-image-upload">';
		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
			esc_attr( $id ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $value )
		);
		echo '<br><br>';
		echo '<input type="button" id="mcnac_upload_logo_btn" class="button" value="' . esc_attr__( 'Choose Image', 'mcnac-n8n-chat-advanced' ) . '" /> ';
		echo '<input type="button" id="mcnac_remove_logo_btn" class="button" value="' . esc_attr__( 'Remove', 'mcnac-n8n-chat-advanced' ) . '" />';
		echo '<br><br>';
		
		$style = empty( $value ) ? 'display:none;' : '';
		echo '<img id="mcnac_logo_preview" src="' . esc_url( $value ) . '" style="max-width: 100px; max-height: 100px; ' . esc_attr( $style ) . '" />';
		
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( $args ) {
		// Method kept for potential future use or external compatibility.
		// Implementation removed as no current fields use it.
	}

	/**
	 * Render the settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="mcnac-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'mcnac-n8n-chat' );
				submit_button();
				?>
			</form>

			<div class="mcnac-contribution-message">
				<p>
					<?php
					printf(
						/* translators: %s: email address */
						esc_html__( 'This plugin is in constant evolution. If you have ideas or suggestions, do not hesitate to contact me at %s. Your collaboration is highly valued!', 'mcnac-n8n-chat-advanced' ),
						'<a href="mailto:hola@devcristian.com">hola@devcristian.com</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
