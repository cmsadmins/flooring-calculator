<?php
/**
 * Uebernahme von Feldern aus einer frueheren Loesung.
 *
 * Manche Shops haben den Rechner vorher im Child-Theme stehen gehabt, mit
 * eigenen Meta-Keys. Diese Klasse benennt sie einmalig um.
 *
 * Wichtig fuer den Einsatz in einem neuen Shop: **es wird nur umbenannt, was
 * tatsaechlich vorhanden ist.** Findet sich kein einziger alter Schluessel,
 * passiert nichts, und es wird auch nichts vermerkt, damit eine spaetere
 * Uebernahme noch moeglich bleibt.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Datenuebernahme.
 */
final class CAQC_Migrate {

	const OPTION_DONE   = 'caqc_migration_done';
	const OPTION_REPORT = 'caqc_migration_report';
	const VERSION       = 1;

	/**
	 * Alte auf neue Meta-Keys.
	 *
	 * Ab Werk leer. Ein Shop, der den Rechner vorher mit eigenen Feldern
	 * betrieben hat, trägt seine alten Namen über den Filter ein, etwa:
	 *
	 *     add_filter( 'caqc_migration_map', function ( $map ) {
	 *         $map['_my_quantity_type'] = CAQC_Product::META_TYPE;
	 *         return $map;
	 *     } );
	 *
	 * Feste Namen ab Werk wären gefährlich: ein generischer Schlüssel wie
	 * _custom_product_quantity_type kann in einem fremden Shop einem anderen
	 * Plugin gehören.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		/**
		 * Erlaubt, eigene Altfelder zu übernehmen.
		 *
		 * @param array<string, string> $map Alter Name zu neuem Namen.
		 */
		return (array) apply_filters( 'caqc_migration_map', array() );
	}

	/**
	 * Zaehlt, wie viele Zeilen unter den alten Namen liegen.
	 *
	 * @return array<string, int> Nur Schluessel mit Treffern.
	 */
	public static function pending(): array {
		global $wpdb;

		$found = array();

		foreach ( array_keys( self::map() ) as $old ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- einmalige Zählung bei Aktivierung und auf der Einstellungsseite.
			$count = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $old )
			);

			if ( $count > 0 ) {
				$found[ $old ] = $count;
			}
		}

		return $found;
	}

	/**
	 * Laeuft bei der Aktivierung.
	 *
	 * Tut nichts, solange es nichts zu uebernehmen gibt. Ein frischer Shop
	 * merkt von dieser Klasse also gar nichts.
	 */
	public static function maybe_run(): void {
		if ( (int) get_option( self::OPTION_DONE, 0 ) >= self::VERSION ) {
			return;
		}

		if ( empty( self::pending() ) ) {
			return;
		}

		$report = self::run();

		update_option( self::OPTION_DONE, self::VERSION, false );
		update_option( self::OPTION_REPORT, $report, false );
	}

	/**
	 * Fuehrt die Umbenennung durch.
	 *
	 * @return array<string, int> Anzahl je Schluessel.
	 */
	public static function run(): array {
		global $wpdb;

		$report = array();

		// Umbenennen über alle Produkte in einem Rutsch, die Meta-API kann das
		// nicht. Läuft einmal bei der Aktivierung.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( self::map() as $old => $new ) {
			// Zeilen, die den neuen Namen schon tragen, wuerden sonst doppelt
			// liegen. Sie stammen aus einem bereits gelaufenen Versuch.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE old FROM {$wpdb->postmeta} old
					 INNER JOIN {$wpdb->postmeta} new
					 ON new.post_id = old.post_id AND new.meta_key = %s
					 WHERE old.meta_key = %s",
					$new,
					$old
				)
			);

			$changed = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$new,
					$old
				)
			);

			$report[ $old ] = (int) $changed;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		wp_cache_flush_group( 'post_meta' );

		return $report;
	}
}
