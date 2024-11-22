<?php
/**
 * Plugin Name: Homerunner Cloudflare Cache
 * Plugin URI: https://github.com/hudsoncreativestudio/homerunner-cloudflare-cache
 * Description: Clears Cloudflare cache whenever a page, post, or custom post type is updated in WordPress. Stores API credentials for Cloudflare.
 * Version: 1.0.3
 * Author: Hudson Creative Studio
 * Author URI: https://hudsoncreativestudio.com
 * Requires at least: 5.8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'HOMECFCC_VERSION' ) ) {
	define( 'HOMECFCC_VERSION', '1.0.3' );
}

if ( ! defined( 'HOMECFCC_PLUGIN_FILE' ) ) {
	define( 'HOMECFCC_PLUGIN_FILE', __FILE__ );
}


/**
 * Intialize on plugins_loaded action.
 */
add_action( 'plugins_loaded', function () {
	include_once __DIR__ . '/includes/Plugin.php';

	HomerunnerCfCache\Plugin::get_instance();
} );

/**
 * On Plugin activation.
 */
register_activation_hook( __FILE__, function () {
	include_once __DIR__ . '/includes/Installer.php';

	HomerunnerCfCache\Installer::activate();

	update_option( 'homelocal_flush_rewrite_rules', time() );
} );

/**
 * On Plugin activation.
 */
register_activation_hook( __FILE__, function () {
	include_once __DIR__ . '/includes/Installer.php';

	HomerunnerCfCache\Installer::activate();

	update_option( 'homelocal_flush_rewrite_rules', time() );
} );