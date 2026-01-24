<?php
/**
 * Frontend class for MCNAC N8N Chat Advanced.
 *
 * @package MCNAC_N8N_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MCNAC_Frontend
 * Handles frontend rendering and assets.
 */
class MCNAC_Frontend {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_chat_widget' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_assets() {
		// Enqueue CSS
		wp_enqueue_style(
			'mcnac-chat-style',
			MCNAC_URL . 'assets/css/mcnac-chat.css',
			array(),
			MCNAC_VERSION
		);

		// Dynamic CSS for colors
		$options = get_option( 'mcnac_settings' );
		
		// Defaults
		$primary     = isset( $options['primary_color'] ) ? $options['primary_color'] : '#5ea17b';
		$header_bg   = isset( $options['header_bg_color'] ) ? $options['header_bg_color'] : $primary;
		$header_text = isset( $options['header_text_color'] ) ? $options['header_text_color'] : '#ffffff';
		$user_bg     = isset( $options['user_msg_bg_color'] ) ? $options['user_msg_bg_color'] : $primary;
		$user_text   = isset( $options['user_msg_text_color'] ) ? $options['user_msg_text_color'] : '#ffffff';

		$custom_css = "
			:root {
				--mcnac-primary: {$primary};
				--mcnac-header-bg: {$header_bg};
				--mcnac-header-text: {$header_text};
				--mcnac-user-bg: {$user_bg};
				--mcnac-user-text: {$user_text};
			}
		";
		wp_add_inline_style( 'mcnac-chat-style', $custom_css );

		// Enqueue JS
		wp_enqueue_script(
			'mcnac-chat-script',
			MCNAC_URL . 'assets/js/mcnac-chat.js',
			array( 'jquery' ),
			MCNAC_VERSION,
			true
		);

		// Localize script with settings
		wp_localize_script(
			'mcnac-chat-script',
			'mcnacSettings',
			array(
				'webhookUrl'     => isset( $options['webhook_url'] ) ? $options['webhook_url'] : '',
				'initialMessage' => isset( $options['initial_message'] ) ? $options['initial_message'] : 'Hi! How can I help you?',
				'title'          => isset( $options['chat_title'] ) ? $options['chat_title'] : 'Chat',
				'subtitle'       => isset( $options['chat_subtitle'] ) ? $options['chat_subtitle'] : 'Support',
				'placeholder'    => isset( $options['placeholder'] ) ? $options['placeholder'] : 'Type a message...',
				'startText'      => isset( $options['btn_start_text'] ) ? $options['btn_start_text'] : 'Start Chat',
				'logo'           => isset( $options['chat_logo'] ) ? $options['chat_logo'] : '',
				'defaultLogo'    => MCNAC_URL . 'assets/images/mcnac-user-icon.jpg',
				'showPoweredBy'  => isset( $options['show_powered_by'] ) && $options['show_powered_by'] == 1,
			)
		);
	}

	/**
	 * Render the chat widget HTML in the footer.
	 */
	public function render_chat_widget() {
		$options = get_option( 'mcnac_settings' );
		// Only render if webhook URL is set
		if ( empty( $options['webhook_url'] ) ) {
			return;
		}
		?>
		<div id="mcnac-chat-widget" class="mcnac-chat-widget">
			<!-- Toggle Button -->
			<button id="mcnac-chat-toggle" class="mcnac-chat-toggle" aria-label="<?php esc_attr_e( 'Open Chat', 'mcnac-n8n-chat-advanced' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 4C16.42 4 20 7.58 20 12C20 16.42 16.42 20 12 20C7.58 20 4 16.42 4 12C4 7.58 7.58 4 12 4Z" fill="white" fill-opacity="0.2"/>
					<path d="M12 6C8.68629 6 6 8.68629 6 12C6 13.9056 7.00516 15.6033 8.54477 16.6353L8.09312 18.4419C8.01755 18.7442 8.29177 18.9959 8.58284 18.897L10.515 18.2529C10.9902 18.3512 11.4862 18.4 12 18.4C15.3137 18.4 18 15.7137 18 12C18 8.68629 15.3137 6 12 6Z" fill="currentColor"/>
				</svg>
			</button>

			<!-- Chat Window -->
			<div id="mcnac-chat-window" class="mcnac-chat-window">
				<div class="mcnac-chat-header">
					<div class="mcnac-header-info">
						<div class="mcnac-header-logo-container">
							<!-- JS will inject img or svg here -->
						</div>
						<div class="mcnac-header-text-container">
							<p class="mcnac-title"></p>
							<p class="mcnac-subtitle"></p>
						</div>
					</div>
					<button id="mcnac-chat-close" class="mcnac-chat-close" aria-label="<?php esc_attr_e( 'Close Chat', 'mcnac-n8n-chat-advanced' ); ?>">&times;</button>
				</div>
				<div id="mcnac-chat-messages" class="mcnac-chat-messages">
					<!-- Messages will be injected here -->
				</div>
				<div class="mcnac-chat-input-area">
					<input type="text" id="mcnac-chat-input" placeholder="" />
					<button id="mcnac-chat-send">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
				</div>
				<?php if ( isset( $options['show_powered_by'] ) && $options['show_powered_by'] == 1 ) : ?>
					<div class="mcnac-powered-by">
						<a href="https://mcodform.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desarrollado por mcodform.com', 'mcnac-n8n-chat-advanced' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
