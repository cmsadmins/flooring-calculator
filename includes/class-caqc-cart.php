<?php
/**
 * Quantity info in cart, checkout, order and email, via filters only.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Shows the area along the order flow.
 */
final class CAQC_Cart {

	/**
	 * Marker on the order item: the info has been stored.
	 */
	const META_STORED = '_caqc_size_stored';

	/**
	 * Hooks in the filters.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'cart_quantity' ), 10, 3 );
		// No checkout quantity column filter: item data already shows the note there.
		// Not the quantity column filter here either: WooCommerce reuses that string in the email's Schema.org block, where HTML breaks.
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'order_item_meta' ), 10, 2 );

		// The block checkout uses the Store API, where the filters above do not run.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'store_api_item_data' ), 10, 2 );

		// Stored at checkout so admin, emails and invoices stay correct if the product changes later.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_item' ), 10, 3 );

		// The admin shows underscore keys unless they are listed here.
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_order_itemmeta' ) );
	}

	/**
	 * Hides the marker in the order view.
	 *
	 * @param array $keys Hidden keys.
	 * @return array
	 */
	public static function hide_order_itemmeta( $keys ) {
		$keys[] = self::META_STORED;

		return $keys;
	}

	/**
	 * Stores the info as visible item data, e.g. "Area: 41.76 m²".
	 *
	 * @param WC_Order_Item_Product $item          New order item.
	 * @param string                $cart_item_key Cart row key.
	 * @param array                 $values        Cart row.
	 */
	public static function save_order_item( $item, $cart_item_key, $values ): void {
		$value = self::size_note( $values, false );

		if ( '' === $value || ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		$config = CAQC_Product::type_config( (int) ( $values['product_id'] ?? 0 ) );

		$item->add_meta_data( (string) ( $config['cart_label'] ?? __( 'Quantity', 'flooring-calculator' ) ), $value, true );
		$item->add_meta_data( self::META_STORED, '1', true );
	}

	/**
	 * Cart: note below the quantity field.
	 *
	 * @param string $html      Quantity field output.
	 * @param string $cart_item_key Row key.
	 * @param array  $cart_item Row data.
	 * @return string
	 */
	public static function cart_quantity( $html, $cart_item_key, $cart_item ) {
		$note = self::size_note( $cart_item );

		if ( '' === $note ) {
			return $html;
		}

		return $html . '<span class="caqc-cart-size">' . esc_html( $note ) . '</span>';
	}

	/**
	 * Order overview, thank-you page and email.
	 *
	 * @param int           $item_id Item ID.
	 * @param WC_Order_Item $item    Order item.
	 */
	public static function order_item_meta( $item_id, $item ) {
		$note = self::order_size_note( $item );

		if ( '' === $note ) {
			return;
		}

		// Inline style because email clients ignore external stylesheets.
		echo '<span class="caqc-cart-size" style="display:block;margin-top:4px;font-size:0.9em;color:#6b6b6b;">'
			. esc_html( $note )
			. '</span>';
	}

	/**
	 * Item data for checkout, mini cart and Store API, except the classic cart page.
	 *
	 * Uses is_cart() rather than a Store API check because the block checkout
	 * pre-renders its items outside a Store API request.
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Row data.
	 * @return array
	 */
	public static function store_api_item_data( $item_data, $cart_item ) {
		if ( self::is_classic_cart() ) {
			return $item_data;
		}

		$value = self::size_note( $cart_item, false );

		if ( '' === $value ) {
			return $item_data;
		}

		$config = CAQC_Product::type_config( (int) ( $cart_item['product_id'] ?? 0 ) );

		$item_data[] = array(
			'key'     => (string) ( $config['cart_label'] ?? __( 'Quantity', 'flooring-calculator' ) ),
			'value'   => $value,
			'display' => '',
		);

		return $item_data;
	}

	/**
	 * Classic cart page, where the quantity filter already shows the note.
	 */
	private static function is_classic_cart(): bool {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return false;
		}

		return ! has_block( 'woocommerce/cart', wc_get_page_id( 'cart' ) );
	}

	/**
	 * Builds the note for a cart row.
	 *
	 * @param array $cart_item Row data.
	 * @param bool  $with_label With leading word, or just the number.
	 */
	private static function size_note( $cart_item, bool $with_label = true ): string {
		if ( ! is_array( $cart_item ) || empty( $cart_item['data'] ) ) {
			return '';
		}

		// Sample items come from a third-party plugin and have no area.
		if ( ! empty( $cart_item['_is_sample'] ) ) {
			return '';
		}

		$product      = $cart_item['data'];
		$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
		$quantity     = isset( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0.0;

		if ( $product_id <= 0 ) {
			$product_id = $product instanceof WC_Product ? $product->get_id() : 0;
		}

		return self::format_note( $product_id, $variation_id, $quantity, $with_label );
	}

	/**
	 * Builds the note for an order item.
	 *
	 * @param WC_Order_Item $item Order item.
	 */
	private static function order_size_note( $item ): string {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return '';
		}

		if ( $item->get_meta( '_is_sample' ) ) {
			return '';
		}

		// Stored items show as item data; only orders before 1.0.1 need calculating.
		if ( $item->get_meta( self::META_STORED ) ) {
			return '';
		}

		return self::format_note(
			(int) $item->get_product_id(),
			(int) $item->get_variation_id(),
			(float) $item->get_quantity()
		);
	}

	/**
	 * Builds the text, e.g. "equals 50.04 m²".
	 *
	 * @param int   $product_id   Product.
	 * @param int   $variation_id Variation, 0 if none.
	 * @param float $quantity     Quantity.
	 * @param bool  $with_label   With leading word, or just the number.
	 */
	private static function format_note( int $product_id, int $variation_id, float $quantity, bool $with_label = true ): string {
		if ( $product_id <= 0 || $quantity <= 0 ) {
			return '';
		}

		$config = CAQC_Product::type_config( $product_id );

		// Goods sold by the meter: the quantity already is the measure.
		if ( empty( $config['cart_note'] ) ) {
			return '';
		}

		if ( '' === (string) $config['size_unit'] ) {
			return '';
		}

		// Adhesive: weight of all containers, the area depends on consumption.
		if ( CAQC_Types::INPUT_GLUE === $config['input'] ) {
			$glue = CAQC_Product::glue( $product_id );
			$size = null !== $glue ? (float) $glue['pack_size'] : null;
		} else {
			$size = CAQC_Product::size_per_piece( $product_id, $variation_id );
		}

		if ( null === $size ) {
			return '';
		}

		// Two decimals to match the product page.
		$total = wc_format_decimal( $quantity * $size, 2 );
		// Non-breaking space keeps the unit on the same line in narrow columns.
		$amount = wc_format_localized_decimal( $total ) . "\u{00A0}" . (string) $config['size_unit'];

		if ( ! $with_label ) {
			return $amount;
		}

		return sprintf(
			/* translators: %s: total measure with unit, e.g. "41.76 m²". */
			__( 'equals %s', 'flooring-calculator' ),
			$amount
		);
	}
}
