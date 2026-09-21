<?php
/**
 * Aufraeumen beim Loeschen des Plugins.
 *
 * Die Produktdaten bleiben stehen. Sie gehoeren dem Shop, nicht dem Plugin,
 * und ein versehentliches Loeschen wuerde 2.000 Produkte unbrauchbar machen.
 * Entfernt werden nur die eigenen Optionen.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'caqc_waste_percent' );
delete_option( 'caqc_migration_report' );
