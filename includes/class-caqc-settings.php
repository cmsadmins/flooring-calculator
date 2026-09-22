<?php
/**
 * Einstellungen unter WooCommerce, Produkte, Mengenrechner.
 *
 * Ohne diese Seite muesste jeder Shop den Verschnitt und die Auswahl der
 * Warengruppen im Code setzen. Das ist fuer eine Agentur machbar, fuer den
 * Shopbetreiber nicht.
 *
 * @package CMSQuantityCalculator
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
		$sections[ self::SECTION ] = __( 'Mengenrechner', 'cms-quantity-calculator' );

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
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Einstellungen', 'cms-quantity-calculator' ) . '</a>'
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
				'title' => __( 'Mengenrechner', 'cms-quantity-calculator' ),
				'type'  => 'title',
				'desc'  => self::intro(),
				'id'    => 'caqc_options',
			),
			array(
				'title'             => __( 'Verschnitt', 'cms-quantity-calculator' ),
				'desc'              => __( 'Prozent, die der Kunde per Haken aufschlagen kann.', 'cms-quantity-calculator' ),
				'id'                => self::OPTION_WASTE,
				'type'              => 'number',
				'default'           => '5',
				'custom_attributes' => array( 'min' => '0', 'max' => '100', 'step' => '0.5' ),
				'css'               => 'width: 80px;',
				'desc_tip'          => __( 'Gilt für alle Warengruppen, bei denen Verschnitt vorgesehen ist.', 'cms-quantity-calculator' ),
			),
			array(
				'title'    => __( 'Gesamtpreis anzeigen', 'cms-quantity-calculator' ),
				'desc'     => __( 'Preisfeld unter dem Rechner einblenden', 'cms-quantity-calculator' ),
				'id'       => self::OPTION_SHOW_TOTAL,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( 'Abschalten, wenn das Theme bereits einen laufenden Gesamtpreis zeigt.', 'cms-quantity-calculator' ),
			),
			array(
				'title'    => __( 'Lagerbestand umrechnen', 'cms-quantity-calculator' ),
				'desc'     => __( 'Bestand in der Größe statt in Stück anzeigen', 'cms-quantity-calculator' ),
				'id'       => self::OPTION_STOCK,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( 'Aus "534 vorrätig" wird "534,06 m² vorrätig". Betrifft nur Warengruppen mit Flächenangabe.', 'cms-quantity-calculator' ),
			),
			array(
				'title'    => __( 'Verfügbare Mengenangaben', 'cms-quantity-calculator' ),
				'desc'     => __( 'Nur die ausgewählten erscheinen im Produkt-Dropdown. Bereits gesetzte Werte bleiben erhalten.', 'cms-quantity-calculator' ),
				'id'       => self::OPTION_TYPES,
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'options'  => CAQC_Types::options( true ),
				'default'  => array_keys( CAQC_Types::all() ),
				'css'      => 'min-width: 340px;',
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
			'Der Rechner erscheint auf einer Produktseite, sobald im Produkt unter Produktdaten, Allgemein eine Mengenangabe gewählt und die Größe pro Stück eingetragen ist.',
			'cms-quantity-calculator'
		) . '</p>';

		$offen = CAQC_Migrate::pending();

		if ( ! empty( $offen ) ) {
			$text .= '<p><strong>' . esc_html__( 'Daten aus einer früheren Lösung gefunden.', 'cms-quantity-calculator' ) . '</strong> ';
			$text .= esc_html(
				sprintf(
					/* translators: %d: Anzahl der Felder. */
					_n(
						'%d Feld trägt noch einen alten Namen. Das Plugin übernimmt es beim nächsten Aktivieren.',
						'%d Felder tragen noch alte Namen. Das Plugin übernimmt sie beim nächsten Aktivieren.',
						count( $offen ),
						'cms-quantity-calculator'
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
