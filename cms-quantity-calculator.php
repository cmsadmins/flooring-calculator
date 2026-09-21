<?php
/**
 * Plugin Name: CMS ADMINS Mengenrechner
 * Plugin URI:  https://www.cms-admins.de
 * Description: Rechnet auf WooCommerce-Produktseiten zwischen Fläche, Länge und Verkaufseinheit um und zeigt den Gesamtpreis sofort an. Für Bodenbeläge, Unterlagen, Klebstoffe, Leisten, Paneele und Profile.
 * Version:     1.0.0
 * Author:      CMS ADMINS
 * Author URI:  https://www.cms-admins.de
 * Text Domain: cms-quantity-calculator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: Proprietary
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

defined( 'CAQC_VERSION' ) || define( 'CAQC_VERSION', '1.0.0' );
defined( 'CAQC_PLUGIN_FILE' ) || define( 'CAQC_PLUGIN_FILE', __FILE__ );
defined( 'CAQC_PLUGIN_DIR' ) || define( 'CAQC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
defined( 'CAQC_PLUGIN_URL' ) || define( 'CAQC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'CAQC_PLUGIN_BASENAME' ) || define( 'CAQC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-types.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-product.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-product-meta.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-render.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-cart.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-stock.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-assets.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-migrate.php';

/**
 * Einstiegspunkt.
 */
final class CAQC_Plugin {

	const OPTION_WASTE = 'caqc_waste_percent';

	/**
	 * Startet das Plugin, sobald WooCommerce bereit ist.
	 */
	public static function boot(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
	}

	/**
	 * Haengt alle Bestandteile ein.
	 */
	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_woocommerce' ) );

			return;
		}

		CAQC_Product_Meta::init();
		CAQC_Render::init();
		CAQC_Cart::init();
		CAQC_Stock::init();
		CAQC_Assets::init();

		self::update_checker();
	}

	/**
	 * Verschnitt in Prozent. Der Wert lässt sich je Shop setzen.
	 */
	public static function waste_percent(): float {
		$percent = (float) get_option( self::OPTION_WASTE, 5 );

		if ( $percent <= 0 || $percent > 100 ) {
			$percent = 5.0;
		}

		/**
		 * Erlaubt, den Verschnitt je Shop oder je Warengruppe zu ändern.
		 *
		 * @param float $percent Prozentsatz.
		 */
		return (float) apply_filters( 'caqc_waste_percent', $percent );
	}

	/**
	 * Bindet das Plugin an den Update-Server von CMS ADMINS.
	 */
	private static function update_checker(): void {
		$library = CAQC_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

		if ( ! file_exists( $library ) ) {
			return;
		}

		require_once $library;

		if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://update.cms-admins.de/?action=get_metadata&slug=cms-quantity-calculator',
			CAQC_PLUGIN_FILE,
			'cms-quantity-calculator'
		);
	}

	/**
	 * Hinweis, wenn WooCommerce fehlt.
	 */
	public static function notice_missing_woocommerce(): void {
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'CMS ADMINS Mengenrechner benötigt WooCommerce. Das Plugin bleibt ohne Wirkung.', 'cms-quantity-calculator' )
			. '</p></div>';
	}

	/**
	 * Läuft bei der Aktivierung.
	 */
	public static function activate(): void {
		require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-types.php';
		require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-product.php';
		require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-migrate.php';

		CAQC_Migrate::maybe_run();
	}
}

register_activation_hook( __FILE__, array( 'CAQC_Plugin', 'activate' ) );

CAQC_Plugin::boot();
