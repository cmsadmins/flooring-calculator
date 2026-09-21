<?php
/**
 * Einbindung von Skript und Stil.
 *
 * Beide Dateien tragen eine Versionsnummer, damit ein Browser-Cache nach einer
 * Aenderung tatsaechlich neu laedt. Die Vorlage im Child-Theme liess die
 * Version auf null und hatte ausserdem die Abhaengigkeit von jQuery vergessen,
 * obwohl die Datei mit jQuery(document).ready beginnt.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Frontend-Assets.
 */
final class CAQC_Assets {

	/**
	 * Haengt die Einbindung ein.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
	}

	/**
	 * Laedt Skript und Stil auf Produkt-, Warenkorb- und Kassenseiten.
	 */
	public static function enqueue(): void {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		$needs_style  = is_product() || is_cart() || is_checkout();
		$needs_script = is_product();

		if ( ! $needs_style ) {
			return;
		}

		wp_enqueue_style(
			'caqc',
			CAQC_PLUGIN_URL . 'assets/css/caqc.css',
			array(),
			self::version( 'assets/css/caqc.css' )
		);

		if ( ! $needs_script ) {
			return;
		}

		wp_enqueue_script(
			'caqc-math',
			CAQC_PLUGIN_URL . 'assets/js/caqc-math.js',
			array(),
			self::version( 'assets/js/caqc-math.js' ),
			true
		);

		wp_enqueue_script(
			'caqc-frontend',
			CAQC_PLUGIN_URL . 'assets/js/caqc-frontend.js',
			array( 'jquery', 'caqc-math' ),
			self::version( 'assets/js/caqc-frontend.js' ),
			true
		);
	}

	/**
	 * Kleines Stylesheet fuer die Produktfelder im Backend.
	 *
	 * @param string $hook Aktuelle Admin-Seite.
	 */
	public static function enqueue_admin( $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.caqc-glue-options .description { color: #666; }'
		);
	}

	/**
	 * Versionsnummer aus dem Aenderungsdatum, im Zweifel die Plugin-Version.
	 *
	 * @param string $relative Pfad innerhalb des Plugins.
	 */
	private static function version( string $relative ): string {
		$path = CAQC_PLUGIN_DIR . $relative;

		if ( file_exists( $path ) ) {
			return CAQC_VERSION . '.' . (string) filemtime( $path );
		}

		return CAQC_VERSION;
	}
}
