<?php
/**
 * Plugin Settings Repository.
 *
 * @package HomerunnerCfCache
 */
namespace HomerunnerCfCache\Settings;

use HomerunnerCfCache\Abstracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PluginSettings extends Settings {

	protected $id = 'plugin_settings';

	public static $priority = 10;

	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function get_name() {
		return __( 'Plugin Settings', 'homecfcc' );
	}

	public function get_fields() {
		return [ 
			array(
				'id'          => 'homecfcc_central',
				'label'       => __( 'Cloudflare Credentials', 'homecfcc' ),
				'type'        => 'section',
				'collapsible' => true,
				'collapsed'   => false,
				'fields'      => $this->get_cloudflare_settings(),
			),
			array(
				'id'          => 'homecfcc_general',
				'label'       => __( 'General Settings', 'homecfcc' ),
				'type'        => 'section',
				'collapsible' => true,
				'collapsed'   => false,
				'fields'      => $this->get_general_settings(),
			)
		];
	}

	protected function get_cloudflare_settings() {
		return [ 
			[ 
				'id'    => 'homecfcc_api_token',
				'name'  => 'homecfcc_api_token',
				'label' => __( 'API Token', 'homecfcc' ),
				'type'  => 'text',
				'desc'  => __( 'Cloudflare API token.', 'homecfcc' ),
			],
			[ 
				'id'    => 'homecfcc_zone_id',
				'name'  => 'homecfcc_zone_id',
				'label' => __( 'Zone ID', 'homecfcc' ),
				'type'  => 'text',
				'desc'  => __( 'Cloudflare zone id.', 'homecfcc' ),
			]
		];
	}

	protected function get_general_settings() {
		$post_types = [];
		foreach ( get_post_types( [ 'public' => true ], 'object' ) as $post_type ) {
			$post_types[ $post_type->name ] = $post_type->label;
		}

		return [ 
			[ 
				'id'      => 'homecfcc_post_types',
				'name'    => 'homecfcc_post_types',
				'label'   => __( 'Post Types', 'homecfcc' ),
				'type'    => 'checkboxes',
				'choices' => $post_types,
				'desc'    => __( 'Select post types to purge cache for.', 'homecfcc' ),
			],
			// [ 
			// 	'id'      => 'homecfcc_purge_process',
			// 	'name'    => 'homecfcc_purge_process',
			// 	'label'   => __( 'Purge Proces', 'homecfcc' ),
			// 	'type'    => 'radio',
			// 	'choices' => [ 
			// 		'instant' => __( 'Instant', 'homecfcc' ),
			// 		'queued'  => __( 'Queued', 'homecfcc' ),
			// 	],
			// 	'desc'    => __( 'Select Queued, if your sites are updating selected post types frequently. For smaller, infrequent, chose Instant', 'homecfcc' ),
			// ],
		];
	}

	public function get_settings() {
		return array(
			'homecfcc_api_token'     => $this->get_setting( 'homecfcc_api_token' ),
			'homecfcc_zone_id'       => $this->get_setting( 'homecfcc_zone_id' ),
			'homecfcc_post_types'    => $this->get_setting( 'homecfcc_post_types' ),
			'homecfcc_purge_process' => $this->get_setting( 'homecfcc_purge_process' ),
		);
	}
}