<?php
/**
 * Register rest api controllers
 */
namespace HomerunnerCfCache;

class RestApi {
	public static function setup() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$classes = [ 
			__NAMESPACE__ . '\\RestController\\SettingsController',
		];

		foreach ( $classes as $class ) {
			$controller = new $class();
			$controller->register_routes();
		}
	}
}