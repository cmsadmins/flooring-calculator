<?php
/**
 * Typtabelle: die einzige Stelle, an der eine Warengruppe entsteht.
 *
 * Jeder Typ beschreibt, welche Eingabefelder die Produktseite zeigt, wie die
 * Einheit heisst und ob Verschnitt und Raumrechner angeboten werden.
 *
 * @package FlooringCalculator
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
				'label'         => __( 'Square meters / packs', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many packs do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'pack', 'flooring-calculator' ),
				'unit_many'     => __( 'packs', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;pack', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => true,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'qmsr' => array(
				'label'         => __( 'Square meters / rolls', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many rolls do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'roll', 'flooring-calculator' ),
				'unit_many'     => __( 'rolls', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;roll', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => true,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'qmst' => array(
				'label'         => __( 'Square meters / panels', 'flooring-calculator' ),
				'input'         => self::INPUT_AREA,
				'input_label'   => __( 'What area do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many panels do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'panel', 'flooring-calculator' ),
				'unit_many'     => __( 'panels', 'flooring-calculator' ),
				'size_unit'     => __( 'm²', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;panel', 'flooring-calculator' ),
				'stock_in_size' => true,
				'cart_label'    => __( 'Area', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => true,
				'needs_size'    => true,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate panels needed', 'flooring-calculator' ),
			),
			'ms'   => array(
				'label'         => __( 'Meters / pieces', 'flooring-calculator' ),
				'input'         => self::INPUT_METER,
				'input_label'   => __( 'How many meters do you need?', 'flooring-calculator' ),
				'input_unit'    => __( 'm', 'flooring-calculator' ),
				'qty_label'     => __( 'How many pieces do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'piece', 'flooring-calculator' ),
				'unit_many'     => __( 'pieces', 'flooring-calculator' ),
				'size_unit'     => __( 'm', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;piece', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_PIECE,
				'cart_note'     => true,
				'waste'         => false,
				'needs_size'    => true,
				'room'          => self::ROOM_PERIMETER,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'm'    => array(
				'label'         => __( 'Meters', 'flooring-calculator' ),
				'input'         => self::INPUT_NONE,
				'input_label'   => '',
				'input_unit'    => '',
				'qty_label'     => __( 'How many meters do you need?', 'flooring-calculator' ),
				'qty_unit'      => __( 'm', 'flooring-calculator' ),
				'unit_one'      => __( 'meter', 'flooring-calculator' ),
				'unit_many'     => __( 'meters', 'flooring-calculator' ),
				'size_unit'     => __( 'm', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;meter', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_LENGTH,
				'cart_note'     => false,
				'waste'         => false,
				'needs_size'    => false,
				'room'          => self::ROOM_PERIMETER,
				'room_label'    => __( 'Calculate from room dimensions', 'flooring-calculator' ),
			),
			'kg'   => array(
				'label'         => __( 'Coverage / container', 'flooring-calculator' ),
				'input'         => self::INPUT_GLUE,
				'input_label'   => __( 'Area to be glued?', 'flooring-calculator' ),
				'input_unit'    => __( 'm²', 'flooring-calculator' ),
				'qty_label'     => __( 'How many containers do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'container', 'flooring-calculator' ),
				'unit_many'     => __( 'containers', 'flooring-calculator' ),
				'size_unit'     => __( 'kg', 'flooring-calculator' ),
				'price_suffix'  => __( '/&nbsp;container', 'flooring-calculator' ),
				'stock_in_size' => false,
				'cart_label'    => __( 'Weight', 'flooring-calculator' ),
				'hint'          => self::HINT_NONE,
				'cart_note'     => true,
				'waste'         => false,
				'needs_size'    => false,
				'room'          => self::ROOM_AREA,
				'room_label'    => __( 'Calculate area from room dimensions', 'flooring-calculator' ),
			),
			'stk'  => array(
				'label'         => __( 'Pieces (default)', 'flooring-calculator' ),
				'input'         => self::INPUT_NONE,
				'input_label'   => '',
				'input_unit'    => '',
				'qty_label'     => __( 'How many pieces do you need?', 'flooring-calculator' ),
				'unit_one'      => __( 'piece', 'flooring-calculator' ),
				'unit_many'     => __( 'pieces', 'flooring-calculator' ),
				'size_unit'     => '',
				'price_suffix'  => '',
				'stock_in_size' => false,
				'cart_label'    => __( 'Quantity', 'flooring-calculator' ),
				'hint'          => self::HINT_NONE,
				'cart_note'     => false,
				'waste'         => false,
				'needs_size'    => false,
				'room'          => self::ROOM_NONE,
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
