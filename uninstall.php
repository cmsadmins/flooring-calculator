<?php
/**
 * Cleanup when the plugin is deleted.
 *
 * Product data stays in place. It belongs to the shop, not the plugin,
 * and deleting it by accident would break every maintained product.
 * Only the plugin's own options are removed.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array(
	'caqc_waste_percent',
	'caqc_waste_percent_length',
	'caqc_enabled_types',
	'caqc_show_total',
	'caqc_stock_in_size',
	'caqc_length_hint',
	'caqc_migration_done',
	'caqc_migration_report',
) as $caqc_option ) {
	delete_option( $caqc_option );
}
