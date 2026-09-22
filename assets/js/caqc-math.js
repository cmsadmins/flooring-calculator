/**
 * Reine Rechenfunktionen des Mengenrechners.
 *
 * Keine DOM-Zugriffe, kein jQuery. Dadurch laeuft die Datei auch unter node
 * und der Selbsttest in tests/caqc-math.test.js kann sie ohne Browser pruefen.
 */
( function ( root ) {
	'use strict';

	// Toleranz gegen Gleitkomma-Rundung. Ohne sie ergibt 50,04 / 0,36 den Wert
	// 139.00000000000003 und Math.ceil macht daraus 140 statt 139.
	var EPS = 1e-9;

	/**
	 * Liest eine Zahl aus Benutzereingabe. Komma und Punkt gelten beide als
	 * Dezimaltrennzeichen, Leerzeichen werden verworfen.
	 *
	 * @param {string|number} value Eingabe.
	 * @return {number} Zahl, oder NaN wenn nicht lesbar.
	 */
	function parseNumber( value ) {
		if ( typeof value === 'number' ) {
			return isFinite( value ) ? value : NaN;
		}

		if ( typeof value !== 'string' ) {
			return NaN;
		}

		var cleaned = value.replace( /\s/g, '' ).replace( ',', '.' );

		if ( '' === cleaned ) {
			return NaN;
		}

		var parsed = parseFloat( cleaned );

		return isFinite( parsed ) ? parsed : NaN;
	}

	/**
	 * Aufrunden mit Toleranz gegen Gleitkomma-Artefakte.
	 *
	 * @param {number} value Wert.
	 * @return {number} Aufgerundeter Wert.
	 */
	function ceil( value ) {
		return Math.ceil( value - EPS );
	}

	/**
	 * Rundet auf zwei Nachkommastellen.
	 *
	 * @param {number} value Wert.
	 * @return {number} Gerundeter Wert.
	 */
	function round2( value ) {
		return Math.round( ( value + Number.EPSILON ) * 100 ) / 100;
	}

	/**
	 * Prueft, ob ein Wert als Groessenangabe taugt.
	 *
	 * @param {number} value Wert.
	 * @return {boolean} true wenn positiv und endlich.
	 */
	function isUsableSize( value ) {
		return 'number' === typeof value && isFinite( value ) && value > 0;
	}

	/**
	 * Menge aus einer Flaechen- oder Laengenangabe.
	 *
	 * @param {number} amount       Flaeche in m² oder Laenge in m.
	 * @param {number} sizePerPiece Groesse, die in einem Stueck steckt.
	 * @param {boolean} addWaste    Verschnitt aufschlagen.
	 * @param {number} percent      Verschnitt in Prozent, Vorgabe 5.
	 * @return {number|null} Ganze Menge ab 1, oder null wenn nicht berechenbar.
	 */
	function quantityFromAmount( amount, sizePerPiece, addWaste, percent ) {
		if ( ! isUsableSize( sizePerPiece ) || ! isFinite( amount ) || amount <= 0 ) {
			return null;
		}

		var zuschlag = 'number' === typeof percent && isFinite( percent ) && percent >= 0 ? percent : 5;
		var needed   = addWaste ? amount * ( 1 + zuschlag / 100 ) : amount;

		return Math.max( 1, ceil( needed / sizePerPiece ) );
	}

	/**
	 * Flaeche oder Laenge, die eine Menge abdeckt.
	 *
	 * @param {number} quantity     Menge in Stueck.
	 * @param {number} sizePerPiece Groesse je Stueck.
	 * @return {number|null} Wert auf zwei Nachkommastellen, oder null.
	 */
	function amountFromQuantity( quantity, sizePerPiece ) {
		if ( ! isUsableSize( sizePerPiece ) || ! isFinite( quantity ) || quantity <= 0 ) {
			return null;
		}

		return round2( quantity * sizePerPiece );
	}

	/**
	 * Gebinde aus zu verklebender Flaeche.
	 *
	 * @param {number} area        Flaeche in m².
	 * @param {number} consumption Verbrauch in kg je m².
	 * @param {number} packSize    Inhalt eines Gebindes in kg.
	 * @return {number|null} Ganze Gebinde ab 1, oder null.
	 */
	function quantityFromGlue( area, consumption, packSize ) {
		if ( ! isUsableSize( consumption ) || ! isUsableSize( packSize ) ) {
			return null;
		}

		if ( ! isFinite( area ) || area <= 0 ) {
			return null;
		}

		return Math.max( 1, ceil( ( area * consumption ) / packSize ) );
	}

	/**
	 * Benoetigte Kilogramm fuer eine Flaeche, als Zwischenanzeige.
	 *
	 * @param {number} area        Flaeche in m².
	 * @param {number} consumption Verbrauch in kg je m².
	 * @return {number|null} Kilogramm auf zwei Nachkommastellen, oder null.
	 */
	function glueWeight( area, consumption ) {
		if ( ! isUsableSize( consumption ) || ! isFinite( area ) || area <= 0 ) {
			return null;
		}

		return round2( area * consumption );
	}

	/**
	 * Raumumfang fuer Sockelleisten.
	 *
	 * @param {number} length Raumlaenge in m.
	 * @param {number} width  Raumbreite in m.
	 * @return {number|null} Umfang auf zwei Nachkommastellen, oder null.
	 */
	function perimeter( length, width ) {
		if ( ! isUsableSize( length ) || ! isUsableSize( width ) ) {
			return null;
		}

		return round2( 2 * ( length + width ) );
	}

	/**
	 * Raumflaeche.
	 *
	 * @param {number} length Raumlaenge in m.
	 * @param {number} width  Raumbreite in m.
	 * @return {number|null} Flaeche auf zwei Nachkommastellen, oder null.
	 */
	function roomArea( length, width ) {
		if ( ! isUsableSize( length ) || ! isUsableSize( width ) ) {
			return null;
		}

		return round2( length * width );
	}

	/**
	 * Gesamtpreis.
	 *
	 * @param {number} quantity  Menge.
	 * @param {number} unitPrice Einzelpreis.
	 * @return {number|null} Preis auf zwei Nachkommastellen, oder null.
	 */
	function total( quantity, unitPrice ) {
		if ( ! isFinite( quantity ) || ! isFinite( unitPrice ) || quantity <= 0 || unitPrice < 0 ) {
			return null;
		}

		return round2( quantity * unitPrice );
	}

	/**
	 * Formatiert eine Zahl nach den Einstellungen des Shops.
	 *
	 * @param {number} value    Wert.
	 * @param {object} settings decimals, decimalSeparator, thousandSeparator.
	 * @return {string} Formatierte Zahl.
	 */
	function formatNumber( value, settings ) {
		var config   = settings || {};
		var decimals = 'number' === typeof config.decimals ? config.decimals : 2;
		var decSep   = config.decimalSeparator || ',';
		var thouSep  = 'string' === typeof config.thousandSeparator ? config.thousandSeparator : '.';

		if ( ! isFinite( value ) ) {
			return '';
		}

		var parts = Math.abs( value ).toFixed( decimals ).split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thouSep );

		return ( value < 0 ? '-' : '' ) + parts.join( decSep );
	}

	/**
	 * Setzt den Preis in das Muster von WooCommerce ein.
	 *
	 * @param {number} value    Betrag.
	 * @param {object} settings Wie formatNumber, zusaetzlich symbol und format.
	 * @return {string} Formatierter Preis.
	 */
	function formatPrice( value, settings ) {
		var config = settings || {};
		var format = config.format || '%1$s%2$s';

		return format
			.replace( '%1$s', config.symbol || '' )
			.replace( '%2$s', formatNumber( value, config ) );
	}

	var api = {
		parseNumber: parseNumber,
		quantityFromAmount: quantityFromAmount,
		amountFromQuantity: amountFromQuantity,
		quantityFromGlue: quantityFromGlue,
		glueWeight: glueWeight,
		perimeter: perimeter,
		roomArea: roomArea,
		total: total,
		formatNumber: formatNumber,
		formatPrice: formatPrice,
	};

	if ( typeof module === 'object' && module.exports ) {
		module.exports = api;
	} else {
		root.CAQCMath = api;
	}
}( typeof window !== 'undefined' ? window : this ) );
