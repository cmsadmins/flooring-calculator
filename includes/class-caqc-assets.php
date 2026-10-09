<?php
/**
 * Enqueues script and style.
 *
 * Both files carry a version number so that the browser cache really
 * reloads after a change. The template in the child theme left the
 * version at null and also forgot the jQuery dependency,
 * even though the file starts with jQuery(document).ready.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Frontend assets.
 */
final class CAQC_Assets {

	/**
	 * Hooks in the enqueueing.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
	}

	/**
	 * Loads script and style on product, cart and checkout pages.
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
	 * Small stylesheet for the product fields in the admin.
	 *
	 * @param string $hook Current admin page.
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
	 * Version number from the modification time, falling back to the plugin version.
	 *
	 * @param string $relative Path inside the plugin.
	 */
	private static function version( string $relative ): string {
		$path = CAQC_PLUGIN_DIR . $relative;

		if ( file_exists( $path ) ) {
			return CAQC_VERSION . '.' . (string) filemtime( $path );
		}

		return CAQC_VERSION;
	}
}
