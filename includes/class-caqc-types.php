<?php
/**
 * Type table: the only place where a product group is defined.
 *
 * Each type describes which input fields the product page shows, what the
 * unit is called and whether waste allowance and room calculator are offered.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registry of quantity types.
 */
final class CAQC_Types {

	/**
	 * Cache of the type table.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $cache = null;

	/**
	 * Field kind of the left input block.
	 */
	const INPUT_NONE  = 'none';
	const INPUT_AREA  = 'area';   // Area in m².
	const INPUT_METER = 'meter';  // Running meters.
	const INPUT_GLUE  = 'glue';   // Area to be glued in m².

	/**
	 * Further per-type properties that are needed in several places:
	 *
	 *   stock_in_size  show stock in the size instead of pieces
	 *   cart_label     label of the info in the block checkout
	 *   cart_note      whether to show the info in the cart at all
	 *   waste          waste group (WASTE_AREA, WASTE_LENGTH) or false
	 *   needs_size     no calculator without a size per piece
	 *   qty_unit       unit to the right of the quantity field when the quantity
	 *                  itself is a measure (goods sold by the meter)
	 *
	 * Kind of note below the quantity field.
	 */
	const HINT_PIECE  = 'piece';   // "1 package = 0.46 m²"
	const HINT_LENGTH = 'length';  // "Sold by the meter, board length approx. 2.70 m"
	const HINT_NONE   = 'none';

	/**
	 * Kind of room calculator.
	 */
	const ROOM_NONE      = '';
	const ROOM_PERIMETER = 'perimeter'; // Perimeter: 2 * (L + W).
	const ROOM_AREA      = 'area';      // Area: L * W.

	/**
	 * Waste group of a type. Each group has its own default in the settings.
	 */
	const WASTE_AREA   = 'area';   // Floor, underlay, panels.
	const WASTE_LENGTH = 'length'; // Skirting boards, goods sold by the meter.

	/**
	 * Returns the full type table.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$types = array(
			'qms'  => array(
				'label'         => __( 'Square meters / packs', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many packs do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'pack', 'flooring-calculator' ),
				'unit_many'     => __( 'packs', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;pack', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => self::WASTE_AREA,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'qmsr' => array(
				'label'         => __( 'Square meters / rolls', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many rolls do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'roll', 'flooring-calculator' ),
				'unit_many'     => __( 'rolls', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;roll', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => self::WASTE_AREA,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'qmst' => array(
				'label'         => __( 'Square meters / panels', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many panels do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'panel', 'flooring-calculator' ),
				'unit_many'     => __( 'panels', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;panel', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => self::WASTE_AREA,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate panels needed', 'flooring-calculator' ),
			),
			'ms'   => array(
				'label'         => __( 'Meters / pieces', 'flooring-calculator' ),
				'input'         => self::INPUT_METER,
				'input_label'   => __( 'How many meters do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm', 'flooring-calculator' ),
				'qty_label'     => __( 'How many pieces do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'piece', 'flooring-calculator' ),
				'unit_many'     => __( 'pieces', 'flooring-calculator' ),
				'size_unit'     => __( 'm', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;piece', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => self::WASTE_LENGTH,
				'needs_size'    => true,
				'room'          => self::ROOM_PERIMETER,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'm'    => array(
				'label'         => __( 'Meters', 'flooring-calculator' ),
				'input'         => self::INPUT_NONE,
				'input_label'   => '',
				'input_unit'    => '',
				'qty_label'     => __( 'How many meters do you need?', 'flooring-calculator' ),
				'qty_unit'      => __( 'm', 'flooring-calculator' ),
				'unit_one'      => __( 'meter', 'flooring-calculator' ),
				'unit_many'     => __( 'meters', 'flooring-calculator' ),
				'size_unit'     => __( 'm', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;meter', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_LENGTH,
				'cart_note'     => false,
				'waste'         => self::WASTE_LENGTH,
				'needs_size'    => false,
				'room'          => self::ROOM_PERIMETER,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'kg'   => array(
				'label'         => __( 'Coverage / container', 'flooring-calculator' ),
				'input'         => self::INPUT_GLUE,
				'input_label'   => __( 'Area to be glued?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many containers do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'container', 'flooring-calculator' ),
				'unit_many'     => __( 'containers', 'flooring-calculator' ),
				'size_unit'     => __( 'kg', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;container', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Weight', 'flooring-calculator' ),
				'hint'          => self::HINT_NONE,
				'cart_note'     => true,
				'waste'         => false,
				'needs_size'    => false,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate area from room dimensions', 'flooring-calculator' ),
			),
			'stk'  => array(
				'label'         => __( 'Pieces (default)', 'flooring-calculator' ),
				'input'         => self::INPUT_NONE,
				'input_label'   => '',
				'input_unit'    => '',
				'qty_label'     => __( 'How many pieces do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'piece', 'flooring-calculator' ),
				'unit_many'     => __( 'pieces', 'flooring-calculator' ),
				'size_unit'     => '',
				'price_suffix'  => '',
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_NONE,
				'cart_note'     => false,
				'waste'         => false,
				'needs_size'    => false,
				'room'          => self::ROOM_NONE,
			),
		);

		/**
		 * Allows other shops to add their own product groups.
		 *
		 * @param array<string, array<string, mixed>> $types Type table.
		 */
		$types = apply_filters( 'caqc_types', $types );

		self::$cache = $types;

		return $types;
	}

	/**
	 * Discards the cache, e.g. after a settings change.
	 */
	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Returns the definition of a type, or that of the default type.
	 *
	 * @param string $key Type key.
	 * @return array<string, mixed>
	 */
	public static function get( string $key ): array {
		$types = self::all();

		return $types[ $key ] ?? $types[ self::default_key() ];
	}

	/**
	 * Checks whether a type key is known.
	 *
	 * @param string $key Type key.
	 */
	public static function exists( string $key ): bool {
		return isset( self::all()[ $key ] );
	}

	/**
	 * Key of the default type.
	 */
	public static function default_key(): string {
		return 'stk';
	}

	/**
	 * Options for the admin dropdown.
	 *
	 * A shop rarely needs all product groups. Whatever is deselected in the
	 * settings no longer appears here. The logic still knows the types,
	 * so that already maintained products do not break.
	 *
	 * @param bool $include_all Include deselected types as well.
	 * @return array<string, string>
	 */
	public static function options( bool $include_all = false ): array {
		$allowed = $include_all || ! class_exists( 'CAQC_Settings' )
			? array()
			: CAQC_Settings::enabled_types();

		$options = array();

		foreach ( self::all() as $key => $type ) {
			if ( ! empty( $allowed ) && ! in_array( $key, $allowed, true ) ) {
				continue;
			}

			$options[ $key ] = (string) $type['label'];
		}

		return $options;
	}
}
