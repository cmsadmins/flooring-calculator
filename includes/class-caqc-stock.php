<?php
/**
 * Stock in the product's unit, e.g. "534.06 m² in stock"; other texts pass through unchanged.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Stock text.
 */
final class CAQC_Stock {

	/**
	 * Hooks in the filter.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_get_availability_text', array( __CLASS__, 'availability_text' ), 10, 2 );
	}

	/**
	 * Replaces the piece count with the area.
	 *
	 * @param string     $text    Existing text.
	 * @param WC_Product $product Product.
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

		// Meter and piece goods already show the number that goes into the cart.
		if ( empty( $config['stock_in_size'] ) || '' === $unit ) {
			return $text;
		}

		/**
		 * Allows disabling the conversion of the stock text.
		 *
		 * @param bool       $convert  Whether to convert.
		 * @param WC_Product $product Product.
		 */
		if ( ! apply_filters( 'caqc_stock_in_size', CAQC_Settings::stock_in_size(), $product ) ) {
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
			/* translators: %s: remaining stock with unit, e.g. "12.5 m²". */
			return sprintf( __( 'Only %s left in stock', 'flooring-calculator' ), $amount );
		}

		/* translators: %s: stock with unit, e.g. "534.06 m²". */
		return sprintf( __( '%s in stock', 'flooring-calculator' ), $amount );
	}

	/**
	 * Determines which product and variation the stock belongs to.
	 *
	 * @param WC_Product $product Product or variation.
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
