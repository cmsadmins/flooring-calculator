<?php
/**
 * Migration of fields from an earlier solution.
 *
 * Some shops previously ran the calculator in their child theme, with
 * their own meta keys. This class renames them once.
 *
 * Important when used in a new shop: **only what actually exists
 * gets renamed.** If not a single old key is found,
 * nothing happens and nothing is recorded, so that a later
 * migration remains possible.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Data migration.
 */
final class CAQC_Migrate {

	const OPTION_DONE   = 'caqc_migration_done';
	const OPTION_REPORT = 'caqc_migration_report';
	const VERSION       = 1;

	/**
	 * Old to new meta keys.
	 *
	 * Empty by default. A shop that previously ran the calculator with its own
	 * fields adds its old names through the filter, for example:
	 *
	 *     add_filter( 'caqc_migration_map', function ( $map ) {
	 *         $map['_my_quantity_type'] = CAQC_Product::META_TYPE;
	 *         return $map;
	 *     } );
	 *
	 * Fixed names by default would be dangerous: a generic key like
	 * _custom_product_quantity_type may belong to another plugin in someone
	 * else's shop.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		/**
		 * Allows migrating custom legacy fields.
		 *
		 * @param array<string, string> $map Old name to new name.
		 */
		return (array) apply_filters( 'caqc_migration_map', array() );
	}

	/**
	 * Counts how many rows exist under the old names.
	 *
	 * @return array<string, int> Only keys with matches.
	 */
	public static function pending(): array {
		global $wpdb;

		$found = array();

		foreach ( array_keys( self::map() ) as $old ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off count on activation and on the settings page.
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
	 * Runs on activation.
	 *
	 * Does nothing as long as there is nothing to migrate. A fresh shop
	 * never notices this class at all.
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
	 * Performs the rename.
	 *
	 * @return array<string, int> Count per key.
	 */
	public static function run(): array {
		global $wpdb;

		$report = array();

		// Rename across all products in one go, the meta API cannot do
		// that. Runs once on activation.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( self::map() as $old => $new ) {
			// Rows that already carry the new name would otherwise exist
			// twice. They come from a previous attempt.
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
