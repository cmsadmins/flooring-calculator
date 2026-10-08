<?php
/**
 * Aufräumen beim Löschen des Plugins.
 *
 * Die Produktdaten bleiben stehen. Sie gehören dem Shop, nicht dem Plugin,
 * und ein versehentliches Löschen würde jedes gepflegte Produkt unbrauchbar
 * machen. Entfernt werden nur die eigenen Optionen.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array(
	'caqc_waste_percent',
	'caqc_enabled_types',
	'caqc_show_total',
	'caqc_stock_in_size',
	'caqc_length_hint',
	'caqc_migration_done',
	'caqc_migration_report',
) as $caqc_option ) {
	delete_option( $caqc_option );
}
