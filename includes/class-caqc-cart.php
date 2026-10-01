<?php
/**
 * Mengenangabe in Warenkorb, Kasse, Bestellung und E-Mail.
 *
 * Alles laeuft ueber Filter. Das Template-Override fuer email-order-items.php,
 * das der Vorgaenger im Child-Theme brauchte, entfaellt damit.
 *
 * Gegenueber der Vorlage behoben: die Flaechenangabe hatte pro Warenkorbzeile
 * dieselbe DOM-ID. Hier steht eine Klasse.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Anzeige der Flaeche entlang des Bestellwegs.
 */
final class CAQC_Cart {

	/**
	 * Haengt die Filter ein.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'cart_quantity' ), 10, 3 );
		// Kein eigener Filter auf die Mengenspalte der klassischen Kasse: dort
		// erscheint die Angabe bereits als Positionsdatum (siehe unten), ein
		// zweiter Weg zeigte dieselbe Zahl zweimal.
		// Bestellübersicht und E-Mail bekommen die Angabe als Positionsdatum
		// unter dem Artikelnamen. Der Filter auf die Mengenspalte wäre der
		// kürzere Weg, aber WooCommerce verwendet dieselbe Zeichenkette auch
		// im Schema.org-Block der E-Mail, und dort hat HTML nichts zu suchen.
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'order_item_meta' ), 10, 3 );

		// Der Block-Checkout rendert im Browser und holt seine Daten über die
		// Store API. Die Filter oben laufen dort nicht. Diese Angabe reist als
		// Positionsdatum mit und erscheint deshalb auch dort unter dem Artikel.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'store_api_item_data' ), 10, 2 );
	}

	/**
	 * Warenkorb: Hinweis unter dem Mengenfeld.
	 *
	 * @param string $html      Ausgabe des Mengenfelds.
	 * @param string $cart_item_key Schluessel der Zeile.
	 * @param array  $cart_item Zeilendaten.
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
	 * Bestelluebersicht, Danke-Seite und E-Mail.
	 *
	 * @param int           $item_id Positions-ID.
	 * @param WC_Order_Item $item    Bestellposition.
	 * @param WC_Order      $order   Bestellung.
	 */
	public static function order_item_meta( $item_id, $item, $order ) {
		$note = self::order_size_note( $item );

		if ( '' === $note ) {
			return;
		}

		// E-Mail-Programme laden kein externes Stylesheet, deshalb steht die
		// Formatierung hier direkt am Element. In der Webansicht greift
		// zusätzlich die Klasse.
		echo '<span class="caqc-cart-size" style="display:block;margin-top:4px;font-size:0.9em;color:#6b6b6b;">'
			. esc_html( $note )
			. '</span>';
	}

	/**
	 * Positionsdatum fuer Kasse (klassisch und Block), Mini-Warenkorb und Store API.
	 *
	 * Ausgenommen ist allein die klassische Warenkorbseite. Dort steht die
	 * Angabe bereits unter dem Mengenfeld und bewegt sich beim Aendern der
	 * Menge sofort mit. Zweimal dieselbe Zeile waere dort nur Laerm.
	 *
	 * Die Pruefung laeuft ueber is_cart() und nicht ueber die Store API: der
	 * Block-Checkout rendert seine Positionen beim Seitenaufbau vor, und in
	 * diesem Moment ist es keine Anfrage der Store API.
	 *
	 * @param array $item_data Bisherige Positionsdaten.
	 * @param array $cart_item Zeilendaten.
	 * @return array
	 */
	public static function store_api_item_data( $item_data, $cart_item ) {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return $item_data;
		}

		// Hier steht die Bezeichnung schon daneben, deshalb nur der Wert.
		$value = self::size_note( $cart_item, false );

		if ( '' === $value ) {
			return $item_data;
		}

		$config = CAQC_Product::type_config( (int) ( $cart_item['product_id'] ?? 0 ) );

		$item_data[] = array(
			'key'     => (string) ( $config['cart_label'] ?? __( 'Menge', 'cms-quantity-calculator' ) ),
			'value'   => $value,
			'display' => '',
		);

		return $item_data;
	}

	/**
	 * Baut den Hinweis fuer eine Warenkorbzeile.
	 *
	 * @param array $cart_item Zeilendaten.
	 * @param bool  $with_label Mit einleitendem Wort, oder nur die Zahl.
	 */
	private static function size_note( $cart_item, bool $with_label = true ): string {
		if ( ! is_array( $cart_item ) || empty( $cart_item['data'] ) ) {
			return '';
		}

		// Musterartikel stammen aus einem Fremd-Plugin und haben keine Flaeche.
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
	 * Baut den Hinweis fuer eine Bestellposition.
	 *
	 * @param WC_Order_Item $item Bestellposition.
	 */
	private static function order_size_note( $item ): string {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return '';
		}

		if ( $item->get_meta( '_is_sample' ) ) {
			return '';
		}

		return self::format_note(
			(int) $item->get_product_id(),
			(int) $item->get_variation_id(),
			(float) $item->get_quantity()
		);
	}

	/**
	 * Erzeugt den Text, etwa "entspricht 50,04 m²".
	 *
	 * @param int   $product_id   Produkt.
	 * @param int   $variation_id Variante, 0 wenn keine.
	 * @param float $quantity     Menge.
	 * @param bool  $with_label   Mit einleitendem Wort, oder nur die Zahl.
	 */
	private static function format_note( int $product_id, int $variation_id, float $quantity, bool $with_label = true ): string {
		if ( $product_id <= 0 || $quantity <= 0 ) {
			return '';
		}

		$config = CAQC_Product::type_config( $product_id );

		// Bei Meterware ist die Menge bereits das Mass. Eine Zeile
		// "entspricht 18 m" neben der Menge 18 waere nur Wiederholung.
		if ( empty( $config['cart_note'] ) ) {
			return '';
		}

		if ( '' === (string) $config['size_unit'] ) {
			return '';
		}

		$size = CAQC_Product::size_per_piece( $product_id, $variation_id );

		if ( null === $size ) {
			return '';
		}

		// Zwei Nachkommastellen wie auf der Produktseite, damit aus 19,2 m
		// im Warenkorb nicht eine andere Zahl wird als die 19,20 m davor.
		$total  = wc_format_decimal( $quantity * $size, 2 );
		$amount = wc_format_localized_decimal( $total ) . ' ' . (string) $config['size_unit'];

		if ( ! $with_label ) {
			return $amount;
		}

		return sprintf(
			/* translators: %s: Menge mit Einheit. */
			__( 'entspricht %s', 'cms-quantity-calculator' ),
			$amount
		);
	}
}
