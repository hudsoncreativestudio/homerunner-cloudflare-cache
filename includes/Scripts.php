<?php
/**
 * Register scripts.
 *
 * @package HomerunnerCfCache
 */
namespace HomerunnerCfCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scripts class
 */
class Scripts {
	/**
	 * Initialize.
	 */
	public static function setup() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_admin_scripts' ), 5 );
	}

	public static function register_admin_scripts() {
		wp_register_style(
			'homecfcc-admin-common',
			HOMECFCC_URL . 'assets/css/admin-common.css',
			array(),
			HOMECFCC_VERSION
		);

		wp_register_script(
			'homecfcc-admin-common',
			HOMECFCC_URL . 'assets/js/admin-common.js',
			array( 'jquery' ),
			HOMECFCC_VERSION,
			true
		);

		wp_localize_script( 'homecfcc-admin-common', 'homecfcc_admin_common_settings', array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'requestFailed'   => __( 'Request Failed', 'homecfcc' ),
			'loading'         => __( 'Loading', 'homecfcc' ),
			'serverSideRrror' => __( 'Server side error occured, please check your server error log.', 'homecfcc' ),
			'actionAlert'     => __( 'Are you sure you want to do this? This action can not be undone.', 'homecfcc' ),
			'restEndpoint'    => rest_url( 'homecfcc/v1/' ),
			'restNonce'       => wp_create_nonce( 'wp_rest' )
		) );
	}
}