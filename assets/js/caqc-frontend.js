/**
 * Verdrahtung des Mengenrechners auf der Produktseite.
 *
 * Gerechnet wird in CAQCMath, hier stehen nur DOM-Zugriffe und Ereignisse.
 * jQuery wird allein fuer das Ereignis show_variation von WooCommerce
 * gebraucht, alles andere laeuft ueber die Standard-API des Browsers.
 */
( function ( $ ) {
	'use strict';

	var math = window.CAQCMath;

	if ( ! math ) {
		return;
	}

	/**
	 * Ein Rechner auf der Seite.
	 *
	 * @param {HTMLElement} root Wurzelelement mit data-caqc.
	 */
	function Calculator( root ) {
		this.root = root;

		try {
			this.data = JSON.parse( root.getAttribute( 'data-caqc' ) || '{}' );
		} catch ( e ) {
			this.data = {};
		}

		this.form      = root.closest( 'form.cart' ) || document;
		this.amountEl  = root.querySelector( '[data-caqc-amount]' );
		this.noteEl    = root.querySelector( '[data-caqc-note]' );
		this.hintEl    = root.querySelector( '[data-caqc-hint]' );
		this.wasteEl   = root.querySelector( '[data-caqc-waste]' );
		this.wasteOut  = root.querySelector( '[data-caqc-waste-result]' );
		this.roomEl    = root.querySelector( '[data-caqc-room]' );
		this.qtyEl     = this.findQuantityInput();
		this.totalEl   = this.findTotalValue();

		this.size  = this.toSize( this.data.sizePerPiece );
		this.price = 'number' === typeof this.data.price ? this.data.price : null;

		// Solange wir die Menge selbst setzen, ist die Gegenrichtung gesperrt.
		// Sonst schreibt das ausgelöste change die Fläche zurück, die der
		// Kunde gerade eintippt, und das Feld springt unter den Fingern weg.
		this.schreibtMenge = false;

		this.bind();
		this.renderHint();
		this.syncFromQuantity();
	}

	/**
	 * Das Mengenfeld liegt zwischen den Hooks und gehoert dem Theme.
	 *
	 * @return {HTMLElement|null} Eingabefeld.
	 */
	Calculator.prototype.findQuantityInput = function () {
		return this.root.querySelector( 'input.qty' )
			|| this.root.querySelector( 'input[name="quantity"]' );
	};

	/**
	 * Die Preisbox steht hinter dem Rechner, nicht darin.
	 *
	 * @return {HTMLElement|null} Anzeigeelement.
	 */
	Calculator.prototype.findTotalValue = function () {
		var footer = this.root.nextElementSibling;

		if ( footer && footer.classList.contains( 'caqc-footer' ) ) {
			return footer.querySelector( '[data-caqc-total-value]' );
		}

		return this.form.querySelector ? this.form.querySelector( '[data-caqc-total-value]' ) : null;
	};

	/**
	 * Nimmt nur positive, endliche Groessen an.
	 *
	 * @param {*} value Rohwert.
	 * @return {number|null} Groesse.
	 */
	Calculator.prototype.toSize = function ( value ) {
		var number = math.parseNumber( value );

		return isFinite( number ) && number > 0 ? number : null;
	};

	/**
	 * Haengt alle Ereignisse ein.
	 */
	Calculator.prototype.bind = function () {
		var self = this;

		if ( this.amountEl ) {
			this.amountEl.addEventListener( 'input', function () {
				self.syncFromAmount();
			} );

			this.amountEl.addEventListener( 'blur', function () {
				self.normalizeAmount();
			} );
		}

		Array.prototype.forEach.call( this.root.querySelectorAll( '[data-caqc-step]' ), function ( button ) {
			button.addEventListener( 'click', function () {
				self.stepAmount( parseInt( button.getAttribute( 'data-caqc-step' ), 10 ) );
			} );
		} );

		if ( this.qtyEl ) {
			// change deckt die Plus- und Minus-Schaltflaechen des Themes ab,
			// input die Tastatureingabe.
			this.qtyEl.addEventListener( 'change', function () {
				self.syncFromQuantity();
			} );

			this.qtyEl.addEventListener( 'input', function () {
				self.syncFromQuantity();
			} );

			// Themes lösen die Änderung meist über jQuery aus. Ein
			// jQuery-trigger erreicht die Zeilen oben nicht, weil es die
			// Handler nur in seiner eigenen Liste aufruft. Deshalb hier
			// zusätzlich über jQuery hören, sonst bleibt die Fläche stehen,
			// wenn jemand die Plus-Schaltfläche des Themes benutzt.
			if ( $ ) {
				$( this.qtyEl ).on( 'change.caqc input.caqc', function () {
					self.syncFromQuantity();
				} );
			}
		}

		if ( this.wasteEl ) {
			this.wasteEl.addEventListener( 'change', function () {
				self.syncFromAmount( true );
			} );
		}

		if ( this.roomEl ) {
			var apply = this.roomEl.querySelector( '[data-caqc-room-apply]' );

			if ( apply ) {
				apply.addEventListener( 'click', function () {
					self.applyRoom();
				} );
			}
		}

		if ( $ ) {
			$( this.form ).on( 'show_variation', function ( event, variation ) {
				self.onVariation( variation );
			} );

			$( this.form ).on( 'hide_variation reset_data', function () {
				self.onVariationCleared();
			} );
		}
	};

	/**
	 * Flaeche wurde bearbeitet: Menge nachziehen, Flaeche stehen lassen.
	 *
	 * Das Feld wird bewusst nicht auf ein Vielfaches der Packungsgroesse
	 * zurueckgeschrieben, sonst springt es waehrend des Tippens.
	 *
	 * @param {boolean} keepAmount Auch bei leerem Feld nichts tun.
	 */
	Calculator.prototype.syncFromAmount = function ( keepAmount ) {
		if ( ! this.amountEl || ! this.qtyEl ) {
			return;
		}

		var raw    = this.amountEl.value.trim();
		var amount = math.parseNumber( raw );

		// Leeres Feld: der Kunde tippt gerade. Die Menge bleibt stehen, sonst
		// springt sie bei jedem Löschen auf eins.
		if ( '' === raw ) {
			this.renderNote( '' );

			return;
		}

		// Keine lesbare Zahl: ebenfalls abwarten, das ist meist ein Tippfehler.
		if ( ! isFinite( amount ) ) {
			this.renderNote( '' );

			return;
		}

		// Null oder negativ ist eine Angabe, keine Zwischenstufe. Die Menge geht
		// auf das Minimum, damit Eingabe und Preis zusammenpassen.
		if ( amount <= 0 ) {
			this.setQuantity( this.minQuantity() );
			this.renderNote( '' );
			this.renderTotal();

			return;
		}

		var quantity = this.quantityFor( amount );

		if ( null === quantity ) {
			this.renderNote( this.data.i18n && this.data.i18n.chooseVariant ? this.data.i18n.chooseVariant : '' );

			return;
		}

		var gesetzt = this.setQuantity( quantity );

		// Der Lagerbestand kann kleiner sein als der Bedarf. Dann steht im Feld
		// eine Fläche, die so nicht lieferbar ist, und der Kunde sollte das
		// erfahren, statt sich über die Zahl daneben zu wundern.
		if ( gesetzt < quantity ) {
			this.renderNote( this.buildNote( 'capped', gesetzt ) );
		} else if ( gesetzt > quantity ) {
			// Eine Mindestabnahme hebt die Menge an. Ohne Hinweis wundert sich
			// der Kunde, warum neben 15 m² zwei Rollen stehen.
			this.renderNote( this.buildNote( 'minimum', gesetzt ) );
		} else {
			this.renderNote( '' );
		}

		this.renderHint();
		this.renderWaste();
		this.renderTotal();
	};

	/**
	 * Zeile neben dem Verschnitt-Haken: was aus der Eingabe mit Aufschlag wird.
	 * Die Eingabe selbst bleibt unangetastet, sonst weiss der Kunde nach dem
	 * zweiten Umschalten nicht mehr, was er eingetippt hat.
	 */
	Calculator.prototype.renderWaste = function () {
		if ( ! this.wasteOut ) {
			return;
		}

		var amount = this.amountEl ? math.parseNumber( this.amountEl.value ) : NaN;
		var gross  = this.wasteActive() ? math.amountWithWaste( amount, this.data.wastePercent ) : null;

		if ( null === gross || ! this.qtyEl || ! this.data.i18n ) {
			this.wasteOut.textContent = '';

			return;
		}

		var quantity  = math.parseNumber( this.qtyEl.value );
		var separator = this.data.priceFormat ? this.data.priceFormat.thousandSeparator : '.';

		this.wasteOut.textContent = this.data.i18n.wasteResult
			.replace( '%1$s', math.formatNumber( this.data.wastePercent, { decimals: 0, thousandSeparator: separator } ) )
			.replace( '%2$s', math.formatNumber( gross, this.data.priceFormat ) )
			.replace( '%3$s', this.data.inputUnit || '' )
			.replace( '%4$s', math.formatNumber( quantity, { decimals: 0, thousandSeparator: separator } ) )
			.replace( '%5$s', quantity > 1 ? this.data.unitMany : this.data.unitOne );
	};

	/**
	 * Baut einen Hinweis mit Menge und Einheit.
	 *
	 * @param {string} key      Schlüssel im Textvorrat.
	 * @param {number} quantity Menge.
	 * @return {string} Text, leer wenn der Schlüssel fehlt.
	 */
	Calculator.prototype.buildNote = function ( key, quantity ) {
		if ( ! this.data.i18n || ! this.data.i18n[ key ] ) {
			return '';
		}

		var separator = this.data.priceFormat ? this.data.priceFormat.thousandSeparator : '.';

		return this.data.i18n[ key ]
			.replace( '%1$s', math.formatNumber( quantity, { decimals: 0, thousandSeparator: separator } ) )
			.replace( '%2$s', quantity > 1 ? this.data.unitMany : this.data.unitOne );
	};

	/**
	 * Kleinste zulässige Menge des Mengenfelds.
	 *
	 * @return {number} Minimum, mindestens 1.
	 */
	Calculator.prototype.minQuantity = function () {
		var min = math.parseNumber( this.qtyEl.getAttribute( 'min' ) );

		return isFinite( min ) && min > 0 ? min : 1;
	};

	/**
	 * Rechnet eine Eingabe in eine Menge um, je nach Typ.
	 *
	 * @param {number} amount Flaeche oder Laenge.
	 * @return {number|null} Menge.
	 */
	Calculator.prototype.quantityFor = function ( amount ) {
		if ( 'glue' === this.data.input ) {
			if ( ! this.data.glue ) {
				return null;
			}

			return math.quantityFromGlue( amount, this.data.glue.consumption, this.data.glue.pack_size );
		}

		return math.quantityFromAmount( amount, this.size, this.wasteActive(), this.data.wastePercent );
	};

	/**
	 * Menge wurde bearbeitet: Flaeche nachziehen.
	 */
	Calculator.prototype.syncFromQuantity = function () {
		if ( ! this.qtyEl || this.schreibtMenge ) {
			return;
		}

		var quantity = math.parseNumber( this.qtyEl.value );

		if ( this.amountEl && isFinite( quantity ) && quantity > 0 && 'glue' !== this.data.input ) {
			var amount = math.amountFromQuantity( quantity, this.size );

			// Mit Verschnitt deckt die Menge die Flaeche samt Aufschlag ab. Im
			// Feld steht die Flaeche ohne Aufschlag, die Zeile am Haken zeigt
			// den Rest. Sonst widersprechen sich Feld und Zeile.
			if ( null !== amount && this.wasteActive() ) {
				amount = math.amountWithoutWaste( amount, this.data.wastePercent );
			}

			if ( null !== amount ) {
				this.amountEl.value = math.formatNumber( amount, this.data.priceFormat );
			}
		}

		this.renderHint();
		this.renderWaste();
		this.renderTotal();
	};

	/**
	 * Plus oder Minus am Flaechenfeld. Ein Schritt ist genau eine Einheit des
	 * Produkts, sonst aendert ein Klick die Menge um mehrere Pakete.
	 *
	 * @param {number} direction 1 oder -1.
	 */
	Calculator.prototype.stepAmount = function ( direction ) {
		if ( ! this.amountEl ) {
			return;
		}

		var step    = this.size || 1;
		var current = math.parseNumber( this.amountEl.value );

		if ( ! isFinite( current ) ) {
			current = 0;
		}

		var next = current + ( direction * step );

		if ( next < step ) {
			next = step;
		}

		this.amountEl.value = math.formatNumber( next, this.data.priceFormat );
		this.syncFromAmount();
	};

	/**
	 * Formatiert die Eingabe, sobald das Feld verlassen wird.
	 */
	Calculator.prototype.normalizeAmount = function () {
		if ( ! this.amountEl ) {
			return;
		}

		var amount = math.parseNumber( this.amountEl.value );

		if ( isFinite( amount ) && amount > 0 ) {
			this.amountEl.value = math.formatNumber( amount, this.data.priceFormat );
		}
	};

	/**
	 * Uebernimmt das Ergebnis des Raumrechners in das Hauptfeld.
	 */
	Calculator.prototype.applyRoom = function () {
		if ( ! this.roomEl ) {
			return;
		}

		var lengthEl = this.roomEl.querySelector( '[data-caqc-room-length]' );
		var widthEl  = this.roomEl.querySelector( '[data-caqc-room-width]' );
		var resultEl = this.roomEl.querySelector( '[data-caqc-room-result]' );
		var mode     = this.roomEl.getAttribute( 'data-caqc-room' );

		var length = math.parseNumber( lengthEl ? lengthEl.value : '' );
		var width  = math.parseNumber( widthEl ? widthEl.value : '' );
		var value  = 'perimeter' === mode ? math.perimeter( length, width ) : math.roomArea( length, width );

		if ( null === value ) {
			if ( resultEl ) {
				resultEl.textContent = '';
			}

			return;
		}

		if ( this.amountEl ) {
			this.amountEl.value = math.formatNumber( value, this.data.priceFormat );
			this.syncFromAmount();
		} else if ( this.qtyEl ) {
			// Typ Meter ohne eigenes Flaechenfeld: direkt in die Menge.
			this.setQuantity( Math.max( 1, Math.ceil( value ) ) );
			this.syncFromQuantity();
		}

		if ( resultEl && this.data.i18n ) {
			resultEl.textContent = this.data.i18n.roomResult
				.replace( '%1$s', math.formatNumber( value, this.data.priceFormat ) )
				.replace( '%2$s', this.data.inputUnit || this.data.sizeUnit || '' );
		}
	};

	/**
	 * Variante gewaehlt: Groesse und Preis uebernehmen.
	 *
	 * @param {object} variation Variantendaten von WooCommerce.
	 */
	Calculator.prototype.onVariation = function ( variation ) {
		if ( ! variation ) {
			return;
		}

		if ( this.data.useVariation && Array.isArray( this.data.variations ) ) {
			var match = null;

			this.data.variations.forEach( function ( entry ) {
				if ( entry.id === variation.variation_id ) {
					match = entry.size;
				}
			} );

			this.size = this.toSize( match );
		}

		if ( 'number' === typeof variation.display_price ) {
			this.price = variation.display_price;
		}

		this.root.classList.remove( 'caqc--no-variation' );
		this.renderNote( '' );
		this.renderHint();
		this.syncFromQuantity();
	};

	/**
	 * Variantenauswahl zurueckgesetzt.
	 */
	Calculator.prototype.onVariationCleared = function () {
		if ( ! this.data.useVariation ) {
			return;
		}

		this.size  = null;
		this.price = null;

		this.root.classList.add( 'caqc--no-variation' );
		this.renderHint();
		this.renderTotal();
	};

	/**
	 * Schreibt eine Menge in das Feld des Themes und meldet die Aenderung.
	 *
	 * @param {number} quantity Menge.
	 */
	Calculator.prototype.setQuantity = function ( quantity ) {
		if ( ! this.qtyEl ) {
			return quantity;
		}

		var max = math.parseNumber( this.qtyEl.getAttribute( 'max' ) );
		var min = math.parseNumber( this.qtyEl.getAttribute( 'min' ) );

		if ( isFinite( max ) && max > 0 && quantity > max ) {
			quantity = max;
		}

		if ( isFinite( min ) && min > 0 && quantity < min ) {
			quantity = min;
		}

		if ( String( quantity ) === this.qtyEl.value ) {
			return quantity;
		}

		this.schreibtMenge = true;
		this.qtyEl.value = String( quantity );

		// Andere Skripte, etwa das Theme, hoeren auf change.
		try {
			if ( $ ) {
				$( this.qtyEl ).trigger( 'change' );
			} else {
				this.qtyEl.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		} finally {
			this.schreibtMenge = false;
		}

		return quantity;
	};

	/**
	 * Zeigt, wie viel in einem Stueck steckt und was die Menge abdeckt.
	 */
	Calculator.prototype.renderHint = function () {
		if ( ! this.hintEl || ! this.data.i18n ) {
			return;
		}

		if ( 'none' === this.data.hint || 'glue' === this.data.input ) {
			this.hintEl.textContent = this.glueHint();

			return;
		}

		if ( ! this.size && ! ( 'length' === this.data.hint && this.data.lengthHint ) ) {
			this.hintEl.textContent = '';

			return;
		}

		// Meterware wird nach Mass verkauft. Dort ist die hinterlegte Groesse
		// die Laenge einer Leiste, keine Verkaufseinheit. Ein fester Text aus
		// den Einstellungen geht vor, etwa wenn die Laengen je Lieferung
		// schwanken und eine Spanne genannt werden soll.
		if ( 'length' === this.data.hint ) {
			if ( this.data.lengthHint ) {
				this.hintEl.textContent = this.data.lengthHint;

				return;
			}

			this.hintEl.textContent = this.data.i18n.soldByLength
				.replace( '%1$s', math.formatNumber( this.size, this.data.priceFormat ) )
				.replace( '%2$s', this.data.sizeUnit || '' );

			return;
		}

		this.hintEl.textContent = this.data.i18n.pieceEquals
			.replace( '%1$s', this.data.unitOne || '' )
			.replace( '%2$s', math.formatNumber( this.size, this.data.priceFormat ) )
			.replace( '%3$s', this.data.sizeUnit || '' );
	};

	/**
	 * Hinweis auf den Klebstoffbedarf.
	 *
	 * @return {string} Text.
	 */
	Calculator.prototype.glueHint = function () {
		if ( 'glue' !== this.data.input || ! this.data.glue || ! this.amountEl ) {
			return '';
		}

		var weight = math.glueWeight( math.parseNumber( this.amountEl.value ), this.data.glue.consumption );

		if ( null === weight ) {
			return '';
		}

		return this.data.i18n.glueWeight.replace( '%1$s', math.formatNumber( weight, this.data.priceFormat ) );
	};

	/**
	 * Meldung unter dem Eingabefeld.
	 *
	 * @param {string} message Text, leer blendet aus.
	 */
	Calculator.prototype.renderNote = function ( message ) {
		if ( this.noteEl ) {
			this.noteEl.textContent = message || '';
		}
	};

	/**
	 * Gesamtpreis.
	 */
	Calculator.prototype.renderTotal = function () {
		if ( ! this.totalEl ) {
			return;
		}

		if ( null === this.price || ! this.qtyEl ) {
			this.totalEl.innerHTML = '&ndash;';

			return;
		}

		var quantity = math.parseNumber( this.qtyEl.value );
		var total    = math.total( quantity, this.price );

		if ( null === total ) {
			this.totalEl.innerHTML = '&ndash;';

			return;
		}

		this.totalEl.innerHTML = math.formatPrice( total, this.data.priceFormat );
	};

	/**
	 * Ist der Verschnitt angehakt?
	 *
	 * @return {boolean} true wenn aktiv.
	 */
	Calculator.prototype.wasteActive = function () {
		return !! ( this.wasteEl && this.wasteEl.checked );
	};

	/**
	 * Startet alle Rechner auf der Seite.
	 */
	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '.caqc[data-caqc]' ), function ( root ) {
			if ( root.getAttribute( 'data-caqc-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-caqc-ready', '1' );
			new Calculator( root );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Themes, die das Formular per AJAX nachladen, etwa in einer Schnellansicht.
	if ( $ ) {
		$( document.body ).on( 'wc_variation_form quick_view_displayed', boot );
	}
}( window.jQuery ) );
