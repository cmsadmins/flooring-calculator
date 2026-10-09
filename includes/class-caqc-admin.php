<?php
/**
 * Product list conveniences: column, quick edit, bulk edit.
 *
 * A shop with two thousand products cannot be configured product by
 * product. Without this class, rolling out in a new shop takes a full
 * day of work; with it, a matter of minutes.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tools in the product list.
 */
final class CAQC_Admin {

	const NONCE = 'caqc_bulk';

	/**
	 * Hooks everything into the product list.
	 */
	public static function init(): void {
		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column_content' ), 20, 2 );

		add_action( 'woocommerce_product_quick_edit_end', array( __CLASS__, 'quick_edit_fields' ) );
		add_action( 'woocommerce_product_bulk_edit_end', array( __CLASS__, 'bulk_edit_fields' ) );

		add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'save_quick_edit' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( __CLASS__, 'save_bulk_edit' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'quick_edit_script' ) );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_dropdown' ) );
		add_filter( 'request', array( __CLASS__, 'filter_query' ) );
	}

	/**
	 * Column after the stock column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'is_in_stock' === $key ) {
				$new_columns['caqc'] = __( 'Quantity type', 'flooring-calculator' );
			}
		}

		if ( ! isset( $new_columns['caqc'] ) ) {
			$new_columns['caqc'] = __( 'Quantity type', 'flooring-calculator' );
		}

		return $new_columns;
	}

	/**
	 * Column content: type and size, or what is missing.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Product.
	 */
	public static function column_content( $column, $post_id ): void {
		if ( 'caqc' !== $column ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return;
		}

		$type   = CAQC_Product::type( $product );
		$config = CAQC_Types::get( $type );

		echo '<span class="caqc-col-type" data-type="' . esc_attr( $type ) . '" data-waste="'
			. esc_attr( (string) $product->get_meta( CAQC_Product::META_WASTE ) ) . '">'
			. esc_html( (string) $config['label'] ) . '</span>';

		if ( empty( $config['needs_size'] ) && 'kg' !== $type ) {
			return;
		}

		echo '<br>';

		if ( 'kg' === $type ) {
			$glue = CAQC_Product::glue( $product );

			if ( null === $glue ) {
				self::missing_hint( __( 'Coverage or container size missing', 'flooring-calculator' ) );

				return;
			}

			printf(
				'<small>%s</small>',
				esc_html(
					sprintf(
						/* translators: 1: coverage in kg per m², 2: container size in kg. */
						__( '%1$s kg/m², container %2$s kg', 'flooring-calculator' ),
						wc_format_localized_decimal( (string) $glue['consumption'] ),
						wc_format_localized_decimal( (string) $glue['pack_size'] )
					)
				)
			);

			return;
		}

		if ( CAQC_Product::uses_variation_size( $product ) ) {
			printf( '<small>%s</small>', esc_html__( 'Size per variation', 'flooring-calculator' ) );

			return;
		}

		$size = CAQC_Product::size_per_piece( $product );

		if ( null === $size ) {
			self::missing_hint( __( 'Size per unit missing', 'flooring-calculator' ) );

			return;
		}

		printf(
			'<small>%s %s</small>',
			esc_html( wc_format_localized_decimal( (string) $size ) ),
			esc_html( (string) $config['size_unit'] )
		);
	}

	/**
	 * A notice that catches the eye without shouting.
	 *
	 * @param string $text Text.
	 */
	private static function missing_hint( string $text ): void {
		printf( '<small class="caqc-col-missing">%s</small>', esc_html( $text ) );
	}

	/**
	 * Fields in quick edit.
	 */
	public static function quick_edit_fields(): void {
		self::edit_fields( 'quick' );
	}

	/**
	 * Fields in bulk edit.
	 */
	public static function bulk_edit_fields(): void {
		self::edit_fields( 'bulk' );
	}

	/**
	 * Markup shared by both edit forms.
	 *
	 * @param string $mode quick or bulk.
	 */
	private static function edit_fields( string $mode ): void {
		$type_options = CAQC_Types::options();

		wp_nonce_field( self::NONCE, '_caqc_nonce', false );

		echo '<div class="inline-edit-group caqc-inline">';
		echo '<label class="alignleft"><span class="title">'
			. esc_html__( 'Quantity type', 'flooring-calculator' ) . '</span>';
		echo '<span class="input-text-wrap"><select name="_caqc_type">';

		printf(
			'<option value="">%s</option>',
			'bulk' === $mode
				? esc_html__( '— No change —', 'flooring-calculator' )
				: esc_html__( '— Select —', 'flooring-calculator' )
		);

		foreach ( $type_options as $key => $label ) {
			printf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $label ) );
		}

		echo '</select></span></label>';

		echo '<label class="alignleft"><span class="title">'
			. esc_html__( 'Size per unit', 'flooring-calculator' ) . '</span>';
		printf(
			'<span class="input-text-wrap"><input type="text" name="_caqc_size" class="text" value="" placeholder="%s"></span>',
			'bulk' === $mode
				? esc_attr__( 'no change', 'flooring-calculator' )
				: ''
		);
		echo '</label>';

		echo '<label class="alignleft"><span class="title">'
			. esc_html__( 'Waste (%)', 'flooring-calculator' ) . '</span><span class="input-text-wrap">';

		if ( 'bulk' === $mode ) {
			echo '<select name="_caqc_waste_mode">';
			printf( '<option value="">%s</option>', esc_html__( '— No change —', 'flooring-calculator' ) );
			printf( '<option value="set">%s</option>', esc_html__( 'Set to', 'flooring-calculator' ) );
			printf( '<option value="reset">%s</option>', esc_html__( 'Reset to shop default', 'flooring-calculator' ) );
			echo '</select> ';
		}

		printf(
			'<input type="text" name="_caqc_waste" class="text caqc-waste-input" value="" placeholder="%s">',
			'bulk' === $mode ? '' : esc_attr__( 'Shop default', 'flooring-calculator' )
		);
		echo '</span></label>';

		echo '</div>';
	}

	/**
	 * Saves quick edit.
	 *
	 * @param WC_Product $product Product.
	 */
	public static function save_quick_edit( $product ): void {
		self::save_edit( $product, false );
	}

	/**
	 * Saves bulk edit.
	 *
	 * @param WC_Product $product Product.
	 */
	public static function save_bulk_edit( $product ): void {
		self::save_edit( $product, true );
	}

	/**
	 * Applies the values if any are set.
	 *
	 * In bulk edit an empty field means "leave unchanged".
	 * Saving there without this rule would wipe sizes across many products.
	 *
	 * @param WC_Product $product Product.
	 * @param bool       $bulk    Bulk edit.
	 */
	private static function save_edit( $product, bool $bulk ): void {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}

		$nonce = isset( $_REQUEST['_caqc_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_caqc_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}

		if ( isset( $_REQUEST['_caqc_type'] ) ) {
			$type = sanitize_key( wp_unslash( $_REQUEST['_caqc_type'] ) );

			if ( '' !== $type && CAQC_Types::exists( $type ) ) {
				$product->update_meta_data( CAQC_Product::META_TYPE, $type );
			}
		}

		if ( isset( $_REQUEST['_caqc_size'] ) ) {
			$raw_size = trim( sanitize_text_field( wp_unslash( $_REQUEST['_caqc_size'] ) ) );

			if ( '' !== $raw_size ) {
				$size = CAQC_Product::to_float( $raw_size );

				if ( null !== $size ) {
					$product->update_meta_data( CAQC_Product::META_SIZE, str_replace( ',', '.', $raw_size ) );
				}
			} elseif ( ! $bulk ) {
				// In quick edit, empty really does mean empty.
				$product->delete_meta_data( CAQC_Product::META_SIZE );
			}
		}

		self::save_waste( $product, $bulk );

		$product->save();
	}

	/**
	 * Applies the waste field. In bulk edit only an explicit mode changes
	 * anything, so an unrelated bulk save cannot reset hundreds of products.
	 *
	 * @param WC_Product $product Product.
	 * @param bool       $bulk    Bulk edit.
	 */
	private static function save_waste( WC_Product $product, bool $bulk ): void {
		// Nonce and capability were checked by save_edit().
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$raw  = isset( $_REQUEST['_caqc_waste'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['_caqc_waste'] ) ) ) : null;
		$mode = isset( $_REQUEST['_caqc_waste_mode'] ) ? sanitize_key( wp_unslash( $_REQUEST['_caqc_waste_mode'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$percent = null === $raw ? null : CAQC_Product::to_percent( $raw );

		if ( $bulk ) {
			if ( 'reset' === $mode ) {
				$product->delete_meta_data( CAQC_Product::META_WASTE );
			} elseif ( 'set' === $mode && null !== $percent ) {
				$product->update_meta_data( CAQC_Product::META_WASTE, (string) $percent );
			}

			return;
		}

		if ( '' === $raw ) {
			$product->delete_meta_data( CAQC_Product::META_WASTE );
		} elseif ( null !== $percent ) {
			$product->update_meta_data( CAQC_Product::META_WASTE, (string) $percent );
		}
	}

	/**
	 * Fills quick edit with the values of the row.
	 *
	 * WordPress does not pass any data for this, so they are read from the
	 * column that is in the table anyway.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function quick_edit_script( $hook ): void {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'caqc-admin',
			CAQC_PLUGIN_URL . 'assets/js/caqc-admin.js',
			array( 'inline-edit-post' ),
			CAQC_VERSION,
			true
		);

		wp_enqueue_style(
			'caqc-admin',
			CAQC_PLUGIN_URL . 'assets/css/caqc-admin.css',
			array(),
			CAQC_VERSION
		);
	}

	/**
	 * Dropdown above the product list to filter by quantity type.
	 */
	public static function filter_dropdown(): void {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		// Read-only filter of the product list, changes nothing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['caqc_type'] ) ? sanitize_key( wp_unslash( $_GET['caqc_type'] ) ) : '';

		echo '<select name="caqc_type">';
		printf(
			'<option value="">%s</option>',
			esc_html__( 'All quantity types', 'flooring-calculator' )
		);

		foreach ( CAQC_Types::options( true ) as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $label )
			);
		}

		printf(
			'<option value="_missing"%s>%s</option>',
			selected( $current, '_missing', false ),
			esc_html__( 'Size per unit missing', 'flooring-calculator' )
		);

		echo '</select>';
	}

	/**
	 * Applies the filter.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function filter_query( $vars ) {
		global $typenow;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter, see filter_dropdown().
		if ( 'product' !== $typenow || empty( $_GET['caqc_type'] ) ) {
			return $vars;
		}

		$filter_value = sanitize_key( wp_unslash( $_GET['caqc_type'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '_missing' === $filter_value ) {
			// Products whose type needs a size but has none.
			$needs_size = array();

			foreach ( CAQC_Types::all() as $key => $config ) {
				if ( ! empty( $config['needs_size'] ) ) {
					$needs_size[] = $key;
				}
			}

			$vars['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => CAQC_Product::META_TYPE,
					'value'   => $needs_size,
					'compare' => 'IN',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => CAQC_Product::META_SIZE,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => CAQC_Product::META_SIZE,
						'value' => '',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => CAQC_Product::META_USE_VARIATION,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => CAQC_Product::META_USE_VARIATION,
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			);

			return $vars;
		}

		if ( CAQC_Types::exists( $filter_value ) ) {
			$vars['meta_query'] = array(
				array(
					'key'   => CAQC_Product::META_TYPE,
					'value' => $filter_value,
				),
			);
		}

		return $vars;
	}
}
