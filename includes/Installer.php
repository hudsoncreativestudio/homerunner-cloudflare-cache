<?php
/**
 * Installer
 *
 * @package HomerunnerCfCache
 */
namespace HomerunnerCfCache;

// Load required files.
include_once dirname( HOMECFCC_PLUGIN_FILE ) . "/includes/functions.php";

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {

	public static function activate() {
		self::default_options();
		self::migrate_options();

		update_option( 'homelocal_version', HOMECFCC_VERSION );
	}

	public static function deactivate() {
		static::clear_cron_jobs();
	}

	public static function upgrade() {
		self::default_options();
		self::migrate_options();

		update_option( 'homelocal_version', HOMECFCC_VERSION );
	}


	/**
	 * Clear registered cronjobs.
	 */
	public static function clear_cron_jobs() {
		$hooks = [
		];

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}

			wp_clear_scheduled_hook( $hook );
		}
	}

	public static function default_options() {
		add_option( 'homelocal_generate_property_review_stat', 'yes' );
		add_option( 'homelocal_tools_enabled', 'yes' );
		add_option( 'homelocal_explorer_search_log_enabled', 'yes' );
	}

	public static function migrate_options() {
		$change_options = [];

		if ( ! empty( $change_options ) ) {
			// Change option names.
			foreach ( $change_options as $old_name => $new_name ) {
				if ( $old_val = get_option( $old_name ) ) {
					update_option( $new_name, $old_val );
				}

				delete_option( $old_name );
			}
		}

		$delete_options = [];
		if ( ! empty( $delete_options ) ) {
			array_walk( $delete_options, 'delete_option' );
		}

		$options = get_option( 'homerunner_cloudflare_cache_settings' );

		if ( ! empty( $options ) ) {
			update_option( 'homecfcc_api_token', $options['api_token'] );
			update_option( 'homecfcc_zone_id', $options['zone_id'] );
			delete_option( 'homerunner_cloudflare_cache_settings' );
		}
	}
}