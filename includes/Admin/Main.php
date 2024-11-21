<?php
namespace HomerunnerCfCache\Admin;

class Main {

	const OPTION_NAME = 'homerunner_cloudflare_cache_settings';

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */

	public static function setup() {
		add_action( 'admin_menu', [ __CLASS__, 'register_settings_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}


	public static function register_settings_page() {
		add_options_page(
			'Homerunner Cloudflare Cache Settings',
			'Homerunner Cloudflare Cache',
			'manage_options',
			'homerunner-cloudflare-cache',
			[ __CLASS__, 'settings_page_callback' ]
		);
	}

	public static function register_settings() {
		register_setting( 'homerunner_cloudflare_cache_group', self::OPTION_NAME );

		add_settings_section(
			'homerunner_cloudflare_cache_section',
			__( 'Cloudflare API Credentials' ),
			'__return_empty_string',
			'homerunner-cloudflare-cache'
		);

		add_settings_field(
			'api_token',
			__( 'API Token' ),
			[ __CLASS__, 'api_token_callback' ],
			'homerunner-cloudflare-cache',
			'homerunner_cloudflare_cache_section'
		);

		add_settings_field(
			'zone_id',
			__( 'Zone ID' ),
			[ __CLASS__, 'zone_id_callback' ],
			'homerunner-cloudflare-cache',
			'homerunner_cloudflare_cache_section'
		);
	}

	public static function settings_page_callback() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Homerunner Cloudflare Cache Settings' ); ?></h1>
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

	public static function api_token_callback() {
		$options   = get_option( self::OPTION_NAME );
		$api_token = isset( $options['api_token'] ) ? esc_attr( $options['api_token'] ) : '';
		echo '<input type="text" name="' . self::OPTION_NAME . '[api_token]" value="' . $api_token . '" class="regular-text">';
	}

	public static function zone_id_callback() {
		$options = get_option( self::OPTION_NAME );
		$zone_id = isset( $options['zone_id'] ) ? esc_attr( $options['zone_id'] ) : '';
		echo '<input type="text" name="' . self::OPTION_NAME . '[zone_id]" value="' . $zone_id . '" class="regular-text">';
	}
}