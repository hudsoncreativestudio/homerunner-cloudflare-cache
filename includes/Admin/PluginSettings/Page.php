<?php
/**
 * Admin Plugin Settings Page Class.
 *
 * @package HomerunnerCfCache
 */
namespace HomerunnerCfCache\Admin\PluginSettings;

use Shazzad\WpFormUi;
use HomerunnerCfCache\Settings\PluginSettings;
use HomerunnerCfCache\SettingsRepository;

/**
 * Page class.
 */
class Page {

	public $page_slug = 'homecfcc-plugin-settings';

	protected $settings_instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings_instance = PluginSettings::instance();

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
	}

	/**
	 * Register settings page menu.
	 */
	public function admin_menu() {
		$admin_page = add_submenu_page(
			'options-general.php',
			__( 'Homerunner Cloudflare Cache - Plugin Settings', 'homecfcc' ),
			__( 'Homerunner Cloudflare Cache', 'homecfcc' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'enqueue_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'load_page' ) );
	}

	public function load_page() {
		$this->settings_instance->store_default_settings();
	}

	/**
	 * Render admin page.
	 */
	public function render_page() {
		?>
		<div class="wrap homecfcc-wrap">
			<h1>
				<?php
				printf( __( 'Homerunner Cloudflare Cache - v%s', 'homecfcc' ), HOMECFCC_VERSION );
				?>
			</h1>

			<div class="homecfcc-with-sidebar">
				<div class="homecfcc-primary">
					<div class="homecfcc-form-wrap">
						<?php
						$instance = $this->settings_instance;
						( new WpFormUi\Form\Form() )
							->setValues( $instance->get_settings() )
							->setSettings( [ 
								'ajax'            => true,
								'id'              => 'homecfcc-settings-form-' . $instance->get_id(),
								'label_alignment' => 'right',
								'theme'           => 'basic',
								'success_text'    => __( 'Settings updated successfully.', 'homegb' ),
								'action'          => SettingsRepository::instance()->get_settings_rest_url( $instance->get_id() ) . '?_wpnonce=' . wp_create_nonce( 'wp_rest' ),
							] )
							->addFields( $instance->get_fields() )
							->render();
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue page scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'homecfcc-admin-common' );
		wp_enqueue_script( 'homecfcc-admin-common' );

		WpFormUi\Provider::enqueueScripts();
	}
}