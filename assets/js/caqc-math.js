/**
 * Pure calculation functions, DOM-free so tests/caqc-math.test.js runs them under node.
 */
( function ( root ) {
	'use strict';

	// 50.04 / 0.36 yields 139.00000000000003, which Math.ceil would turn into 140.
	var EPS = 1e-9;

	/**
	 * Reads a number from user input, accepting comma or dot and ignoring spaces.
	 *
	 * @param {string|number} value Input.
	 * @return {number} Number, or NaN if unreadable.
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
	 * Rounds up with tolerance against floating-point artifacts.
	 *
	 * @param {number} value Value.
	 * @return {number} Rounded-up value.
	 */
	function ceil( value ) {
		return Math.ceil( value - EPS );
	}

	/**
	 * Rounds to two decimal places.
	 *
	 * @param {number} value Value.
	 * @return {number} Rounded value.
	 */
	function round2( value ) {
		return Math.round( ( value + Number.EPSILON ) * 100 ) / 100;
	}

	/**
	 * Checks whether a value is usable as a size.
	 *
	 * @param {number} value Value.
	 * @return {boolean} true if positive and finite.
	 */
	function isUsableSize( value ) {
		return 'number' === typeof value && isFinite( value ) && value > 0;
	}

	/**
	 * Quantity from an area or length.
	 *
	 * @param {number} amount       Area in m² or length in m.
	 * @param {number} sizePerPiece Size contained in one piece.
	 * @param {boolean} addWaste    Add waste.
	 * @param {number} percent      Waste in percent, default 5.
	 * @return {number|null} Whole quantity of at least 1, or null if not computable.
	 */
	function quantityFromAmount( amount, sizePerPiece, addWaste, percent ) {
		if ( ! isUsableSize( sizePerPiece ) || ! isFinite( amount ) || amount <= 0 ) {
			return null;
		}

		var surcharge = 'number' === typeof percent && isFinite( percent ) && percent >= 0 ? percent : 5;
		var needed    = addWaste ? amount * ( 1 + surcharge / 100 ) : amount;

		return Math.max( 1, ceil( needed / sizePerPiece ) );
	}

	/**
	 * Area including waste.
	 *
	 * @param {number} amount  Entered area.
	 * @param {number} percent Waste in percent.
	 * @return {number|null} Value to two decimal places, or null.
	 */
	function amountWithWaste( amount, percent ) {
		if ( ! isFinite( amount ) || amount <= 0 || ! isFinite( percent ) || percent < 0 ) {
			return null;
		}

		return round2( amount * ( 1 + percent / 100 ) );
	}

	/**
	 * Area without waste, the inverse of amountWithWaste.
	 *
	 * @param {number} amount  Area including waste.
	 * @param {number} percent Waste in percent.
	 * @return {number|null} Value to two decimal places, or null.
	 */
	function amountWithoutWaste( amount, percent ) {
		if ( ! isFinite( amount ) || amount <= 0 || ! isFinite( percent ) || percent < 0 ) {
			return null;
		}

		return round2( amount / ( 1 + percent / 100 ) );
	}

	/**
	 * Running meters including waste, rounded up to whole meters.
	 *
	 * @param {number} length  Length in m.
	 * @param {number} percent Waste in percent.
	 * @return {number|null} Whole meters, or null.
	 */
	function lengthWithWaste( length, percent ) {
		if ( ! isUsableSize( length ) || ! isFinite( percent ) || percent < 0 ) {
			return null;
		}

		return Math.max( 1, ceil( length * ( 1 + percent / 100 ) ) );
	}

	/**
	 * Area or length covered by a quantity.
	 *
	 * @param {number} quantity     Quantity in pieces.
	 * @param {number} sizePerPiece Size per piece.
	 * @return {number|null} Value to two decimal places, or null.
	 */
	function amountFromQuantity( quantity, sizePerPiece ) {
		if ( ! isUsableSize( sizePerPiece ) || ! isFinite( quantity ) || quantity <= 0 ) {
			return null;
		}

		return round2( quantity * sizePerPiece );
	}

	/**
	 * Packs needed for the area to be glued.
	 *
	 * @param {number} area        Area in m².
	 * @param {number} consumption Consumption in kg per m².
	 * @param {number} packSize    Content of one pack in kg.
	 * @return {number|null} Whole packs, at least 1, or null.
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
	 * Area glued by a number of packs, rounded down so it converts back to the same quantity.
	 *
	 * @param {number} quantity    Packs.
	 * @param {number} consumption Consumption in kg per m².
	 * @param {number} packSize    Content of one pack in kg.
	 * @return {number|null} Area to two decimal places, or null.
	 */
	function areaFromGlue( quantity, consumption, packSize ) {
		if ( ! isUsableSize( consumption ) || ! isUsableSize( packSize ) || ! isFinite( quantity ) || quantity <= 0 ) {
			return null;
		}

		return Math.floor( ( ( quantity * packSize ) / consumption ) * 100 + EPS ) / 100;
	}

	/**
	 * Kilograms needed for an area, shown as an intermediate value.
	 *
	 * @param {number} area        Area in m².
	 * @param {number} consumption Consumption in kg per m².
	 * @return {number|null} Kilograms to two decimal places, or null.
	 */
	function glueWeight( area, consumption ) {
		if ( ! isUsableSize( consumption ) || ! isFinite( area ) || area <= 0 ) {
			return null;
		}

		return round2( area * consumption );
	}

	/**
	 * Room perimeter for skirting boards.
	 *
	 * @param {number} length Room length in m.
	 * @param {number} width  Room width in m.
	 * @return {number|null} Perimeter to two decimal places, or null.
	 */
	function perimeter( length, width ) {
		if ( ! isUsableSize( length ) || ! isUsableSize( width ) ) {
			return null;
		}

		return round2( 2 * ( length + width ) );
	}

	/**
	 * Room area.
	 *
	 * @param {number} length Room length in m.
	 * @param {number} width  Room width in m.
	 * @return {number|null} Area to two decimal places, or null.
	 */
	function roomArea( length, width ) {
		if ( ! isUsableSize( length ) || ! isUsableSize( width ) ) {
			return null;
		}

		return round2( length * width );
	}

	/**
	 * Total price.
	 *
	 * @param {number} quantity  Quantity.
	 * @param {number} unitPrice Unit price.
	 * @return {number|null} Price to two decimal places, or null.
	 */
	function total( quantity, unitPrice ) {
		if ( ! isFinite( quantity ) || ! isFinite( unitPrice ) || quantity <= 0 || unitPrice < 0 ) {
			return null;
		}

		return round2( quantity * unitPrice );
	}

	/**
	 * Formats a number according to the shop settings.
	 *
	 * @param {number} value    Value.
	 * @param {object} settings decimals, decimalSeparator, thousandSeparator.
	 * @return {string} Formatted number.
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
	 * Inserts the price into the WooCommerce price pattern.
	 *
	 * @param {number} value    Amount.
	 * @param {object} settings Like formatNumber, plus symbol and format.
	 * @return {string} Formatted price.
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
		amountWithWaste: amountWithWaste,
		amountWithoutWaste: amountWithoutWaste,
		lengthWithWaste: lengthWithWaste,
		quantityFromGlue: quantityFromGlue,
		areaFromGlue: areaFromGlue,
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
