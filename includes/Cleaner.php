<?php
namespace HomerunnerCfCache;

class Cleaner {

	public static function setup() {
		add_action( 'save_post', [ __CLASS__, 'after_post_update' ], 10, 2 );
	}

	public static function after_post_update( $post_ID, $post ) {
		if ( wp_is_post_revision( $post_ID ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// check if post type public.
		$post_type = get_post_type_object( $post->post_type );
		if ( ! $post_type->public ) {
			return;
		}

		// check if post status is published.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$url = get_permalink( $post_ID );
		if ( false === $url ) {
			return;
		}

		self::clear_cloudflare_cache( $url );
	}

	protected static function clear_cloudflare_cache( $url ) {
		$options   = get_option( 'homerunner_cloudflare_cache_settings' );
		$api_token = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id   = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $api_token ) || empty( $zone_id ) ) {
			return;
		}

		$api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache";

		$body = json_encode( [ 'files' => [ $url ] ] );

		$response = wp_remote_post( $api_url, [ 
			'method'  => 'POST',
			'headers' => [ 
				'Authorization' => "Bearer {$api_token}",
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
		] );

		if ( is_wp_error( $response ) ) {
			homecfcc_log( 'Cloudflare cache clear error: ' . $response->get_error_message(), [ $response ], 'error' );
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), 1 );
			homecfcc_log( "Cloudflare cache cleared: {$url}", [ 'body' => $body ] );
		}
	}
}