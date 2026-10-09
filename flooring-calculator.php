<?php
/**
 * Plugin Name:          Flooring Calculator for WooCommerce
 * Plugin URI:           https://www.cms-admins.de/
 * Description:          Converts between area, length and sales unit on WooCommerce product pages and shows the total price instantly. For flooring, underlay, adhesives, skirting boards, panels and profiles.
 * Version:              1.0.4
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Patrick Schlesinger
 * Author URI:           https://www.cms-admins.de/
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          flooring-calculator
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 *
 * Copyright (C) 2026 Patrick Schlesinger <flooring-calculator@cms-admins.de>
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

defined( 'CAQC_VERSION' ) || define( 'CAQC_VERSION', '1.0.4' );
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
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-settings.php';
require_once CAQC_PLUGIN_DIR . 'includes/class-caqc-admin.php';

/**
 * Entry point.
 */
final class CAQC_Plugin {

	/**
	 * Boots the plugin once WooCommerce is ready.
	 */
	public static function boot(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_compatibility' ) );
	}

	/**
	 * Hooks in all components.
	 */
	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_woocommerce' ) );

			return;
		}

		CAQC_Settings::init();
		CAQC_Admin::init();
		CAQC_Product_Meta::init();
		CAQC_Render::init();
		CAQC_Cart::init();
		CAQC_Stock::init();
		CAQC_Assets::init();
	}

	/**
	 * Declares HPOS and block checkout support; orders are read only via the WooCommerce API.
	 */
	public static function declare_compatibility(): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CAQC_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CAQC_PLUGIN_FILE, true );
	}

	/**
	 * Shop default waste for area products, kept for backward compatibility.
	 */
	public static function waste_percent(): float {
		return CAQC_Settings::waste_percent();
	}

	/**
	 * Notice when WooCommerce is missing.
	 */
	public static function notice_missing_woocommerce(): void {
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Flooring Calculator for WooCommerce needs WooCommerce to be installed and active.', 'flooring-calculator' )
			. '</p></div>';
	}

	/**
	 * Runs on activation.
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
