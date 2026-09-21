/**
 * Selbsttest der Rechenfunktionen. Aufruf: node tests/caqc-math.test.js
 *
 * Kein Framework, nur assert. Faellt der Test, bricht der Aufruf mit Exit-Code 1.
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

check( 'Komma und Punkt werden gleich gelesen', function () {
	assert.strictEqual( m.parseNumber( '12,5' ), 12.5 );
	assert.strictEqual( m.parseNumber( '12.5' ), 12.5 );
	assert.strictEqual( m.parseNumber( ' 12,5 ' ), 12.5 );
	assert.ok( isNaN( m.parseNumber( '' ) ) );
	assert.ok( isNaN( m.parseNumber( 'abc' ) ) );
	assert.ok( isNaN( m.parseNumber( null ) ) );
} );

check( '50 m² bei 0,36 m² je Paket ergibt 139 Pakete', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0.36, false ), 139 );
} );

check( '50 m² mit Verschnitt ergibt 146 Pakete', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0.36, true ), 146 );
} );

check( 'Verschnitt schlaegt 5 Prozent auf, nicht 5,26', function () {
	// Der alte Code rechnete menge / 0.95 und kam damit auf 5,26 Prozent.
	// 100 / 1 mit Verschnitt muss 105 ergeben, nicht 106.
	assert.strictEqual( m.quantityFromAmount( 100, 1, true ), 105 );
} );

check( 'exaktes Vielfaches rundet nicht eine Einheit zu hoch', function () {
	// 139 * 0,36 = 50.040000000000006 in Gleitkomma. Ohne Toleranz waeren das 140.
	assert.strictEqual( m.quantityFromAmount( 50.04, 0.36, false ), 139 );
	assert.strictEqual( m.quantityFromAmount( 3.6, 0.36, false ), 10 );
	assert.strictEqual( m.quantityFromAmount( 8.5, 8.5, false ), 1 );
} );

check( 'Menge ist nie kleiner als 1', function () {
	assert.strictEqual( m.quantityFromAmount( 0.01, 8.5, false ), 1 );
} );

check( 'Eingabe 0 oder negativ ergibt kein Ergebnis', function () {
	assert.strictEqual( m.quantityFromAmount( 0, 0.36, false ), null );
	assert.strictEqual( m.quantityFromAmount( -5, 0.36, false ), null );
} );

check( 'fehlende Groesse fuehrt nicht zur Division durch Null', function () {
	assert.strictEqual( m.quantityFromAmount( 50, 0, false ), null );
	assert.strictEqual( m.quantityFromAmount( 50, NaN, false ), null );
	assert.strictEqual( m.quantityFromAmount( 50, -1, false ), null );
	assert.strictEqual( m.amountFromQuantity( 10, 0 ), null );
} );

check( 'Menge zurueck in Flaeche', function () {
	assert.strictEqual( m.amountFromQuantity( 10, 0.36 ), 3.6 );
	assert.strictEqual( m.amountFromQuantity( 139, 0.36 ), 50.04 );
	assert.strictEqual( m.amountFromQuantity( 2, 10 ), 20 );
} );

check( 'sehr grosse Eingabe bleibt eine endliche Zahl', function () {
	var q = m.quantityFromAmount( 99999, 0.36, false );
	assert.strictEqual( q, 277775 );
	assert.ok( isFinite( q ) );
} );

check( 'Klebstoff: 50 m² bei 1 kg je m² und 10 kg Gebinde ergibt 5', function () {
	assert.strictEqual( m.quantityFromGlue( 50, 1, 10 ), 5 );
	assert.strictEqual( m.glueWeight( 50, 1 ), 50 );
} );

check( 'Klebstoff rundet auf ganze Gebinde auf', function () {
	assert.strictEqual( m.quantityFromGlue( 51, 1, 10 ), 6 );
	assert.strictEqual( m.quantityFromGlue( 12, 0.3, 5 ), 1 );
} );

check( 'Klebstoff ohne gepflegte Felder rechnet nicht', function () {
	assert.strictEqual( m.quantityFromGlue( 50, 0, 10 ), null );
	assert.strictEqual( m.quantityFromGlue( 50, 1, 0 ), null );
	assert.strictEqual( m.quantityFromGlue( 50, NaN, 10 ), null );
} );

check( 'Raumumfang fuer Sockelleisten', function () {
	assert.strictEqual( m.perimeter( 5, 4 ), 18 );
	assert.strictEqual( m.perimeter( 3.5, 2.25 ), 11.5 );
	assert.strictEqual( m.perimeter( 0, 4 ), null );
} );

check( 'Raumflaeche fuer Paneele', function () {
	assert.strictEqual( m.roomArea( 5, 4 ), 20 );
	assert.strictEqual( m.roomArea( 2.6, 0.625 ), 1.63 );
	assert.strictEqual( m.roomArea( 5, 0 ), null );
} );

check( 'Gesamtpreis', function () {
	assert.strictEqual( m.total( 10, 29.99 ), 299.9 );
	assert.strictEqual( m.total( 2, 39.9 ), 79.8 );
	assert.strictEqual( m.total( 0, 29.99 ), null );
	assert.strictEqual( m.total( 3, 0 ), 0 );
} );

check( 'Preisformat folgt den Shop-Einstellungen', function () {
	var de = { decimals: 2, decimalSeparator: ',', thousandSeparator: '.', symbol: '&euro;', format: '%2$s&nbsp;%1$s' };
	assert.strictEqual( m.formatNumber( 1234.5, de ), '1.234,50' );
	assert.strictEqual( m.formatNumber( 299.9, de ), '299,90' );
	assert.strictEqual( m.formatPrice( 299.9, de ), '299,90&nbsp;&euro;' );
} );

console.log( '\n' + checks + ' Pruefungen bestanden.' );
