<?php
/**
 * Read access to a product's quantity data; the only place that knows the meta keys.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Quantity data of a product.
 */
final class CAQC_Product {

	const META_TYPE          = '_caqc_type';
	const META_SIZE          = '_caqc_size_per_piece';
	const META_USE_VARIATION = '_caqc_use_variation_size';
	const META_VARIATION     = '_caqc_variation_size';
	const META_CONSUMPTION   = '_caqc_consumption';
	const META_PACK_SIZE     = '_caqc_pack_size';
	const META_WASTE         = '_caqc_waste';

	/**
	 * Type key of a product.
	 *
	 * @param WC_Product|int $product Product or product ID.
	 */
	public static function type( $product ): string {
		$value = self::meta( $product, self::META_TYPE );

		return CAQC_Types::exists( $value ) ? $value : CAQC_Types::default_key();
	}

	/**
	 * Type definition of a product.
	 *
	 * @param WC_Product|int $product Product or product ID.
	 * @return array<string, mixed>
	 */
	public static function type_config( $product ): array {
		return CAQC_Types::get( self::type( $product ) );
	}

	/**
	 * Does the product use the sizes of its variations?
	 *
	 * @param WC_Product|int $product Product or product ID.
	 */
	public static function uses_variation_size( $product ): bool {
		return 'yes' === self::meta( $product, self::META_USE_VARIATION );
	}

	/**
	 * Size of one piece; null for variation sizes until a variation is given.
	 *
	 * @param WC_Product|int $product      Product or product ID.
	 * @param int            $variation_id Variation, 0 for none.
	 * @return float|null Positive number, or null if not usable.
	 */
	public static function size_per_piece( $product, int $variation_id = 0 ): ?float {
		if ( self::uses_variation_size( $product ) ) {
			if ( $variation_id <= 0 ) {
				return null;
			}

			return self::to_float( get_post_meta( $variation_id, self::META_VARIATION, true ) );
		}

		return self::to_float( self::meta( $product, self::META_SIZE ) );
	}

	/**
	 * Sizes of all variations, for the data attribute of the product page.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int, array{id: int, size: float}>
	 */
	public static function variation_sizes( $product ): array {
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		if ( ! self::uses_variation_size( $product ) ) {
			return array();
		}

		$sizes = array();

		foreach ( $product->get_children() as $variation_id ) {
			$size = self::to_float( get_post_meta( (int) $variation_id, self::META_VARIATION, true ) );

			if ( null === $size ) {
				continue;
			}

			$sizes[] = array(
				'id'   => (int) $variation_id,
				'size' => $size,
			);
		}

		return $sizes;
	}

	/**
	 * Adhesive coverage (kg/m²) and container size (kg); null if either is missing.
	 *
	 * @param WC_Product|int $product Product or product ID.
	 * @return array{consumption: float, pack_size: float}|null
	 */
	public static function glue( $product ): ?array {
		$consumption = self::to_float( self::meta( $product, self::META_CONSUMPTION ) );
		$pack_size   = self::to_float( self::meta( $product, self::META_PACK_SIZE ) );

		if ( null === $consumption || null === $pack_size ) {
			return null;
		}

		return array(
			'consumption' => $consumption,
			'pack_size'   => $pack_size,
		);
	}

	/**
	 * Waste percentage: own value, else the group default; variations use the parent.
	 *
	 * @param WC_Product|int $product Product or product ID.
	 */
	public static function waste_percent( $product ): float {
		$product = wc_get_product( $product instanceof WC_Product ? $product : (int) $product );

		if ( ! $product instanceof WC_Product ) {
			return 0.0;
		}

		if ( $product->get_parent_id() > 0 ) {
			$parent  = wc_get_product( $product->get_parent_id() );
			$product = $parent instanceof WC_Product ? $parent : $product;
		}

		$group = self::type_config( $product )['waste'] ?? false;

		// Adhesive and pieces never get waste, not even through the filter.
		if ( ! is_string( $group ) ) {
			return 0.0;
		}

		$own     = self::to_percent( self::meta( $product, self::META_WASTE ) );
		$percent = null !== $own ? $own : CAQC_Settings::waste_default( $group );

		/** This filter is documented in includes/class-caqc-settings.php */
		return (float) apply_filters( 'caqc_waste_percent', $percent, $product, $group );
	}

	/**
	 * Does this product show a calculator?
	 *
	 * @param WC_Product|int $product Product or product ID.
	 */
	public static function has_calculator( $product ): bool {
		$type   = self::type( $product );
		$config = CAQC_Types::get( $type );

		if ( 'kg' === $type ) {
			return null !== self::glue( $product );
		}

		if ( CAQC_Types::INPUT_NONE === $config['input'] ) {
			return false;
		}

		if ( ! empty( $config['needs_size'] ) ) {
			return self::uses_variation_size( $product )
				|| null !== self::size_per_piece( $product );
		}

		return true;
	}

	/**
	 * Unit in singular or plural.
	 *
	 * @param WC_Product|int $product  Product or product ID.
	 * @param float          $quantity Quantity.
	 */
	public static function unit( $product, float $quantity ): string {
		$config = self::type_config( $product );

		return (string) ( $quantity > 1 ? $config['unit_many'] : $config['unit_one'] );
	}

	/**
	 * Reads a meta field as a trimmed string.
	 *
	 * @param WC_Product|int $product Product or product ID.
	 * @param string         $key     Meta key.
	 */
	private static function meta( $product, string $key ): string {
		if ( $product instanceof WC_Product ) {
			$value = $product->get_meta( $key );
		} elseif ( is_numeric( $product ) ) {
			$value = get_post_meta( (int) $product, $key, true );
		} else {
			return '';
		}

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Converts input to a positive number, accepting comma or dot as decimal separator.
	 *
	 * @param mixed $value Raw value.
	 * @return float|null Positive number, or null.
	 */
	public static function to_float( $value ): ?float {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$normalized = str_replace( ',', '.', trim( (string) $value ) );

		if ( '' === $normalized || ! is_numeric( $normalized ) ) {
			return null;
		}

		$number = (float) $normalized;

		return $number > 0 ? $number : null;
	}

	/**
	 * Converts input to 0 to 100; zero is valid and hides the waste checkbox.
	 *
	 * @param mixed $value Raw value.
	 * @return float|null Percentage, or null if empty or invalid.
	 */
	public static function to_percent( $value ): ?float {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$normalized = str_replace( ',', '.', trim( (string) $value ) );

		if ( '' === $normalized || ! is_numeric( $normalized ) ) {
			return null;
		}

		$number = (float) $normalized;

		return ( $number >= 0 && $number <= 100 ) ? round( $number, 1 ) : null;
	}
}
