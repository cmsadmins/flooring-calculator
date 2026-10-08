<?php
/**
 * Einstellungen unter WooCommerce, Produkte, Mengenrechner.
 *
 * Ohne diese Seite muesste jeder Shop den Verschnitt und die Auswahl der
 * Warengruppen im Code setzen. Das ist fuer eine Agentur machbar, fuer den
 * Shopbetreiber nicht.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Einstellungsseite.
 */
final class CAQC_Settings {

	const SECTION = 'caqc';

	const OPTION_WASTE       = 'caqc_waste_percent';
	const OPTION_TYPES       = 'caqc_enabled_types';
	const OPTION_SHOW_TOTAL  = 'caqc_show_total';
	const OPTION_STOCK       = 'caqc_stock_in_size';
	const OPTION_LENGTH_HINT = 'caqc_length_hint';

	/**
	 * Haengt die Seite in die WooCommerce-Einstellungen.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'settings' ), 10, 2 );

		// Ein eigener Abschnitt bekommt seinen eigenen Speicherlauf. Ohne ihn
		// zeigt die Seite die Felder zwar an, die Eingaben landen aber nirgends.
		add_action( 'woocommerce_update_options_products_' . self::SECTION, array( __CLASS__, 'save' ) );

		add_filter( 'plugin_action_links_' . CAQC_PLUGIN_BASENAME, array( __CLASS__, 'action_link' ) );
	}

	/**
	 * Traegt den Abschnitt ein.
	 *
	 * @param array $sections Vorhandene Abschnitte.
	 * @return array
	 */
	public static function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Flooring calculator', 'flooring-calculator' );

		return $sections;
	}

	/**
	 * Verknüpfung in der Plugin-Liste.
	 *
	 * @param array $links Vorhandene Verknüpfungen.
	 * @return array
	 */
	public static function action_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=products&section=' . self::SECTION );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'flooring-calculator' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Die Felder des Abschnitts.
	 *
	 * @param array  $settings Bisherige Felder.
	 * @param string $section  Aktueller Abschnitt.
	 * @return array
	 */
	public static function settings( $settings, $section ) {
		if ( self::SECTION !== $section ) {
			return $settings;
		}

		$felder = array(
			array(
				'title' => __( 'Flooring calculator', 'flooring-calculator' ),
				'type'  => 'title',
				'desc'  => self::intro(),
				'id'    => 'caqc_options',
			),
			array(
				'title'             => __( 'Waste', 'flooring-calculator' ),
				'desc'              => __( 'Percentage the customer can add with a checkbox.', 'flooring-calculator' ),
				'id'                => self::OPTION_WASTE,
				'type'              => 'number',
				'default'           => '5',
				'custom_attributes' => array(
					'min'  => '0',
					'max'  => '100',
					'step' => '0.5',
				),
				'css'               => 'width: 80px;',
				'desc_tip'          => __( 'Applies to all quantity types that support waste.', 'flooring-calculator' ),
			),
			array(
				'title'    => __( 'Show total price', 'flooring-calculator' ),
				'desc'     => __( 'Show a running total below the calculator', 'flooring-calculator' ),
				'id'       => self::OPTION_SHOW_TOTAL,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( 'Turn off if your theme already shows a running total.', 'flooring-calculator' ),
			),
			array(
				'title'    => __( 'Convert stock display', 'flooring-calculator' ),
				'desc'     => __( 'Show stock in the product\'s measure instead of units', 'flooring-calculator' ),
				'id'       => self::OPTION_STOCK,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( '"534 in stock" becomes "534.06 m² in stock". Only affects area based quantity types.', 'flooring-calculator' ),
			),
			array(
				'title'       => __( 'Note for goods sold by the meter', 'flooring-calculator' ),
				'desc'        => __( 'Fixed text below the quantity field for the type Meters. Leave empty to show the product\'s piece length instead.', 'flooring-calculator' ),
				'id'          => self::OPTION_LENGTH_HINT,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'Sold by the meter. Length per piece approx. 2.40 to 2.70 meters.', 'flooring-calculator' ),
				'css'         => 'min-width: 340px;',
			),
			array(
				'title'   => __( 'Available quantity types', 'flooring-calculator' ),
				'desc'    => __( 'Only the selected types appear in the product dropdown. Values already set are kept.', 'flooring-calculator' ),
				'id'      => self::OPTION_TYPES,
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => CAQC_Types::options( true ),
				'default' => array_keys( CAQC_Types::all() ),
				'css'     => 'min-width: 340px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'caqc_options',
			),
		);

		return $felder;
	}

	/**
	 * Kurze Einführung über den Feldern, samt Hinweis auf eine mögliche
	 * Datenübernahme.
	 */
	private static function intro(): string {
		$text = '<p>' . esc_html__(
			'The calculator appears on a product page once a quantity type is selected and a size per unit is entered under Product data, General.',
			'flooring-calculator'
		) . '</p>';

		$offen = CAQC_Migrate::pending();

		if ( ! empty( $offen ) ) {
			$text .= '<p><strong>' . esc_html__( 'Data from a previous solution found.', 'flooring-calculator' ) . '</strong> ';
			$text .= esc_html(
				sprintf(
					/* translators: %d: number of meta keys. */
					_n(
						'%d field still uses an old name. The plugin takes it over on the next activation.',
						'%d fields still use old names. The plugin takes them over on the next activation.',
						count( $offen ),
						'flooring-calculator'
					),
					count( $offen )
				)
			) . '</p>';
		}

		return $text;
	}

	/**
	 * Speichert die Felder des eigenen Abschnitts.
	 */
	public static function save(): void {
		if ( ! class_exists( 'WC_Admin_Settings' ) ) {
			return;
		}

		WC_Admin_Settings::save_fields( self::settings( array(), self::SECTION ) );

		CAQC_Types::flush();
	}

	/**
	 * Verschnitt in Prozent.
	 */
	public static function waste_percent(): float {
		$percent = (float) get_option( self::OPTION_WASTE, 5 );

		if ( $percent < 0 || $percent > 100 ) {
			$percent = 5.0;
		}

		/**
		 * Erlaubt, den Verschnitt je Shop oder Warengruppe zu ändern.
		 *
		 * @param float $percent Prozentsatz.
		 */
		return (float) apply_filters( 'caqc_waste_percent', $percent );
	}

	/**
	 * Fester Hinweistext bei Meterware, leer wenn die Produktlänge gezeigt
	 * werden soll.
	 */
	public static function length_hint(): string {
		return trim( (string) get_option( self::OPTION_LENGTH_HINT, '' ) );
	}

	/**
	 * Soll der Gesamtpreis erscheinen?
	 */
	public static function show_total(): bool {
		return 'no' !== get_option( self::OPTION_SHOW_TOTAL, 'yes' );
	}

	/**
	 * Soll der Lagerbestand umgerechnet werden?
	 */
	public static function stock_in_size(): bool {
		return 'no' !== get_option( self::OPTION_STOCK, 'yes' );
	}

	/**
	 * Welche Mengenangaben stehen zur Auswahl?
	 *
	 * @return array<int, string> Leeres Feld bedeutet: alle.
	 */
	public static function enabled_types(): array {
		$werte = get_option( self::OPTION_TYPES, array() );

		return is_array( $werte ) ? array_filter( array_map( 'strval', $werte ) ) : array();
	}
}
