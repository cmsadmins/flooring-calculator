/**
 * Self-test of the calculation functions, plain assert. Run: node tests/caqc-math.test.js
 */
'use strict';

var assert = require( 'assert' );
var m      = require( '../assets/js/caqc-math.js' );

var checks = 0;

function check( name, fn ) {
	fn();
	checks++;
	console.log( '  ok  ' + name );
}

console.log( 'caqc-math' );

check( 'Comma and dot are read the same', function () {
	assert.strictEqual( m.parseNumber( '12,5' ), 12.5 );
	assert.strictEqual( m.parseNumber( '12.5' ), 12.5 );
	assert.strictEqual( m.parseNumber( ' 12,5 ' ), 12.5 );
	assert.ok( isNaN( m.parseNumber( '' ) ) );
	assert.ok( isNaN( m.parseNumber( 'abc' ) ) );
	assert.ok( isNaN( m.parseNumber( null ) ) );
} );

check( '50 m² at 0.36 m² per pack yields 139 packs', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0.36, false ), 139 );
} );

check( '50 m² with waste yields 146 packs', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0.36, true ), 146 );
} );

check( 'Waste adds 5 percent, not 5.26', function () {
	// Dividing by 0.95 would give 5.26 % and 106.
	assert.strictEqual( m.quantityFromAmount( 100, 1, true ), 105 );
} );

check( 'Waste follows the configured percentage', function () {
	// Defaults to five percent.
	assert.strictEqual( m.quantityFromAmount( 100, 1, true ), 105 );
	assert.strictEqual( m.quantityFromAmount( 100, 1, true, 5 ), 105 );
	assert.strictEqual( m.quantityFromAmount( 100, 1, true, 10 ), 110 );
	assert.strictEqual( m.quantityFromAmount( 50, 0.46, true, 7.5 ), 117 );
	assert.strictEqual( m.quantityFromAmount( 100, 1, true, 0 ), 100 );
	// Nonsensical values fall back to five percent.
	assert.strictEqual( m.quantityFromAmount( 100, 1, true, -3 ), 105 );
	assert.strictEqual( m.quantityFromAmount( 100, 1, true, NaN ), 105 );
	// Without the checkbox the rate does not matter.
	assert.strictEqual( m.quantityFromAmount( 100, 1, false, 50 ), 100 );
} );

check( 'Exact multiple does not round up one unit too many', function () {
	// 50.04 / 0.36 is slightly above 139 in floating point.
	assert.strictEqual( m.quantityFromAmount( 50.04, 0.36, false ), 139 );
	assert.strictEqual( m.quantityFromAmount( 3.6, 0.36, false ), 10 );
	assert.strictEqual( m.quantityFromAmount( 8.5, 8.5, false ), 1 );
} );

check( 'Quantity is never less than 1', function () {
	assert.strictEqual( m.quantityFromAmount( 0.01, 8.5, false ), 1 );
} );

check( 'Input of 0 or less yields no result', function () {
	assert.strictEqual( m.quantityFromAmount( 0, 0.36, false ), null );
	assert.strictEqual( m.quantityFromAmount( -5, 0.36, false ), null );
} );

check( 'Missing size does not cause a division by zero', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0, false ), null );
	assert.strictEqual( m.quantityFromAmount( 50, NaN, false ), null );
	assert.strictEqual( m.quantityFromAmount( 50, -1, false ), null );
	assert.strictEqual( m.amountFromQuantity( 10, 0 ), null );
} );

check( 'Quantity back to area', function () {
	assert.strictEqual( m.amountFromQuantity( 10, 0.36 ), 3.6 );
	assert.strictEqual( m.amountFromQuantity( 139, 0.36 ), 50.04 );
	assert.strictEqual( m.amountFromQuantity( 2, 10 ), 20 );
} );

check( 'Very large input stays a finite number', function () {
	var q = m.quantityFromAmount( 99999, 0.36, false );
	assert.strictEqual( q, 277775 );
	assert.ok( isFinite( q ) );
} );

check( 'Adhesive: 50 m² at 1 kg per m² and 10 kg packs yields 5', function () {
	assert.strictEqual( m.quantityFromGlue( 50, 1, 10 ), 5 );
	assert.strictEqual( m.glueWeight( 50, 1 ), 50 );
} );

check( 'Adhesive rounds up to whole packs', function () {
	assert.strictEqual( m.quantityFromGlue( 51, 1, 10 ), 6 );
	assert.strictEqual( m.quantityFromGlue( 12, 0.3, 5 ), 1 );
} );

check( 'Adhesive: packs back to area yields the same quantity again', function () {
	assert.strictEqual( m.areaFromGlue( 5, 1, 10 ), 50 );
	assert.strictEqual( m.areaFromGlue( 10, 0.29, 11 ), 379.31 );

	[ 1, 2, 7, 10, 33 ].forEach( function ( q ) {
		assert.strictEqual( m.quantityFromGlue( m.areaFromGlue( q, 0.29, 11 ), 0.29, 11 ), q );
	} );

	assert.strictEqual( m.areaFromGlue( 5, 0, 10 ), null );
} );

check( 'Adhesive without filled-in fields does not calculate', function () {
	assert.strictEqual( m.quantityFromGlue( 50, 0, 10 ), null );
	assert.strictEqual( m.quantityFromGlue( 50, 1, 0 ), null );
	assert.strictEqual( m.quantityFromGlue( 50, NaN, 10 ), null );
} );

check( 'Meters with waste round up to whole meters', function () {
	// 7 x 5 room: 24 m perimeter, 5 % waste = 25.2 m, ordered 26 m.
	assert.strictEqual( m.lengthWithWaste( 24, 5 ), 26 );
	assert.strictEqual( m.lengthWithWaste( 24, 0 ), 24 );
	// Exact results must not add a meter.
	assert.strictEqual( m.lengthWithWaste( 20, 5 ), 21 );
	assert.strictEqual( m.lengthWithWaste( 0, 5 ), null );
	assert.strictEqual( m.lengthWithWaste( 24, -1 ), null );
} );

check( 'Room perimeter for skirting boards', function () {
	assert.strictEqual( m.perimeter( 5, 4 ), 18 );
	assert.strictEqual( m.perimeter( 3.5, 2.25 ), 11.5 );
	assert.strictEqual( m.perimeter( 0, 4 ), null );
} );

check( 'Room area for panels', function () {
	assert.strictEqual( m.roomArea( 5, 4 ), 20 );
	assert.strictEqual( m.roomArea( 2.6, 0.625 ), 1.63 );
	assert.strictEqual( m.roomArea( 5, 0 ), null );
} );

check( 'Waste round trip', function () {
	assert.strictEqual( m.amountWithWaste( 50, 10 ), 55 );
	assert.strictEqual( m.amountWithWaste( 50, 5 ), 52.5 );
	assert.strictEqual( m.amountWithoutWaste( 55, 10 ), 50 );
	assert.strictEqual( m.amountWithoutWaste( 55.2, 10 ), 50.18 );
	assert.strictEqual( m.amountWithWaste( 0, 10 ), null );
	assert.strictEqual( m.amountWithWaste( 50, -1 ), null );
} );

check( 'Total price', function () {
	assert.strictEqual( m.total( 10, 29.99 ), 299.9 );
	assert.strictEqual( m.total( 2, 39.9 ), 79.8 );
	assert.strictEqual( m.total( 0, 29.99 ), null );
	assert.strictEqual( m.total( 3, 0 ), 0 );
} );

check( 'Price format follows the shop settings', function () {
	var de = { decimals: 2, decimalSeparator: ',', thousandSeparator: '.', symbol: '&euro;', format: '%2$s&nbsp;%1$s' };
	assert.strictEqual( m.formatNumber( 1234.5, de ), '1.234,50' );
	assert.strictEqual( m.formatNumber( 299.9, de ), '299,90' );
	assert.strictEqual( m.formatPrice( 299.9, de ), '299,90&nbsp;&euro;' );
} );

console.log( '\n' + checks + ' checks passed.' );
