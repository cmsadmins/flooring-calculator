<?php
/**
 * Fields in the product admin.
 *
 * Fixed compared to the template in the child theme: missing isset checks,
 * missing whitelisting of the type, and the behaviour that a cleared field
 * could never delete the old value.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Product and variation fields.
 */
final class CAQC_Product_Meta {

	/**
	 * Hooks the fields into product management.
	 */
	public static function init(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'render_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_fields' ) );

		add_action( 'woocommerce_variation_options_pricing', array( __CLASS__, 'render_variation_field' ), 100, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_field' ), 10, 2 );
	}

	/**
	 * Fields on the General tab.
	 */
	public static function render_product_fields(): void {
		echo '<div class="options_group caqc-product-options">';

		woocommerce_wp_select(
			array(
				'id'          => CAQC_Product::META_TYPE,
				'label'       => __( 'Quantity type', 'flooring-calculator' ),
				'options'     => CAQC_Types::options(),
				'desc_tip'    => true,
				'description' => __( 'How this product is measured and sold.', 'flooring-calculator' ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => CAQC_Product::META_USE_VARIATION,
				'label'       => __( 'Use "Size per unit" from variations', 'flooring-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'The calculator uses the "Size per unit" of each variation. Useful when variations differ in size. The field below is ignored then.', 'flooring-calculator' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_SIZE,
				'label'       => __( 'Size per unit', 'flooring-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Size contained in one unit of this product, for example 0.46 for 0.46 m² per pack or 8.5 for a roll of 8.5 m².', 'flooring-calculator' ),
				'type'        => 'text',
			)
		);

		echo '</div>';

		echo '<div class="options_group caqc-glue-options">';

		echo '<p class="form-field"><span class="description">'
			. esc_html__( 'Only for the quantity type "Coverage / container". Both fields are required, otherwise the product page shows the plain quantity field.', 'flooring-calculator' )
			. '</span></p>';

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_CONSUMPTION,
				'label'       => __( 'Coverage in kg per m²', 'flooring-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'For example 1 for 1 kg per m². If the manufacturer gives a range, enter the upper value so the amount is sufficient.', 'flooring-calculator' ),
				'type'        => 'text',
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => CAQC_Product::META_PACK_SIZE,
				'label'       => __( 'Container size in kg', 'flooring-calculator' ),
				'desc_tip'    => true,
				'description' => __( 'Content of one container, for example 10 for a 10 kg bucket.', 'flooring-calculator' ),
				'type'        => 'text',
			)
		);

		echo '</div>';
	}

	/**
	 * Saves the product fields.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save_product_fields( $post_id ): void {
		$post_id = (int) $post_id;

		// WooCommerce checks nonce and capability before this hook. The
		// check is repeated here anyway so that the hook cannot be triggered
		// from elsewhere without a nonce.
		$nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_key( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) || ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// The field set can still be missing, e.g. during an import.
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
	 * Field on a single variation.
	 *
	 * @param int     $loop           Index of the variation in the form.
	 * @param array   $variation_data Raw data passed by WooCommerce.
	 * @param WP_Post $variation      Variation.
	 */
	public static function render_variation_field( $loop, $variation_data, $variation ): void {
		woocommerce_wp_text_input(
			array(
				'id'            => CAQC_Product::META_VARIATION . '[' . (int) $loop . ']',
				'class'         => 'short',
				'wrapper_class' => 'form-row',
				'label'         => __( 'Size per unit', 'flooring-calculator' ),
				'desc_tip'      => true,
				'description'   => __( 'Size contained in one unit of this variation, for example 1.63 for a panel of 1.63 m².', 'flooring-calculator' ),
				'value'         => get_post_meta( (int) $variation->ID, CAQC_Product::META_VARIATION, true ),
				'type'          => 'text',
			)
		);
	}

	/**
	 * Saves the variation field.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Index in the form.
	 */
	public static function save_variation_field( $variation_id, $loop ): void {
		$key = CAQC_Product::META_VARIATION;

		// WooCommerce saves variations via Ajax with its own nonce.
		if ( ! check_ajax_referer( 'save-variations', 'security', false ) || ! current_user_can( 'edit_product', (int) $variation_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ $key ][ $loop ] ) ) {
			return;
		}

		self::write_number( (int) $variation_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ][ $loop ] ) ) );
	}

	/**
	 * Reads a number field from the form and writes it.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 */
	private static function save_number( int $post_id, string $key ): void {
		// The nonce was checked by save_product_fields().
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) ) {
			return;
		}

		self::write_number( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Writes a number, or deletes the field on empty input.
	 *
	 * A cleared field must remove the value. The legacy code checked for
	 * !empty() and kept the old value, so a wrong size
	 * could no longer be removed.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $raw     Raw value from the form.
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

		// Input with a comma is stored as typed, calculations go through
		// CAQC_Product::to_float() anyway.
		update_post_meta( $post_id, $key, str_replace( ',', '.', $value ) );
	}
}
