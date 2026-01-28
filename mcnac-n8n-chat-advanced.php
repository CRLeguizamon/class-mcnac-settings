<?php
/**
 * Plugin Name: MCOD N8N node Chat Advanced
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

// Include and initialize exclusions
require_once MCNAC_DIR . 'includes/class-mcnac-exclusions.php';
$mcnac_exclusions = new MCNAC_Exclusions();
$mcnac_exclusions->init();

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


