<?php
/**
 * Komfort in der Produktliste: Spalte, Schnellbearbeitung, Massenbearbeitung.
 *
 * Ein Shop mit zweitausend Produkten laesst sich nicht Produkt fuer Produkt
 * einstellen. Ohne diese Klasse ist die Einfuehrung in einem neuen Shop
 * Tagesarbeit, mit ihr eine Sache von Minuten.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Werkzeuge in der Produktliste.
 */
final class CAQC_Admin {

	const NONCE = 'caqc_bulk';

	/**
	 * Haengt alles in die Produktliste.
	 */
	public static function init(): void {
		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column_content' ), 20, 2 );

		add_action( 'woocommerce_product_quick_edit_end', array( __CLASS__, 'quick_edit_fields' ) );
		add_action( 'woocommerce_product_bulk_edit_end', array( __CLASS__, 'bulk_edit_fields' ) );

		add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'save_quick_edit' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( __CLASS__, 'save_bulk_edit' ) );

		add_action( 'admin_footer-edit.php', array( __CLASS__, 'quick_edit_script' ) );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_dropdown' ) );
		add_filter( 'request', array( __CLASS__, 'filter_query' ) );
	}

	/**
	 * Spalte hinter dem Lagerbestand.
	 *
	 * @param array $columns Vorhandene Spalten.
	 * @return array
	 */
	public static function column( $columns ) {
		$neu = array();

		foreach ( $columns as $key => $label ) {
			$neu[ $key ] = $label;

			if ( 'is_in_stock' === $key ) {
				$neu['caqc'] = __( 'Mengenangabe', 'cms-quantity-calculator' );
			}
		}

		if ( ! isset( $neu['caqc'] ) ) {
			$neu['caqc'] = __( 'Mengenangabe', 'cms-quantity-calculator' );
		}

		return $neu;
	}

	/**
	 * Inhalt der Spalte: Typ und Groesse, oder was fehlt.
	 *
	 * @param string $column  Spaltenname.
	 * @param int    $post_id Produkt.
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

		echo '<span class="caqc-col-type" data-type="' . esc_attr( $type ) . '">'
			. esc_html( (string) $config['label'] ) . '</span>';

		if ( empty( $config['needs_size'] ) && 'kg' !== $type ) {
			return;
		}

		echo '<br>';

		if ( 'kg' === $type ) {
			$glue = CAQC_Product::glue( $product );

			if ( null === $glue ) {
				self::hinweis( __( 'Verbrauch oder Gebinde fehlt', 'cms-quantity-calculator' ) );

				return;
			}

			printf(
				'<small>%s</small>',
				esc_html(
					sprintf(
						/* translators: 1: Verbrauch, 2: Gebindeinhalt. */
						__( '%1$s kg/m², Gebinde %2$s kg', 'cms-quantity-calculator' ),
						wc_format_localized_decimal( (string) $glue['consumption'] ),
						wc_format_localized_decimal( (string) $glue['pack_size'] )
					)
				)
			);

			return;
		}

		if ( CAQC_Product::uses_variation_size( $product ) ) {
			printf( '<small>%s</small>', esc_html__( 'Größe je Variante', 'cms-quantity-calculator' ) );

			return;
		}

		$size = CAQC_Product::size_per_piece( $product );

		if ( null === $size ) {
			self::hinweis( __( 'Größe pro Stück fehlt', 'cms-quantity-calculator' ) );

			return;
		}

		printf(
			'<small>%s %s</small>',
			esc_html( wc_format_localized_decimal( (string) $size ) ),
			esc_html( (string) $config['size_unit'] )
		);
	}

	/**
	 * Ein Hinweis, der ins Auge faellt, ohne zu schreien.
	 *
	 * @param string $text Text.
	 */
	private static function hinweis( string $text ): void {
		printf( '<small class="caqc-col-missing">%s</small>', esc_html( $text ) );
	}

	/**
	 * Felder in der Schnellbearbeitung.
	 */
	public static function quick_edit_fields(): void {
		self::felder( 'quick' );
	}

	/**
	 * Felder in der Massenbearbeitung.
	 */
	public static function bulk_edit_fields(): void {
		self::felder( 'bulk' );
	}

	/**
	 * Gemeinsames Markup beider Bearbeitungsformen.
	 *
	 * @param string $modus quick oder bulk.
	 */
	private static function felder( string $modus ): void {
		$optionen = CAQC_Types::options();

		wp_nonce_field( self::NONCE, '_caqc_nonce', false );

		echo '<div class="inline-edit-group caqc-inline">';
		echo '<label class="alignleft"><span class="title">'
			. esc_html__( 'Mengenangabe', 'cms-quantity-calculator' ) . '</span>';
		echo '<span class="input-text-wrap"><select name="_caqc_type">';

		printf(
			'<option value="">%s</option>',
			'bulk' === $modus
				? esc_html__( '— unverändert —', 'cms-quantity-calculator' )
				: esc_html__( '— bitte wählen —', 'cms-quantity-calculator' )
		);

		foreach ( $optionen as $key => $label ) {
			printf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $label ) );
		}

		echo '</select></span></label>';

		echo '<label class="alignleft"><span class="title">'
			. esc_html__( 'Größe pro Stück', 'cms-quantity-calculator' ) . '</span>';
		printf(
			'<span class="input-text-wrap"><input type="text" name="_caqc_size" class="text" value="" placeholder="%s"></span>',
			'bulk' === $modus
				? esc_attr__( 'unverändert', 'cms-quantity-calculator' )
				: ''
		);
		echo '</label>';

		echo '</div>';
	}

	/**
	 * Schnellbearbeitung speichern.
	 *
	 * @param WC_Product $product Produkt.
	 */
	public static function save_quick_edit( $product ): void {
		self::speichern( $product, false );
	}

	/**
	 * Massenbearbeitung speichern.
	 *
	 * @param WC_Product $product Produkt.
	 */
	public static function save_bulk_edit( $product ): void {
		self::speichern( $product, true );
	}

	/**
	 * Uebernimmt die Werte, wenn welche gesetzt sind.
	 *
	 * Ein leeres Feld bedeutet in der Massenbearbeitung "unveraendert lassen".
	 * Dort ohne diese Regel zu speichern wuerde reihenweise Groessen loeschen.
	 *
	 * @param WC_Product $product Produkt.
	 * @param bool       $bulk    Massenbearbeitung.
	 */
	private static function speichern( $product, bool $bulk ): void {
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
			$roh = trim( (string) wp_unslash( $_REQUEST['_caqc_size'] ) );

			if ( '' !== $roh ) {
				$size = CAQC_Product::to_float( $roh );

				if ( null !== $size ) {
					$product->update_meta_data( CAQC_Product::META_SIZE, str_replace( ',', '.', $roh ) );
				}
			} elseif ( ! $bulk ) {
				// In der Schnellbearbeitung heisst leer tatsaechlich leer.
				$product->delete_meta_data( CAQC_Product::META_SIZE );
			}
		}

		$product->save();
	}

	/**
	 * Füllt die Schnellbearbeitung mit den Werten der Zeile.
	 *
	 * WordPress liefert dafür keine Daten mit, deshalb werden sie aus der
	 * Spalte gelesen, die ohnehin in der Tabelle steht.
	 */
	public static function quick_edit_script(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}
		?>
		<script>
		( function () {
			var inlineEdit = window.inlineEditPost && window.inlineEditPost.edit;

			if ( ! inlineEdit ) {
				return;
			}

			window.inlineEditPost.edit = function ( id ) {
				var ergebnis = inlineEdit.apply( this, arguments );
				var beitrag  = typeof id === 'object' ? this.getId( id ) : id;

				if ( ! beitrag ) {
					return ergebnis;
				}

				var zeile   = document.getElementById( 'post-' + beitrag );
				var formular = document.getElementById( 'edit-' + beitrag );

				if ( ! zeile || ! formular ) {
					return ergebnis;
				}

				var typ   = zeile.querySelector( '.caqc-col-type' );
				var feld  = formular.querySelector( 'select[name="_caqc_type"]' );

				if ( typ && feld ) {
					feld.value = typ.getAttribute( 'data-type' ) || '';
				}

				return ergebnis;
			};
		}() );
		</script>
		<style>
			.caqc-inline { clear: both; padding-top: 6px; }
			.caqc-inline label { margin-right: 14px; }
			.column-caqc { width: 12%; }
			.caqc-col-missing { color: #b32d2e; }
		</style>
		<?php
	}

	/**
	 * Auswahlfeld über der Produktliste, um nach Mengenangabe zu filtern.
	 */
	public static function filter_dropdown(): void {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		$aktuell = isset( $_GET['caqc_type'] ) ? sanitize_key( wp_unslash( $_GET['caqc_type'] ) ) : '';

		echo '<select name="caqc_type">';
		printf(
			'<option value="">%s</option>',
			esc_html__( 'Alle Mengenangaben', 'cms-quantity-calculator' )
		);

		foreach ( CAQC_Types::options( true ) as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $aktuell, $key, false ),
				esc_html( $label )
			);
		}

		printf(
			'<option value="_missing"%s>%s</option>',
			selected( $aktuell, '_missing', false ),
			esc_html__( 'Größe pro Stück fehlt', 'cms-quantity-calculator' )
		);

		echo '</select>';
	}

	/**
	 * Wertet den Filter aus.
	 *
	 * @param array $vars Abfrageparameter.
	 * @return array
	 */
	public static function filter_query( $vars ) {
		global $typenow;

		if ( 'product' !== $typenow || empty( $_GET['caqc_type'] ) ) {
			return $vars;
		}

		$wert = sanitize_key( wp_unslash( $_GET['caqc_type'] ) );

		if ( '_missing' === $wert ) {
			// Produkte, deren Typ eine Groesse braucht, aber keine hat.
			$braucht = array();

			foreach ( CAQC_Types::all() as $key => $config ) {
				if ( ! empty( $config['needs_size'] ) ) {
					$braucht[] = $key;
				}
			}

			$vars['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => CAQC_Product::META_TYPE,
					'value'   => $braucht,
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

		if ( CAQC_Types::exists( $wert ) ) {
			$vars['meta_query'] = array(
				array(
					'key'   => CAQC_Product::META_TYPE,
					'value' => $wert,
				),
			);
		}

		return $vars;
	}
}
