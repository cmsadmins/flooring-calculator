<?php
/**
 * Felder im Produkt-Backend.
 *
 * Gegenueber der Vorlage im Child-Theme behoben: fehlende isset-Pruefungen,
 * fehlendes Whitelisting des Typs, und das Verhalten, dass ein geleertes Feld
 * den alten Wert nie loeschen konnte.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Produkt- und Variantenfelder.
 */
final class CAQC_Product_Meta {

	/**
	 * Haengt die Felder in die Produktverwaltung.
	 */
	public static function init(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'render_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_fields' ) );

		add_action( 'woocommerce_variation_options_pricing', array( __CLASS__, 'render_variation_field' ), 100, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_field' ), 10, 2 );
	}

	/**
	 * Felder auf der Registerkarte Allgemein.
	 */
	public static function render_product_fields(): void {
		echo '<div class="options_group caqc-product-options">';

		woocommerce_wp_select(
			array(
				'id'          => CAQC_Product::META_TYPE,
				'label'       => __( 'Mengenangabe', 'cms-quantity-calculator' ),
				'options'     => CAQC_Types::options(),
				'desc_tip'    => true,
				'description' => __( 'Art der Mengenangabe für dieses Produkt.', 'cms-quantity-calculator' ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => CAQC_Product::META_USE_VARIATION,
				'label'       => __( '"Größe pro Stück" von Varianten verwenden', 'cms-quantity-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Die "Größe pro Stück" der einzelnen Varianten wird für die Berechnung verwendet. Sinnvoll, wenn die Varianten unterschiedlich groß sind. Das Feld unten wird dann ignoriert.', 'cms-quantity-calculator' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_SIZE,
				'label'       => __( 'Größe pro Stück', 'cms-quantity-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Größe, die in einem Stück des Produktes enthalten ist, zum Beispiel 0,46 für 0,46 m² je Paket oder 8,5 für eine Rolle mit 8,5 m².', 'cms-quantity-calculator' ),
				'type'        => 'text',
			)
		);

		echo '</div>';

		echo '<div class="options_group caqc-glue-options">';

		echo '<p class="form-field"><span class="description">'
			. esc_html__( 'Nur für die Mengenangabe "Verbrauch / Gebinde". Beide Felder sind nötig, sonst zeigt die Produktseite nur die Stückeingabe.', 'cms-quantity-calculator' )
			. '</span></p>';

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_CONSUMPTION,
				'label'       => __( 'Verbrauch in kg je m²', 'cms-quantity-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Zum Beispiel 1 für 1 kg je m². Bei Bereichsangaben des Herstellers den oberen Wert eintragen, damit die Menge reicht.', 'cms-quantity-calculator' ),
				'type'        => 'text',
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_PACK_SIZE,
				'label'       => __( 'Gebindeinhalt in kg', 'cms-quantity-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Inhalt eines Gebindes, zum Beispiel 10 für einen 10-kg-Eimer.', 'cms-quantity-calculator' ),
				'type'        => 'text',
			)
		);

		echo '</div>';
	}

	/**
	 * Speichert die Produktfelder.
	 *
	 * @param int $post_id Produkt-ID.
	 */
	public static function save_product_fields( $post_id ): void {
		$post_id = (int) $post_id;

		// WooCommerce prueft Nonce und Berechtigung bereits vor diesem Hook.
		// Der Feldsatz kann trotzdem fehlen, etwa bei Quick Edit oder Import.
		if ( ! isset( $_POST[ CAQC_Product::META_TYPE ] ) ) {
			return;
		}

		$type = sanitize_key( wp_unslash( $_POST[ CAQC_Product::META_TYPE ] ) );

		if ( ! CAQC_Types::exists( $type ) ) {
			$type = CAQC_Types::default_key();
		}

		update_post_meta( $post_id, CAQC_Product::META_TYPE, $type );

		update_post_meta(
			$post_id,
			CAQC_Product::META_USE_VARIATION,
			isset( $_POST[ CAQC_Product::META_USE_VARIATION ] ) ? 'yes' : 'no'
		);

		foreach ( array( CAQC_Product::META_SIZE, CAQC_Product::META_CONSUMPTION, CAQC_Product::META_PACK_SIZE ) as $key ) {
			self::save_number( $post_id, $key );
		}
	}

	/**
	 * Feld an der einzelnen Variante.
	 *
	 * @param int     $loop           Index der Variante im Formular.
	 * @param array   $variation_data Rohdaten, von WooCommerce uebergeben.
	 * @param WP_Post $variation      Variante.
	 */
	public static function render_variation_field( $loop, $variation_data, $variation ): void {
		woocommerce_wp_text_input(
			array(
				'id'            => CAQC_Product::META_VARIATION . '[' . (int) $loop . ']',
				'class'         => 'short',
				'wrapper_class' => 'form-row',
				'label'         => __( 'Größe pro Stück', 'cms-quantity-calculator' ),
				'desc_tip'      => true,
				'description'   => __( 'Größe, die in einem Stück dieser Variante enthalten ist, zum Beispiel 1,63 für ein Paneel mit 1,63 m².', 'cms-quantity-calculator' ),
				'value'         => get_post_meta( (int) $variation->ID, CAQC_Product::META_VARIATION, true ),
				'type'          => 'text',
			)
		);
	}

	/**
	 * Speichert das Variantenfeld.
	 *
	 * @param int $variation_id Varianten-ID.
	 * @param int $loop         Index im Formular.
	 */
	public static function save_variation_field( $variation_id, $loop ): void {
		$key = CAQC_Product::META_VARIATION;

		// Der Altcode las den Array-Index vor der isset-Pruefung und lief damit
		// bei jedem Speichern ohne dieses Feld in eine Warnung.
		if ( ! isset( $_POST[ $key ][ $loop ] ) ) {
			return;
		}

		self::write_number( (int) $variation_id, $key, wp_unslash( $_POST[ $key ][ $loop ] ) );
	}

	/**
	 * Liest ein Zahlenfeld aus dem Formular und schreibt es.
	 *
	 * @param int    $post_id Beitrags-ID.
	 * @param string $key     Meta-Key.
	 */
	private static function save_number( int $post_id, string $key ): void {
		if ( ! isset( $_POST[ $key ] ) ) {
			return;
		}

		self::write_number( $post_id, $key, wp_unslash( $_POST[ $key ] ) );
	}

	/**
	 * Schreibt eine Zahl, oder loescht das Feld bei leerer Eingabe.
	 *
	 * Ein geleertes Feld muss den Wert entfernen. Der Altcode pruefte auf
	 * !empty() und liess den alten Wert stehen, sodass eine falsche Groesse
	 * nicht mehr zu entfernen war.
	 *
	 * @param int    $post_id Beitrags-ID.
	 * @param string $key     Meta-Key.
	 * @param mixed  $raw     Rohwert aus dem Formular.
	 */
	private static function write_number( int $post_id, string $key, $raw ): void {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );

			return;
		}

		$number = CAQC_Product::to_float( $value );

		if ( null === $number ) {
			delete_post_meta( $post_id, $key );

			return;
		}

		// Eingabe mit Komma bleibt lesbar gespeichert, gerechnet wird ohnehin
		// ueber CAQC_Product::to_float().
		update_post_meta( $post_id, $key, str_replace( ',', '.', $value ) );
	}
}
