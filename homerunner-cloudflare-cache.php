<?php
/**
 * Plugin Name: Homerunner Cloudflare Cache
 * Plugin URI: https://github.com/hudsoncreativestudio/homerunner-cloudflare-cache
 * Description: Clears Cloudflare cache whenever a page, post, or custom post type is updated in WordPress. Stores API credentials for Cloudflare.
 * Version: 1.0.0
 * Author: Hudson Creative Studio
 * Author URI: https://hudsoncreativestudio.com
 * Requires at least: 5.8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'HOMECFCC_VERSION' ) ) {
	define( 'HOMECFCC_VERSION', '1.0.0' );
}

class Homerunner_Cloudflare_Cache {

	const OPTION_NAME = 'homerunner_cloudflare_cache_settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'save_post', [ $this, 'clear_cloudflare_cache' ], 10, 3 );
	}

	public function register_settings_page() {
		add_options_page(
			'Homerunner Cloudflare Cache Settings',
			'Homerunner Cloudflare Cache',
			'manage_options',
			'homerunner-cloudflare-cache',
			[ $this, 'settings_page_callback' ]
		);
	}

	public function register_settings() {
		register_setting( 'homerunner_cloudflare_cache_group', self::OPTION_NAME );

		add_settings_section(
			'homerunner_cloudflare_cache_section',
			'Cloudflare API Credentials',
			null,
			'homerunner-cloudflare-cache'
		);

		add_settings_field(
			'api_token',
			'API Token',
			[ $this, 'api_token_callback' ],
			'homerunner-cloudflare-cache',
			'homerunner_cloudflare_cache_section'
		);

		add_settings_field(
			'zone_id',
			'Zone ID',
			[ $this, 'zone_id_callback' ],
			'homerunner-cloudflare-cache',
			'homerunner_cloudflare_cache_section'
		);
	}

	public function settings_page_callback() {
		?>
		<div class="wrap">
			<h1>Homerunner Cloudflare Cache Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'homerunner_cloudflare_cache_group' );
				do_settings_sections( 'homerunner-cloudflare-cache' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function api_token_callback() {
		$options   = get_option( self::OPTION_NAME );
		$api_token = isset( $options['api_token'] ) ? esc_attr( $options['api_token'] ) : '';
		echo '<input type="text" name="' . self::OPTION_NAME . '[api_token]" value="' . $api_token . '" class="regular-text">';
	}

	public function zone_id_callback() {
		$options = get_option( self::OPTION_NAME );
		$zone_id = isset( $options['zone_id'] ) ? esc_attr( $options['zone_id'] ) : '';
		echo '<input type="text" name="' . self::OPTION_NAME . '[zone_id]" value="' . $zone_id . '" class="regular-text">';
	}

	public function clear_cloudflare_cache( $post_ID, $post, $update ) {
		if ( wp_is_post_revision( $post_ID ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$options   = get_option( self::OPTION_NAME );
		$api_token = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id   = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $api_token ) || empty( $zone_id ) ) {
			return;
		}

		$url  = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache";
		$body = json_encode( [ 'files' => [ get_permalink( $post_ID ) ] ] );

		$response = wp_remote_post( $url, [ 
			'method'  => 'POST',
			'headers' => [ 
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'Cloudflare cache clear error: ' . $response->get_error_message() );
			// } else {
			// 	error_log( 'Cloudflare cache cleared for post ID: ' . $post_ID );
		}
	}
}

new Homerunner_Cloudflare_Cache();
