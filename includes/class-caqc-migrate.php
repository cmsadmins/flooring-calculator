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
 * @package CMSQuantityCalculator
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
	 * Die alten Namen stammen aus der Child-Theme-Loesung von Munich Floor.
	 * Wer eine andere Vorgeschichte hat, haengt sich in den Filter.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		$map = array(
			'_custom_product_quantity_type'     => CAQC_Product::META_TYPE,
			'_custom_product_size_per_piece'    => CAQC_Product::META_SIZE,
			'_custom_variations_size_per_piece' => CAQC_Product::META_USE_VARIATION,
			'_custom_field_size_per_piece'      => CAQC_Product::META_VARIATION,
		);

		/**
		 * Erlaubt, eigene Altfelder zu uebernehmen.
		 *
		 * @param array<string, string> $map Alter Name zu neuem Namen.
		 */
		return (array) apply_filters( 'caqc_migration_map', $map );
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

		return $report;
	}

	/**
	 * Setzt alle Produkte einer Kategorie auf einen anderen Mengentyp.
	 *
	 * Gedacht fuer Warengruppen, deren Einheit zwar stimmt, deren Bezeichnung
	 * aber nicht. Bei Munich Floor waren das Akustikpaneele, die als Pakete
	 * gefuehrt wurden, obwohl die hinterlegte Groesse schon die Flaeche eines
	 * einzelnen Paneels war.
	 *
	 * **Laeuft nicht von selbst.** Aufruf ueber die Einstellungsseite oder
	 * direkt, damit in einem fremden Shop nichts ungefragt umgestellt wird.
	 *
	 * @param string $category Name oder Slug der Produktkategorie.
	 * @param string $from     Bisheriger Typ.
	 * @param string $to       Neuer Typ.
	 * @param bool   $dry_run  Nur zaehlen, nichts aendern.
	 * @return int Anzahl betroffener Produkte.
	 */
	public static function convert_category( string $category, string $from, string $to, bool $dry_run = false ): int {
		if ( ! CAQC_Types::exists( $to ) ) {
			return 0;
		}

		$term = get_term_by( 'slug', $category, 'product_cat' );

		if ( ! $term instanceof WP_Term ) {
			$term = get_term_by( 'name', $category, 'product_cat' );
		}

		if ( ! $term instanceof WP_Term ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => $term->term_id,
						'include_children' => true,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => CAQC_Product::META_TYPE,
						'value' => $from,
					),
				),
			)
		);

		if ( $dry_run ) {
			return count( $ids );
		}

		foreach ( $ids as $id ) {
			update_post_meta( (int) $id, CAQC_Product::META_TYPE, $to );
		}

		return count( $ids );
	}

	/**
	 * Macht die Umbenennung rueckgaengig.
	 *
	 * Wird nicht automatisch aufgerufen. Gedacht fuer den Fall, dass ein
	 * Livegang zurueckgenommen werden muss.
	 *
	 * @return array<string, int> Anzahl je Schluessel.
	 */
	public static function rollback(): array {
		global $wpdb;

		$report = array();

		foreach ( self::map() as $old => $new ) {
			$changed = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$old,
					$new
				)
			);

			$report[ $new ] = (int) $changed;
		}

		delete_option( self::OPTION_DONE );
		delete_option( self::OPTION_REPORT );

		return $report;
	}

	/**
	 * Zaehlt die Produkte je Typ, fuer den Abgleich vor und nach einem Lauf.
	 *
	 * @param string $meta_key Zu zaehlender Schluessel.
	 * @return array<string, int>
	 */
	public static function count_types( string $meta_key = CAQC_Product::META_TYPE ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS type, COUNT(*) AS total
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = %s
				 GROUP BY meta_value",
				$meta_key
			),
			ARRAY_A
		);

		$counts = array();

		foreach ( (array) $rows as $row ) {
			$counts[ (string) $row['type'] ] = (int) $row['total'];
		}

		return $counts;
	}
}
