<?php
/**
 * Einmalige Umbenennung der Meta-Keys aus der Child-Theme-Zeit.
 *
 * Die alten Namen stammen aus einem Kundenprojekt und taugen nicht fuer ein
 * Plugin, das in mehreren Shops laufen soll. Die Umbenennung laeuft genau
 * einmal, gesteuert ueber eine Option, und ist ueber rollback() umkehrbar.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Datenmigration.
 */
final class CAQC_Migrate {

	const OPTION_DONE = 'caqc_migration_done';
	const VERSION     = 1;

	/**
	 * Alte auf neue Meta-Keys.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		return array(
			'_custom_product_quantity_type'     => CAQC_Product::META_TYPE,
			'_custom_product_size_per_piece'    => CAQC_Product::META_SIZE,
			'_custom_variations_size_per_piece' => CAQC_Product::META_USE_VARIATION,
			'_custom_field_size_per_piece'      => CAQC_Product::META_VARIATION,
		);
	}

	/**
	 * Laeuft bei der Aktivierung des Plugins.
	 */
	public static function maybe_run(): void {
		if ( (int) get_option( self::OPTION_DONE, 0 ) >= self::VERSION ) {
			return;
		}

		$report = self::run();

		update_option( self::OPTION_DONE, self::VERSION, false );
		update_option( 'caqc_migration_report', $report, false );
	}

	/**
	 * Fuehrt die Umbenennung durch.
	 *
	 * @return array<string, int> Anzahl je Schritt.
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

		$report['panels_to_qmst'] = self::convert_panels();

		return $report;
	}

	/**
	 * Stellt Akustikpaneele von Paketen auf Paneele um.
	 *
	 * Die Groessenangabe dieser Produkte ist bereits die Flaeche eines
	 * einzelnen Paneels. Falsch war nur die Beschriftung, die von "Paket"
	 * sprach. Der Typ qmst sagt dasselbe mit der richtigen Einheit.
	 *
	 * @return int Anzahl umgestellter Produkte.
	 */
	private static function convert_panels(): int {
		$term = get_term_by( 'name', 'Akustikpaneele', 'product_cat' );

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
						'value' => 'qms',
					),
				),
			)
		);

		foreach ( $ids as $id ) {
			update_post_meta( (int) $id, CAQC_Product::META_TYPE, 'qmst' );
		}

		return count( $ids );
	}

	/**
	 * Macht die Umbenennung rueckgaengig.
	 *
	 * Wird nicht automatisch aufgerufen. Gedacht fuer den Fall, dass der
	 * Livegang zurueckgenommen werden muss.
	 *
	 * @return array<string, int> Anzahl je Schritt.
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

		// Die Paneele kommen auf den alten Typ zurueck.
		$report['qmst_to_qms'] = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = 'qms' WHERE meta_key = %s AND meta_value = 'qmst'",
				'_custom_product_quantity_type'
			)
		);

		delete_option( self::OPTION_DONE );

		return $report;
	}

	/**
	 * Zaehlt die Produkte je Typ, fuer den Abgleich vor und nach dem Lauf.
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
