<?php
/**
 * Lagerbestand in der Einheit des Produkts.
 *
 * Statt "534 vorrätig" steht bei Flaechenware "534,06 m² vorrätig".
 *
 * Anders als die Vorlage im Child-Theme ersetzt dieser Filter den Text nur
 * dann, wenn wirklich eine Flaechenangabe vorliegt. Alle anderen Faelle reicht
 * er unveraendert durch. Die Vorlage hat den Text pauschal neu gebaut und dabei
 * "Auf Lager" shopweit unterdrueckt, auch bei Produkten ohne Rechner. Dieses
 * Verhalten ist eine Entscheidung des Shops und gehoert deshalb ins Theme.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Lagertext.
 */
final class CAQC_Stock {

	/**
	 * Haengt den Filter ein.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_get_availability_text', array( __CLASS__, 'availability_text' ), 10, 2 );
	}

	/**
	 * Ersetzt die Stueckzahl durch die Flaeche.
	 *
	 * @param string     $text    Bisheriger Text.
	 * @param WC_Product $product Produkt.
	 * @return string
	 */
	public static function availability_text( $text, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $text;
		}

		if ( ! $product->managing_stock() || ! $product->is_in_stock() || $product->is_on_backorder( 1 ) ) {
			return $text;
		}

		$owner = self::owner( $product );

		if ( null === $owner ) {
			return $text;
		}

		$config = CAQC_Product::type_config( $owner['product_id'] );
		$unit   = (string) $config['size_unit'];

		// Nur Flaechenware bekommt eine andere Einheit. Meter- und Stueckware
		// zeigen ohnehin schon die Zahl, die im Warenkorb landet.
		if ( 'm²' !== $unit ) {
			return $text;
		}

		$size = CAQC_Product::size_per_piece( $owner['product_id'], $owner['variation_id'] );

		if ( null === $size ) {
			return $text;
		}

		$stock = $product->get_stock_quantity();

		if ( null === $stock || $stock <= 0 ) {
			return $text;
		}

		$amount = wc_format_localized_decimal( (string) round( $stock * $size, 2 ) ) . ' ' . $unit;

		if ( $stock <= wc_get_low_stock_amount( $product ) ) {
			/* translators: %s: Restmenge mit Einheit. */
			return sprintf( __( 'Only %s left in stock', 'woocommerce' ), $amount );
		}

		/* translators: %s: Menge mit Einheit. */
		return sprintf( __( '%s in stock', 'woocommerce' ), $amount );
	}

	/**
	 * Ermittelt, zu welchem Produkt und welcher Variante der Bestand gehoert.
	 *
	 * @param WC_Product $product Produkt oder Variante.
	 * @return array{product_id: int, variation_id: int}|null
	 */
	private static function owner( WC_Product $product ): ?array {
		if ( $product->is_type( 'variation' ) ) {
			$parent_id = $product->get_parent_id();

			if ( $parent_id <= 0 ) {
				return null;
			}

			return array(
				'product_id'   => $parent_id,
				'variation_id' => $product->get_id(),
			);
		}

		return array(
			'product_id'   => $product->get_id(),
			'variation_id' => 0,
		);
	}
}
