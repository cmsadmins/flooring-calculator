<?php
/**
 * Calculator markup on the product page.
 *
 * Works exclusively through the WooCommerce hooks around the
 * quantity field, not through template overrides. The order is identical in
 * simple.php and variation-add-to-cart-button.php:
 *
 *   before_add_to_cart_quantity -> quantity field -> after_add_to_cart_quantity
 *   -> Button -> after_add_to_cart_button
 *
 * This way the calculator wraps the theme's quantity field instead of
 * replacing it. The theme's plus and minus buttons stay usable.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Output on the product page.
 */
final class CAQC_Render {

	/**
	 * Remembers whether the opening part has been output.
	 */
	private static $is_open = false;

	/**
	 * Hooks the output into the product page.
	 */
	public static function init(): void {
		add_action( 'woocommerce_before_add_to_cart_quantity', array( __CLASS__, 'open' ), 10 );
		add_action( 'woocommerce_after_add_to_cart_quantity', array( __CLASS__, 'close_fields' ), 10 );
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'close_footer' ), 10 );

		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'variation_price_suffix' ), 10, 2 );
		add_filter( 'woocommerce_show_variation_price', '__return_true' );
	}

	/**
	 * Opens the calculator and outputs the left column.
	 */
	public static function open(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$type   = CAQC_Product::type( $product );
		$config = CAQC_Types::get( $type );

		self::$is_open = true;

		printf(
			'<div class="caqc caqc--%1$s" data-caqc="%2$s">',
			esc_attr( $type ),
			esc_attr( (string) wp_json_encode( self::product_data( $product ) ) )
		);

		echo '<div class="caqc-grid">';

		if ( CAQC_Types::INPUT_NONE !== $config['input'] ) {
			self::amount_field( $config );
		}

		echo '<div class="caqc-field caqc-field--qty">';
		printf( '<span class="caqc-label">%s</span>', esc_html( (string) $config['qty_label'] ) );

		// If the quantity itself is a measure, the unit sits next to it as in the
		// left column. The wrapper closes in close_fields().
		if ( ! empty( $config['qty_unit'] ) ) {
			echo '<div class="caqc-input-row">';
		}
		// WooCommerce outputs the quantity field after this.
	}

	/**
	 * Closes the columns and outputs waste allowance, room calculator and price.
	 */
	public static function close_fields(): void {
		global $product;

		if ( ! self::$is_open || ! $product instanceof WC_Product ) {
			return;
		}

		$type   = CAQC_Product::type( $product );
		$config = CAQC_Types::get( $type );

		if ( ! empty( $config['qty_unit'] ) ) {
			printf( '<span class="caqc-unit">%s</span>', esc_html( (string) $config['qty_unit'] ) );
			echo '</div>'; // .caqc-input-row
		}

		echo '<span class="caqc-hint" data-caqc-hint></span>';
		echo '</div>'; // .caqc-field--qty
		echo '</div>'; // .caqc-grid

		$waste = CAQC_Product::waste_percent( $product );

		// No checkbox at 0 %. Goods sold by the meter have no input field, so
		// there the checkbox sits in the room calculator and only affects its result.
		$waste_in_room = $waste > 0 && CAQC_Types::INPUT_NONE === $config['input'];

		if ( $waste > 0 && ! $waste_in_room && CAQC_Product::has_calculator( $product ) ) {
			self::waste_field( $waste );
		}

		if ( CAQC_Types::ROOM_NONE !== $config['room'] ) {
			self::room_field( (string) $config['room'], (string) ( $config['room_label'] ?? '' ), $waste_in_room ? $waste : 0.0 );
		}

		echo '</div>'; // .caqc

		echo '<div class="caqc-footer">';

		if ( CAQC_Settings::show_total() ) {
			self::total_box();
		}

		// WooCommerce outputs the button after this.
	}

	/**
	 * Closes the area below the fields.
	 */
	public static function close_footer(): void {
		if ( ! self::$is_open ) {
			return;
		}

		echo '</div>'; // .caqc-footer

		self::$is_open = false;
	}

	/**
	 * Left column with area or length input.
	 *
	 * @param array<string, mixed> $config Type definition.
	 */
	private static function amount_field( array $config ): void {
		echo '<div class="caqc-field caqc-field--amount">';
		printf( '<span class="caqc-label">%s</span>', esc_html( (string) $config['input_label'] ) );

		// Stepper and unit sit on one line. Without the wrapper around them,
		// a theme that makes input fields wide pushes the unit onto the
		// next line.
		echo '<div class="caqc-input-row">';

		echo '<div class="caqc-stepper">';
		printf(
			'<button type="button" class="caqc-step caqc-step--minus" data-caqc-step="-1" aria-label="%s">&minus;</button>',
			esc_attr__( 'Decrease amount', 'flooring-calculator' )
		);
		printf(
			'<input type="text" class="caqc-amount" data-caqc-amount inputmode="decimal" autocomplete="off" value="" aria-label="%s">',
			esc_attr( (string) $config['input_label'] )
		);
		printf(
			'<button type="button" class="caqc-step caqc-step--plus" data-caqc-step="1" aria-label="%s">+</button>',
			esc_attr__( 'Increase amount', 'flooring-calculator' )
		);
		echo '</div>';

		printf( '<span class="caqc-unit">%s</span>', esc_html( (string) $config['input_unit'] ) );

		echo '</div>';
		echo '<span class="caqc-note" data-caqc-note></span>';
		echo '</div>';
	}

	/**
	 * Waste allowance option.
	 *
	 * @param float $percent Waste allowance of this product.
	 */
	private static function waste_field( float $percent ): void {
		$id = 'caqc-waste-' . wp_rand();

		// 5 should stay "5", but 7,5 should stay "7,5" too. A fixed format would
		// either round or append needless zeros.
		$display = number_format_i18n( $percent, floor( $percent ) === $percent ? 0 : 1 );

		echo '<div class="caqc-waste">';
		printf(
			'<input type="checkbox" id="%1$s" class="caqc-waste-input" data-caqc-waste value="1">',
			esc_attr( $id )
		);
		printf(
			'<label for="%1$s">%2$s</label>',
			esc_attr( $id ),
			esc_html(
				sprintf(
					/* translators: %s: waste percentage, e.g. "10". */
					__( 'Add %s%% for waste', 'flooring-calculator' ),
					$display
				)
			)
		);
		// The customer's input stays as is, the result including waste
		// is shown next to it.
		echo '<span class="caqc-waste-result" data-caqc-waste-result></span>';
		echo '</div>';
	}

	/**
	 * Room calculator as a collapsible section.
	 *
	 * @param string $mode  ROOM_PERIMETER or ROOM_AREA.
	 * @param string $label Section heading.
	 * @param float  $waste Waste checkbox inside the room calculator, 0 for none.
	 */
	private static function room_field( string $mode, string $label, float $waste = 0.0 ): void {
		$summary = '' !== $label ? $label : __( 'Calculate from room dimensions', 'flooring-calculator' );

		printf( '<details class="caqc-room" data-caqc-room="%s">', esc_attr( $mode ) );
		printf( '<summary>%s</summary>', esc_html( $summary ) );

		echo '<div class="caqc-room-inputs">';

		printf(
			'<label class="caqc-room-label"><span class="caqc-room-caption">%1$s</span><span class="caqc-room-field"><input type="text" data-caqc-room-length inputmode="decimal" autocomplete="off" value=""><span class="caqc-room-unit">%2$s</span></span></label>',
			esc_html__( 'Room length', 'flooring-calculator' ),
			esc_html__( 'm', 'flooring-calculator' )
		);

		printf(
			'<label class="caqc-room-label"><span class="caqc-room-caption">%1$s</span><span class="caqc-room-field"><input type="text" data-caqc-room-width inputmode="decimal" autocomplete="off" value=""><span class="caqc-room-unit">%2$s</span></span></label>',
			esc_html__( 'Room width', 'flooring-calculator' ),
			esc_html__( 'm', 'flooring-calculator' )
		);

		printf(
			'<button type="button" class="caqc-room-apply" data-caqc-room-apply>%s</button>',
			esc_html__( 'Apply', 'flooring-calculator' )
		);

		echo '</div>';

		if ( $waste > 0 ) {
			self::waste_field( $waste );
		}

		echo '<p class="caqc-room-result" data-caqc-room-result></p>';
		echo '</details>';
	}

	/**
	 * Price display.
	 */
	private static function total_box(): void {
		echo '<div class="caqc-total" data-caqc-total>';
		printf( '<span class="caqc-total-label">%s</span>', esc_html__( 'Total price', 'flooring-calculator' ) );
		echo '<span class="caqc-total-value" data-caqc-total-value>&ndash;</span>';
		printf( '<span class="caqc-total-note">%s</span>', esc_html__( 'incl. VAT', 'flooring-calculator' ) );
		// For items on sale: regular total price and savings.
		echo '<span class="caqc-total-saving" data-caqc-total-saving></span>';
		echo '</div>';
	}

	/**
	 * Data the frontend script needs.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	private static function product_data( WC_Product $product ): array {
		$type   = CAQC_Product::type( $product );
		$config = CAQC_Types::get( $type );
		$glue   = CAQC_Product::glue( $product );

		$data = array(
			'type'          => $type,
			'input'         => $config['input'],
			'sizePerPiece'  => CAQC_Product::size_per_piece( $product ),
			'useVariation'  => CAQC_Product::uses_variation_size( $product ),
			'variations'    => CAQC_Product::variation_sizes( $product ),
			'unitOne'       => (string) $config['unit_one'],
			'unitMany'      => (string) $config['unit_many'],
			'inputUnit'     => (string) $config['input_unit'],
			'sizeUnit'      => (string) $config['size_unit'],
			'wastePercent'  => CAQC_Product::waste_percent( $product ),
			'room'          => (string) $config['room'],
			'hint'          => (string) $config['hint'],
			'lengthHint'    => CAQC_Settings::length_hint(),
			'hasCalculator' => CAQC_Product::has_calculator( $product ),
			'price'         => null,
			'regularPrice'  => null,
			'glue'          => $glue,
			'priceFormat'   => self::price_format(),
			'i18n'          => array(
				/* translators: 1: sales unit, e.g. "pack", 2: size of one unit, 3: size unit, e.g. "m²". */
				'pieceEquals'   => __( '1 %1$s = %2$s %3$s', 'flooring-calculator' ),
				/* translators: 1: length of one piece, 2: length unit, e.g. "m". */
				'soldByLength'  => __( 'Sold by the meter. Length per piece approx. %1$s %2$s.', 'flooring-calculator' ),
				'chooseVariant' => __( 'Please select a product variation first.', 'flooring-calculator' ),
				/* translators: 1: maximum quantity, 2: sales unit, e.g. "packs". */
				'capped'        => __( 'More than %1$s %2$s are currently not available.', 'flooring-calculator' ),
				/* translators: 1: minimum quantity, 2: sales unit, e.g. "rolls". */
				'minimum'       => __( 'Minimum order: %1$s %2$s.', 'flooring-calculator' ),
				/* translators: 1: calculated area or length, 2: unit, e.g. "m²". */
				'roomResult'    => __( 'Required: %1$s %2$s', 'flooring-calculator' ),
				/* translators: %1$s: adhesive weight. */
				'glueWeight'    => __( 'Required: %1$s kg', 'flooring-calculator' ),
				/* translators: 1: waste percentage, 2: area incl. waste, 3: unit, 4: quantity, 5: sales unit. */
				'wasteResult'   => __( 'incl. %1$s%% waste: %2$s %3$s, %4$s %5$s', 'flooring-calculator' ),
				/* translators: 1: regular total price, 2: amount saved. */
				'saving'        => __( 'instead of %1$s, you save %2$s', 'flooring-calculator' ),
			),
		);

		if ( ! $product->is_type( 'variable' ) ) {
			$data['price'] = (float) wc_get_price_including_tax( $product );

			if ( $product->is_on_sale() && '' !== (string) $product->get_regular_price() ) {
				$data['regularPrice'] = (float) wc_get_price_including_tax(
					$product,
					array( 'price' => $product->get_regular_price() )
				);
			}
		}

		return $data;
	}

	/**
	 * The shop's price format, so the display matches the rest.
	 *
	 * @return array<string, mixed>
	 */
	private static function price_format(): array {
		return array(
			'decimals'          => wc_get_price_decimals(),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			// The script inserts prices as text, so entities like &euro; or
			// &nbsp; have to become characters here already.
			'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
			'format'            => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
		);
	}

	/**
	 * Appends the unit to the variation price.
	 *
	 * @param array      $variation_data Variation data.
	 * @param WC_Product $product        Parent product.
	 * @return array
	 */
	public static function variation_price_suffix( $variation_data, $product ) {
		$config = CAQC_Product::type_config( $product );
		$suffix = (string) $config['price_suffix'];

		if ( '' !== $suffix && isset( $variation_data['price_html'] ) ) {
			$variation_data['price_html'] .= '<span class="caqc-price-suffix">' . wp_kses_post( $suffix ) . '</span>';
		}

		return $variation_data;
	}
}
