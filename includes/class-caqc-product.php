<?php
/**
 * Lesezugriff auf die Mengendaten eines Produkts.
 *
 * Diese Klasse ist die einzige Stelle, die Meta-Keys kennt. Produktseite,
 * Warenkorb, Checkout, Bestellmail und Lagertext fragen hier, statt die
 * Aufloesung von Produkt- und Variantengroesse jeweils selbst zu wiederholen.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Mengendaten eines Produkts.
 */
final class CAQC_Product {

	const META_TYPE          = '_caqc_type';
	const META_SIZE          = '_caqc_size_per_piece';
	const META_USE_VARIATION = '_caqc_use_variation_size';
	const META_VARIATION     = '_caqc_variation_size';
	const META_CONSUMPTION   = '_caqc_consumption';
	const META_PACK_SIZE     = '_caqc_pack_size';

	/**
	 * Typschluessel eines Produkts.
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
	 */
	public static function type( $product ): string {
		$value = self::meta( $product, self::META_TYPE );

		return CAQC_Types::exists( $value ) ? $value : CAQC_Types::default_key();
	}

	/**
	 * Typdefinition eines Produkts.
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
	 * @return array<string, mixed>
	 */
	public static function type_config( $product ): array {
		return CAQC_Types::get( self::type( $product ) );
	}

	/**
	 * Nutzt das Produkt die Groessen seiner Varianten?
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
	 */
	public static function uses_variation_size( $product ): bool {
		return 'yes' === self::meta( $product, self::META_USE_VARIATION );
	}

	/**
	 * Groesse, die in einem Stueck steckt.
	 *
	 * Ist eine Variante angegeben, gilt deren Wert. Nutzt das Produkt
	 * Variantengroessen, ohne dass eine Variante feststeht, gibt es null,
	 * weil die Groesse erst mit der Auswahl feststeht.
	 *
	 * @param WC_Product|int $product      Produkt oder Produkt-ID.
	 * @param int            $variation_id Variante, 0 fuer keine.
	 * @return float|null Positive Zahl, oder null wenn nicht nutzbar.
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
	 * Groessen aller Varianten, fuer das data-Attribut der Produktseite.
	 *
	 * @param WC_Product $product Variables Produkt.
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
	 * Klebstoffdaten: Verbrauch in kg je m² und Gebindeinhalt in kg.
	 *
	 * Beide Werte sind Pflicht. Fehlt einer, faellt das Produkt auf reine
	 * Stueckeingabe zurueck, statt einen Rechner mit falschen Zahlen zu zeigen.
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
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
	 * Zeigt dieses Produkt einen Rechner?
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
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
	 * Einheit in Ein- oder Mehrzahl.
	 *
	 * @param WC_Product|int $product  Produkt oder Produkt-ID.
	 * @param float          $quantity Menge.
	 */
	public static function unit( $product, float $quantity ): string {
		$config = self::type_config( $product );

		return (string) ( $quantity > 1 ? $config['unit_many'] : $config['unit_one'] );
	}

	/**
	 * Liest ein Meta-Feld als getrimmten String.
	 *
	 * @param WC_Product|int $product Produkt oder Produkt-ID.
	 * @param string         $key     Meta-Key.
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
	 * Wandelt eine Eingabe in eine positive Zahl.
	 *
	 * Komma und Punkt gelten beide als Dezimaltrennzeichen, weil die Felder
	 * im Backend seit jeher beides erlauben.
	 *
	 * @param mixed $value Rohwert.
	 * @return float|null Positive Zahl, oder null.
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
}
