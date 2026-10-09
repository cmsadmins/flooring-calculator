<?php
/**
 * Quantity info in cart, checkout, order and email.
 *
 * Everything runs through filters. The template override for email-order-items.php
 * that the predecessor needed in the child theme is no longer necessary.
 *
 * Fixed compared to the template: the area info used the same DOM ID
 * on every cart row. A class is used here instead.
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
		// No custom filter on the quantity column of the classic checkout: the
		// info already appears there as item data (see below), and a
		// second path showed the same number twice.
		// Order overview and email get the info as item data below the
		// product name. The filter on the quantity column would be the
		// shorter route, but WooCommerce also uses the same string in the
		// Schema.org block of the email, and HTML does not belong there.
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'order_item_meta' ), 10, 3 );

		// The block checkout renders in the browser and fetches its data via the
		// Store API. The filters above do not run there. This info travels along
		// as item data and therefore shows up below the product there too.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'store_api_item_data' ), 10, 2 );

		// Write the info permanently to the item when the order is placed. Only then
		// does it also appear in the admin, in the shop's email and on invoices,
		// and it stays correct if the product's size changes later.
		// Classic checkout and block checkout both go through this hook.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_item' ), 10, 3 );

		// The admin also shows keys with a leading underscore unless they are on
		// this list.
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
	 * @param WC_Order      $order   Order.
	 */
	public static function order_item_meta( $item_id, $item, $order ) {
		$note = self::order_size_note( $item );

		if ( '' === $note ) {
			return;
		}

		// Email clients do not load external stylesheets, so the formatting
		// sits directly on the element. In the web view the class applies
		// as well.
		echo '<span class="caqc-cart-size" style="display:block;margin-top:4px;font-size:0.9em;color:#6b6b6b;">'
			. esc_html( $note )
			. '</span>';
	}

	/**
	 * Item data for checkout (classic and block), mini cart and Store API.
	 *
	 * The only exception is the classic cart page. There the info already
	 * sits below the quantity field and updates immediately when the
	 * quantity changes. The same line twice would just be noise there.
	 *
	 * The check uses is_cart() rather than the Store API: the block
	 * checkout pre-renders its items on page load, and at that
	 * moment it is not a Store API request.
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Row data.
	 * @return array
	 */
	public static function store_api_item_data( $item_data, $cart_item ) {
		if ( self::is_classic_cart() ) {
			return $item_data;
		}

		// The label is already next to it here, so only the value.
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
	 * Classic cart page, i.e. not the cart block?
	 *
	 * The block shows the quantity field without the woocommerce_cart_item_quantity filter,
	 * so the info has to appear there as item data.
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

		// WooCommerce shows stored info itself as item data.
		// It is only calculated for orders from before version 1.0.1.
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

		// For goods sold by the meter the quantity already is the measure. A line
		// "equals 18 m" next to the quantity 18 would just repeat it.
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

		// Two decimals as on the product page, so that 19,2 m does not
		// turn into a different number in the cart than the 19,20 m before.
		$total  = wc_format_decimal( $quantity * $size, 2 );
		// Non-breaking space, otherwise the unit ends up alone on the next
		// line in narrow cart columns.
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
