<?php
/**
 * Typtabelle: die einzige Stelle, an der eine Warengruppe entsteht.
 *
 * Jeder Typ beschreibt, welche Eingabefelder die Produktseite zeigt, wie die
 * Einheit heisst und ob Verschnitt und Raumrechner angeboten werden.
 *
 * @package CMSQuantityCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registry der Mengentypen.
 */
final class CAQC_Types {

	/**
	 * Zwischenspeicher der Typtabelle.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $cache = null;

	/**
	 * Feldart des linken Eingabeblocks.
	 */
	const INPUT_NONE  = 'none';
	const INPUT_AREA  = 'area';   // Flaeche in m².
	const INPUT_METER = 'meter';  // Laufende Meter.
	const INPUT_GLUE  = 'glue';   // Zu verklebende Flaeche in m².

	/**
	 * Weitere Merkmale je Typ, die an mehreren Stellen gebraucht werden:
	 *
	 *   stock_in_size  Lagerbestand in der Groesse statt in Stueck anzeigen
	 *   cart_label     Beschriftung der Angabe im Block-Checkout
	 *   cart_note      Angabe im Warenkorb ueberhaupt zeigen
	 *   needs_size     ohne Groesse je Stueck kein Rechner
	 *   qty_unit       Einheit rechts neben dem Mengenfeld, wenn die Menge
	 *                  selbst ein Mass ist (Meterware)
	 *
	 * Art des Hinweises unter dem Mengenfeld.
	 */
	const HINT_PIECE  = 'piece';   // "1 Paket = 0,46 m²"
	const HINT_LENGTH = 'length';  // "Meterweise bestellbar, Leistenlänge ca. 2,70 m"
	const HINT_NONE   = 'none';

	/**
	 * Art des Raumrechners.
	 */
	const ROOM_NONE      = '';
	const ROOM_PERIMETER = 'perimeter'; // Umfang: 2 * (L + B).
	const ROOM_AREA      = 'area';      // Flaeche: L * B.

	/**
	 * Liefert die vollstaendige Typtabelle.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$types = array(
			'qms'  => array(
				'label'        => __( 'Quadratmeter / Pakete', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_AREA,
				'input_label'  => __( 'Welche Fläche benötigen Sie?', 'cms-quantity-calculator' ),
				'input_unit'   => __( 'm²', 'cms-quantity-calculator' ),
				'qty_label'    => __( 'Wie viele Pakete benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Paket', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Pakete', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'm²', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Paket', 'cms-quantity-calculator' ),
				'stock_in_size' => true,
				'cart_label'   => __( 'Fläche', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_PIECE,
				'cart_note'    => true,
				'waste'        => true,
				'needs_size'   => true,
				'room'         => self::ROOM_NONE,
			),
			'qmsr' => array(
				'label'        => __( 'Quadratmeter / Rollen', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_AREA,
				'input_label'  => __( 'Welche Fläche benötigen Sie?', 'cms-quantity-calculator' ),
				'input_unit'   => __( 'm²', 'cms-quantity-calculator' ),
				'qty_label'    => __( 'Wie viele Rollen benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Rolle', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Rollen', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'm²', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Rolle', 'cms-quantity-calculator' ),
				'stock_in_size' => true,
				'cart_label'   => __( 'Fläche', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_PIECE,
				'cart_note'    => true,
				'waste'        => true,
				'needs_size'   => true,
				'room'         => self::ROOM_NONE,
			),
			'qmst' => array(
				'label'        => __( 'Quadratmeter / Paneele', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_AREA,
				'input_label'  => __( 'Welche Fläche benötigen Sie?', 'cms-quantity-calculator' ),
				'input_unit'   => __( 'm²', 'cms-quantity-calculator' ),
				'qty_label'    => __( 'Wie viele Paneele benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Paneel', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Paneele', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'm²', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Paneel', 'cms-quantity-calculator' ),
				'stock_in_size' => true,
				'cart_label'   => __( 'Fläche', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_PIECE,
				'cart_note'    => true,
				'waste'        => true,
				'needs_size'   => true,
				'room'         => self::ROOM_AREA,
				'room_label'   => __( 'Paneelbedarf berechnen', 'cms-quantity-calculator' ),
			),
			'ms'   => array(
				'label'        => __( 'Meter / Stückzahl', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_METER,
				'input_label'  => __( 'Wie viel Meter benötigen Sie?', 'cms-quantity-calculator' ),
				'input_unit'   => __( 'm', 'cms-quantity-calculator' ),
				'qty_label'    => __( 'Wie viel Stück benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Stück', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Stück', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'm', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Stück', 'cms-quantity-calculator' ),
				'stock_in_size' => false,
				'cart_label'   => __( 'Menge', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_PIECE,
				'cart_note'    => true,
				'waste'        => false,
				'needs_size'   => true,
				'room'         => self::ROOM_PERIMETER,
				'room_label'   => __( 'Bedarf aus Raummaßen berechnen', 'cms-quantity-calculator' ),
			),
			'm'    => array(
				'label'        => __( 'Meter', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_NONE,
				'input_label'  => '',
				'input_unit'   => '',
				'qty_label'    => __( 'Wie viel Meter benötigen Sie?', 'cms-quantity-calculator' ),
				'qty_unit'     => __( 'm', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Meter', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Meter', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'm', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Meter', 'cms-quantity-calculator' ),
				'stock_in_size' => false,
				'cart_label'   => __( 'Menge', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_LENGTH,
				'cart_note'    => false,
				'waste'        => false,
				'needs_size'   => false,
				'room'         => self::ROOM_PERIMETER,
				'room_label'   => __( 'Bedarf aus Raummaßen berechnen', 'cms-quantity-calculator' ),
			),
			'kg'   => array(
				'label'        => __( 'Verbrauch / Gebinde', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_GLUE,
				'input_label'  => __( 'Zu verklebende Fläche?', 'cms-quantity-calculator' ),
				'input_unit'   => __( 'm²', 'cms-quantity-calculator' ),
				'qty_label'    => __( 'Wie viel Stück benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Gebinde', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Gebinde', 'cms-quantity-calculator' ),
				'size_unit'    => __( 'kg', 'cms-quantity-calculator' ),
				'price_suffix' => __( '/&nbsp;Gebinde', 'cms-quantity-calculator' ),
				'stock_in_size' => false,
				'cart_label'   => __( 'Menge', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_NONE,
				'cart_note'    => false,
				'waste'        => false,
				'needs_size'   => false,
				'room'         => self::ROOM_NONE,
			),
			'stk'  => array(
				'label'        => __( 'Stückzahl (Standard)', 'cms-quantity-calculator' ),
				'input'        => self::INPUT_NONE,
				'input_label'  => '',
				'input_unit'   => '',
				'qty_label'    => __( 'Wie viel Stück benötigen Sie?', 'cms-quantity-calculator' ),
				'unit_one'     => __( 'Stück', 'cms-quantity-calculator' ),
				'unit_many'    => __( 'Stück', 'cms-quantity-calculator' ),
				'size_unit'    => '',
				'price_suffix' => '',
				'stock_in_size' => false,
				'cart_label'   => __( 'Menge', 'cms-quantity-calculator' ),
				'hint'         => self::HINT_NONE,
				'cart_note'    => false,
				'waste'        => false,
				'needs_size'   => false,
				'room'         => self::ROOM_NONE,
			),
		);

		/**
		 * Erlaubt weiteren Shops, eigene Warengruppen zu ergaenzen.
		 *
		 * @param array<string, array<string, mixed>> $types Typtabelle.
		 */
		$types = apply_filters( 'caqc_types', $types );

		self::$cache = $types;

		return $types;
	}

	/**
	 * Verwirft den Zwischenspeicher, etwa nach einer Einstellungsaenderung.
	 */
	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Gibt die Definition eines Typs zurueck, oder die des Standardtyps.
	 *
	 * @param string $key Typschluessel.
	 * @return array<string, mixed>
	 */
	public static function get( string $key ): array {
		$types = self::all();

		return $types[ $key ] ?? $types[ self::default_key() ];
	}

	/**
	 * Prueft, ob ein Typschluessel bekannt ist.
	 *
	 * @param string $key Typschluessel.
	 */
	public static function exists( string $key ): bool {
		return isset( self::all()[ $key ] );
	}

	/**
	 * Schluessel des Standardtyps.
	 */
	public static function default_key(): string {
		return 'stk';
	}

	/**
	 * Auswahlliste fuer das Backend-Dropdown.
	 *
	 * Ein Shop braucht selten alle Warengruppen. Was unter den Einstellungen
	 * abgewaehlt ist, erscheint hier nicht mehr. Die Logik kennt die Typen
	 * weiterhin, damit bereits gepflegte Produkte nicht umkippen.
	 *
	 * @param bool $alle Auch abgewaehlte Typen aufnehmen.
	 * @return array<string, string>
	 */
	public static function options( bool $alle = false ): array {
		$erlaubt = $alle || ! class_exists( 'CAQC_Settings' )
			? array()
			: CAQC_Settings::enabled_types();

		$options = array();

		foreach ( self::all() as $key => $type ) {
			if ( ! empty( $erlaubt ) && ! in_array( $key, $erlaubt, true ) ) {
				continue;
			}

			$options[ $key ] = (string) $type['label'];
		}

		return $options;
	}
}
