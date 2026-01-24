<?php
/**
 * Plugin Name: MCNAC N8N Chat Advanced
 * Description: Chat integration for n8n.
 * Version: 1.0.0
 * Author: crleguizamon
 * Author URI: https://mcodform.com/
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * License: GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MCNAC_VERSION', '1.0.0' );
define( 'MCNAC_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCNAC_URL', plugin_dir_url( __FILE__ ) );

// Include and initialize settings
require_once MCNAC_DIR . 'includes/class-mcnac-settings.php';
$mcnac_settings = new MCNAC_Settings();
$mcnac_settings->init();

// Include and initialize frontend
require_once MCNAC_DIR . 'includes/class-mcnac-frontend.php';
$mcnac_frontend = new MCNAC_Frontend();
$mcnac_frontend->init();

/**
 * Add Settings link to plugin actions.
 */
function mcnac_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=mcnac-n8n-chat">' . __( 'Settings', 'mcnac-n8n-chat-advanced' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mcnac_add_settings_link' );

/**
 * Debug function to test webhook connection.
 * Trigger via: /wp-admin/admin.php?page=mcnac-debug or simply checking param on admin_init
 * Using a simple admin_init hook for quick testing as requested.
 * Usage: Visit yoursite.com/wp-admin/?mcnac_debug=test
 */
function mcnac_debug_trigger_check() {
	// Check for debug parameter and capability
	if ( isset( $_GET['mcnac_debug'] ) && 'test' === $_GET['mcnac_debug'] && current_user_can( 'manage_options' ) ) {
		mcnac_send_debug_webhook();
		exit; // Stop execution to show output
	}
}
add_action( 'admin_init', 'mcnac_debug_trigger_check' );

/**
 * Sends a test payload to the configured webhook.
 */
function mcnac_send_debug_webhook() {
	$webhook_url = 'https://automations.devcristian.com/webhook/766621c3-8e4d-4402-8dee-3ab181b28fc3/chat';
	
	$user = wp_get_current_user();
	
	// Sample payload to debug real-time data structure
	$payload = array(
		'action'    => 'debug_init',
		'message'   => 'Hello from WordPress Debug Mode',
		'user_id'   => $user->ID,
		'user_name' => $user->display_name,
		'timestamp' => time(),
		'site_url'  => get_site_url(),
	);

	$args = array(
		'body'        => wp_json_encode( $payload ),
		'headers'     => array(
			'Content-Type' => 'application/json',
		),
		'timeout'     => 45,
		'data_format' => 'body',
	);

	$response = wp_remote_post( $webhook_url, $args );

	// Output results for debugging
	echo '<div style="font-family: monospace; padding: 20px; background: #f0f0f0;">';
	echo '<h2>MCNAC Debug Output</h2>';
	
	echo '<h3>Target URL:</h3>';
	echo '<pre>' . esc_html( $webhook_url ) . '</pre>';

	echo '<h3>Sent Payload:</h3>';
	echo '<pre>' . esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT ) ) . '</pre>';

	if ( is_wp_error( $response ) ) {
		echo '<h3 style="color: red;">Error:</h3>';
		echo '<pre>' . esc_html( $response->get_error_message() ) . '</pre>';
	} else {
		echo '<h3 style="color: green;">Success! Response Code: ' . esc_html( wp_remote_retrieve_response_code( $response ) ) . '</h3>';
		echo '<h4>Response Body:</h4>';
		echo '<pre>' . esc_html( wp_remote_retrieve_body( $response ) ) . '</pre>';
	}
	
	echo '</div>';
}
