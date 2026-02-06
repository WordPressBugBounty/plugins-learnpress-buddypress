<?php
/**
 * Plugin Name: LearnPress - BuddyPress Integration
 * Plugin URI: https://thimpress.com/product/learnpress-buddypress-integration/
 * Description: Using the profile system provided by BuddyPress.
 * Author: ThimPress
 * Version: 4.0.3
 * Author URI: http://thimpress.com
 * Tags: learnpress, lms, add-on, buddypress
 * Text Domain: learnpress-buddypress
 * Domain Path: /languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Require_LP_Version: 4.3.2.5
 *
 * @package learnpress-buddypress
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

const LP_ADDON_BUDDYPRESS_FILE = __FILE__;

/**
 * Class LP_Addon_BuddyPress_Preload
 */
class LP_Addon_BuddyPress_Preload {
	/**
	 * @var array
	 */
	public static $addon_info = array();
	/**
	 * @var LP_Addon_Certificates $addon
	 */
	public static $addon;

	/**
	 * Singleton.
	 *
	 * @return LP_Addon_Certificates_Preload|mixed
	 */
	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * LP_Addon_BuddyPress_Preload constructor.
	 */
	protected function __construct() {
		// Set Base name plugin.
		define( 'LP_ADDON_BUDDYPRESS_BASENAME', plugin_basename( LP_ADDON_BUDDYPRESS_FILE ) );

		// Set version addon for LP check .
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		self::$addon_info = get_file_data(
			LP_ADDON_BUDDYPRESS_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_BUDDYPRESS_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_BUDDYPRESS_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );

		// Check LP activated .
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );

			deactivate_plugins( LP_ADDON_BUDDYPRESS_BASENAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return;
		} elseif ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
			add_action( 'admin_notices', array( $this, 'show_note_must_install_buddypress' ) );

			return;
		}

		// Sure LP loaded.
		add_action( 'learn-press/ready', array( $this, 'load' ) );
	}

	/**
	 * Load addon
	 */
	public function load() {
		self::$addon = LP_Addon::load( 'LP_Addon_BuddyPress', 'inc/load.php', __FILE__ );
		self::$addon = LP_Addon_BuddyPress::instance();
	}

	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LP version ' . LP_ADDON_BUDDYPRESS_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}

	public function show_note_must_install_buddypress() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					__(
						'<strong>LearnPress - BuddyPress Integration</strong> addon for <strong>LearnPress</strong> requires %s plugin is <strong>installed</strong> and <strong>activated</strong>.',
						'learnpress-buddypress'
					),
					sprintf(
						'<a href="%s" target="_blank">BuddyPress</a>',
						admin_url( 'plugin-install.php?tab=search&type=term&s=buddypress' )
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

LP_Addon_BuddyPress_Preload::instance();
