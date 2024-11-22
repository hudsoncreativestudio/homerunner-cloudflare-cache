<?php
namespace HomerunnerCfCache;

use Shazzad\WpFormUi;

class Plugin {

	/**
	 * Singleton The reference the *Singleton* instance of this class.
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * Returns the *Singleton* instance of this class.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->define_constants();
			self::$instance->include_files();
			self::$instance->initialize();

			add_action( 'init', array( self::$instance, 'maybe_upgrade_db' ) );
		}

		return self::$instance;
	}

	/**
	 * Define constants
	 */
	private function define_constants() {
		define( 'HOMECFCC_DIR', plugin_dir_path( HOMECFCC_PLUGIN_FILE ) );
		define( 'HOMECFCC_URL', plugin_dir_url( HOMECFCC_PLUGIN_FILE ) );
		define( 'HOMECFCC_BASENAME', plugin_basename( HOMECFCC_PLUGIN_FILE ) );
	}

	/**
	 * Define constants
	 */
	private function include_files() {
		// Load dependencies.
		require HOMECFCC_DIR . 'vendor/autoload.php';

		// Load files.
		require HOMECFCC_DIR . 'includes/functions.php';
	}

	/**
	 * Initialize the plugin.
	 */
	private function initialize() {
		WpFormUi\Provider::setup();

		Cleaner::setup();
		RestApi::setup();
		Scripts::setup();

		if ( is_admin() ) {
			Admin\Main::setup();
		}
	}

	/**
	 * Upgrade database if plugin version changes.
	 */
	public function maybe_upgrade_db() {
		if ( ! get_option( 'homecfcc_version' )
			|| version_compare( HOMECFCC_VERSION, get_option( 'homecfcc_version' ), '>' ) ) {
			Installer::upgrade();
		}
	}
}