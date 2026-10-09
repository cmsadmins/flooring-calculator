/**
 * Wiring of the quantity calculator on the product page.
 *
 * The math lives in CAQCMath, this file only handles DOM access and events.
 * jQuery is needed solely for the WooCommerce show_variation event,
 * everything else uses the browser's standard API.
 */
( function ( $ ) {
	'use strict';

	var math = window.CAQCMath;

	if ( ! math ) {
		return;
	}

	/**
	 * One calculator on the page.
	 *
	 * @param {HTMLElement} root Root element with data-caqc.
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
		this.savingEl  = this.findInFooter( '[data-caqc-total-saving]' );

		this.size    = this.toSize( this.data.sizePerPiece );
		this.price   = 'number' === typeof this.data.price ? this.data.price : null;
		this.regular = 'number' === typeof this.data.regularPrice ? this.data.regularPrice : null;

		// While we set the quantity ourselves, the reverse direction is locked.
		// Otherwise the triggered change writes back the area the customer is
		// typing right now, and the field jumps away under their fingers.
		this.settingQuantity = false;

		this.bind();
		this.renderHint();
		this.syncFromQuantity();
		this.restore();
	}

	/**
	 * sessionStorage key, per unit. Someone who enters 37.5 m² of flooring
	 * sees the same area on the next flooring product and on the adhesive,
	 * while skirting boards show the meters entered last.
	 *
	 * @return {string|null} Key, null if there is nothing to remember.
	 */
	Calculator.prototype.storageKey = function () {
		if ( ! this.amountEl || ! this.data.inputUnit ) {
			return null;
		}

		return 'caqc:amount:' + this.data.inputUnit;
	};

	/**
	 * Remembers the input and waste setting for the next product page.
	 */
	Calculator.prototype.remember = function () {
		var key = this.storageKey();

		if ( ! key ) {
			return;
		}

		try {
			var amount = math.parseNumber( this.amountEl.value );

			if ( isFinite( amount ) && amount > 0 ) {
				window.sessionStorage.setItem( key, this.amountEl.value );
			}

			if ( this.wasteEl ) {
				window.sessionStorage.setItem( 'caqc:waste', this.wasteEl.checked ? '1' : '0' );
			}
		} catch ( e ) {
			// Private mode or blocked storage: then we simply do not remember.
		}
	};

	/**
	 * Restores the last input if it matches the unit of this page.
	 */
	Calculator.prototype.restore = function () {
		var key = this.storageKey();

		if ( ! key ) {
			return;
		}

		try {
			var stored = window.sessionStorage.getItem( key );
			var waste  = window.sessionStorage.getItem( 'caqc:waste' );

			if ( this.wasteEl && null !== waste ) {
				this.wasteEl.checked = '1' === waste;
			}

			if ( null !== stored && '' !== stored && isFinite( math.parseNumber( stored ) ) ) {
				this.amountEl.value = stored;
				this.syncFromAmount();
			} else if ( this.wasteEl ) {
				this.renderWaste();
			}
		} catch ( e ) {
			// see remember()
		}
	};

	/**
	 * The quantity field sits between the hooks and belongs to the theme.
	 *
	 * @return {HTMLElement|null} Input field.
	 */
	Calculator.prototype.findQuantityInput = function () {
		return this.root.querySelector( 'input.qty' )
			|| this.root.querySelector( 'input[name="quantity"]' );
	};

	/**
	 * The price box sits after the calculator, not inside it.
	 *
	 * @return {HTMLElement|null} Display element.
	 */
	Calculator.prototype.findTotalValue = function () {
		return this.findInFooter( '[data-caqc-total-value]' );
	};

	/**
	 * Finds an element in the footer after the calculator.
	 *
	 * @param {string} selector CSS selector.
	 * @return {HTMLElement|null} Element.
	 */
	Calculator.prototype.findInFooter = function ( selector ) {
		var footer = this.root.nextElementSibling;

		if ( footer && footer.classList.contains( 'caqc-footer' ) ) {
			return footer.querySelector( selector );
		}

		return this.form.querySelector ? this.form.querySelector( selector ) : null;
	};

	/**
	 * Accepts only positive, finite sizes.
	 *
	 * @param {*} value Raw value.
	 * @return {number|null} Size.
	 */
	Calculator.prototype.toSize = function ( value ) {
		var number = math.parseNumber( value );

		return isFinite( number ) && number > 0 ? number : null;
	};

	/**
	 * Attaches all events.
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

		// Select prefilled values on tap. Otherwise the new input is appended
		// to the old one, and 1,74 plus 37,5 turns into 1,7437,5.
		Array.prototype.forEach.call( this.root.querySelectorAll( '[data-caqc-amount], [data-caqc-room] input' ), function ( input ) {
			input.addEventListener( 'focus', function () {
				// On touch devices the tap only places the cursor after the focus event.
				setTimeout( function () {
					if ( document.activeElement === input ) {
						input.select();
					}
				}, 0 );
			} );
		} );

		Array.prototype.forEach.call( this.root.querySelectorAll( '[data-caqc-step]' ), function ( button ) {
			button.addEventListener( 'click', function () {
				self.stepAmount( parseInt( button.getAttribute( 'data-caqc-step' ), 10 ) );
			} );
		} );

		if ( this.qtyEl ) {
			// change covers the theme's plus and minus buttons,
			// input covers keyboard entry.
			this.qtyEl.addEventListener( 'change', function () {
				self.syncFromQuantity();
			} );

			this.qtyEl.addEventListener( 'input', function () {
				self.syncFromQuantity();
			} );

			// Themes usually trigger the change via jQuery. A jQuery trigger
			// does not reach the listeners above, because it only calls the
			// handlers in its own list. So listen via jQuery here as well,
			// otherwise the area does not update when someone uses the
			// theme's plus button.
			if ( $ ) {
				$( this.qtyEl ).on( 'change.caqc input.caqc', function () {
					self.syncFromQuantity();
				} );
			}
		}

		if ( this.wasteEl ) {
			this.wasteEl.addEventListener( 'change', function () {
				// Without an area field the checkbox belongs to the room calculator.
				if ( ! self.amountEl ) {
					self.applyRoom();

					return;
				}

				self.syncFromAmount( true );
				self.remember();
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
	 * Area was edited: update the quantity, leave the area as it is.
	 *
	 * The field is deliberately not rewritten to a multiple of the pack size,
	 * otherwise it jumps while the customer is typing.
	 *
	 * @param {boolean} keepAmount Do nothing even if the field is empty.
	 */
	Calculator.prototype.syncFromAmount = function ( keepAmount ) {
		if ( ! this.amountEl || ! this.qtyEl ) {
			return;
		}

		var raw    = this.amountEl.value.trim();
		var amount = math.parseNumber( raw );

		// Empty field: the customer is typing. The quantity stays, otherwise
		// it jumps to one on every deletion.
		if ( '' === raw ) {
			this.renderNote( '' );

			return;
		}

		// No readable number: wait as well, this is usually a typo.
		if ( ! isFinite( amount ) ) {
			this.renderNote( '' );

			return;
		}

		// Zero or negative is an actual value, not an intermediate step. The quantity
		// goes to the minimum so that input and price match.
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

		var applied = this.setQuantity( quantity );

		// Stock can be lower than the demand. Then the field shows an area
		// that cannot be delivered as such, and the customer should be told
		// instead of wondering about the number next to it.
		if ( applied < quantity ) {
			this.renderNote( this.buildNote( 'capped', applied ) );
		} else if ( applied > quantity ) {
			// A minimum purchase raises the quantity. Without a notice the customer
			// wonders why two rolls are shown next to 15 m².
			this.renderNote( this.buildNote( 'minimum', applied ) );
		} else {
			this.renderNote( '' );
		}

		this.renderHint();
		this.renderWaste();
		this.renderTotal();
		this.remember();
	};

	/**
	 * Line next to the waste checkbox: what the input becomes with the surcharge.
	 * The input itself stays untouched, otherwise after the second toggle
	 * the customer no longer knows what they typed.
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

		this.wasteOut.textContent = this.wasteLine( gross, this.data.inputUnit, math.parseNumber( this.qtyEl.value ) );
	};

	/**
	 * Text of the waste line, e.g. "incl. 7,5% waste: 41,25 m², 24 packs".
	 *
	 * @param {number} gross    Amount including waste.
	 * @param {string} unit     Unit of the amount.
	 * @param {number} quantity Ordered quantity.
	 * @return {string} Text.
	 */
	Calculator.prototype.wasteLine = function ( gross, unit, quantity ) {
		var format  = this.data.priceFormat || {};
		var percent = this.data.wastePercent;
		var whole   = { decimals: 0, thousandSeparator: format.thousandSeparator };

		return this.data.i18n.wasteResult
			.replace( '%%', '%' )
			.replace( '%1$s', math.formatNumber( percent, { decimals: percent % 1 ? 1 : 0, decimalSeparator: format.decimalSeparator, thousandSeparator: format.thousandSeparator } ) )
			.replace( '%2$s', math.formatNumber( gross, format ) )
			.replace( '%3$s', unit || '' )
			.replace( '%4$s', math.formatNumber( quantity, whole ) )
			.replace( '%5$s', quantity > 1 ? this.data.unitMany : this.data.unitOne );
	};

	/**
	 * Builds a notice with quantity and unit.
	 *
	 * @param {string} key      Key in the string table.
	 * @param {number} quantity Quantity.
	 * @return {string} Text, empty if the key is missing.
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
	 * Smallest allowed value of the quantity field.
	 *
	 * @return {number} Minimum, at least 1.
	 */
	Calculator.prototype.minQuantity = function () {
		var min = math.parseNumber( this.qtyEl.getAttribute( 'min' ) );

		return isFinite( min ) && min > 0 ? min : 1;
	};

	/**
	 * Converts an input into a quantity, depending on the type.
	 *
	 * @param {number} amount Area or length.
	 * @return {number|null} Quantity.
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
	 * Quantity was edited: update the area.
	 */
	Calculator.prototype.syncFromQuantity = function () {
		if ( ! this.qtyEl || this.settingQuantity ) {
			return;
		}

		// Meters typed by hand no longer match the room calculator result.
		if ( ! this.amountEl && this.wasteOut ) {
			this.wasteOut.textContent = '';
		}

		var quantity = math.parseNumber( this.qtyEl.value );

		// If the area in the field already matches this quantity, it stays.
		// Otherwise a late change from the theme first turns 37.5 m² into
		// 37.82 m² and on the next pass into one more unit.
		var current = this.amountEl ? math.parseNumber( this.amountEl.value ) : NaN;

		if ( isFinite( current ) && current > 0 && this.quantityFor( current ) === quantity ) {
			this.renderHint();
			this.renderWaste();
			this.renderTotal();

			return;
		}

		if ( this.amountEl && isFinite( quantity ) && quantity > 0 ) {
			var amount = 'glue' === this.data.input
				? ( this.data.glue ? math.areaFromGlue( quantity, this.data.glue.consumption, this.data.glue.pack_size ) : null )
				: math.amountFromQuantity( quantity, this.size );

			// With waste, the quantity covers the area plus surcharge. The
			// field shows the area without surcharge, the line at the checkbox shows
			// the rest. Otherwise field and line contradict each other.
			if ( null !== amount && this.wasteActive() ) {
				amount = math.amountWithoutWaste( amount, this.data.wastePercent );
			}

			if ( null !== amount ) {
				this.amountEl.value = math.formatNumber( amount, this.data.priceFormat );

				// Field and quantity match again, a notice about the old input would be wrong.
				this.renderNote( '' );
			}
		}

		this.renderHint();
		this.renderWaste();
		this.renderTotal();
	};

	/**
	 * Plus or minus on the area field. One step is exactly one unit of the
	 * product, otherwise a click changes the quantity by several packs.
	 *
	 * @param {number} direction 1 or -1.
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
	 * Formats the input as soon as the field loses focus.
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
	 * Copies the room calculator result into the main field.
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

			if ( ! this.amountEl && this.wasteOut ) {
				this.wasteOut.textContent = '';
			}

			return;
		}

		if ( this.amountEl ) {
			this.amountEl.value = math.formatNumber( value, this.data.priceFormat );
			this.syncFromAmount();
		} else if ( this.qtyEl ) {
			// Meter type without an area field: straight into the quantity, with waste if ticked.
			var meters = this.wasteActive() ? math.lengthWithWaste( value, this.data.wastePercent ) : null;

			this.setQuantity( null !== meters ? meters : Math.max( 1, Math.ceil( value ) ) );
			this.syncFromQuantity();
			this.renderRoomWaste( value, meters );
		}

		if ( resultEl && this.data.i18n ) {
			resultEl.textContent = this.data.i18n.roomResult
				.replace( '%1$s', math.formatNumber( value, this.data.priceFormat ) )
				.replace( '%2$s', this.data.inputUnit || this.data.sizeUnit || '' );
		}
	};

	/**
	 * Goods sold by the meter: explains the extra meters next to the checkbox.
	 *
	 * @param {number}      length Room result in m.
	 * @param {number|null} meters Ordered meters including waste, null without.
	 */
	Calculator.prototype.renderRoomWaste = function ( length, meters ) {
		if ( ! this.wasteOut ) {
			return;
		}

		if ( null === meters || ! this.data.i18n ) {
			this.wasteOut.textContent = '';

			return;
		}

		this.wasteOut.textContent = this.wasteLine( math.amountWithWaste( length, this.data.wastePercent ), this.data.sizeUnit, meters );
	};

	/**
	 * Variation selected: take over size and price.
	 *
	 * @param {object} variation Variation data from WooCommerce.
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

		this.regular = null;

		if ( 'number' === typeof variation.display_regular_price
			&& variation.display_regular_price > this.price ) {
			this.regular = variation.display_regular_price;
		}

		this.root.classList.remove( 'caqc--no-variation' );
		this.renderNote( '' );
		this.renderHint();

		// If an area is already in the field, for example from the previous product
		// or because the customer typed before choosing the variation, it stays and
		// determines the quantity. Otherwise choosing the variation would overwrite
		// the input with "1 piece".
		// WooCommerce and the theme reset the quantity field to the minimum
		// themselves after show_variation. Hence one tick later, otherwise
		// the right side shows 1 again while the price already counts 16.
		var self = this;

		window.setTimeout( function () {
			var amount = self.amountEl ? math.parseNumber( self.amountEl.value ) : NaN;

			if ( isFinite( amount ) && amount > 0 ) {
				self.syncFromAmount();
			} else {
				self.syncFromQuantity();
			}
		}, 0 );
	};

	/**
	 * Variation selection was reset.
	 */
	Calculator.prototype.onVariationCleared = function () {
		if ( ! this.data.useVariation ) {
			return;
		}

		this.size    = null;
		this.price   = null;
		this.regular = null;

		this.root.classList.add( 'caqc--no-variation' );
		this.renderHint();
		this.renderTotal();
	};

	/**
	 * Writes a quantity into the theme's field and reports the change.
	 *
	 * @param {number} quantity Quantity.
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

		this.settingQuantity = true;
		this.qtyEl.value = String( quantity );

		// Other scripts, such as the theme, listen for change.
		try {
			if ( $ ) {
				$( this.qtyEl ).trigger( 'change' );
			} else {
				this.qtyEl.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		} finally {
			this.settingQuantity = false;
		}

		return quantity;
	};

	/**
	 * Shows how much one piece contains and what the quantity covers.
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

		// Goods sold by the meter are cut to measure. There the stored size
		// is the length of one board, not a sales unit. A fixed text from
		// the settings takes precedence, for example when lengths vary per
		// delivery and a range should be stated.
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
	 * Notice about the adhesive required.
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
	 * Message below the input field.
	 *
	 * @param {string} message Text, empty hides it.
	 */
	Calculator.prototype.renderNote = function ( message ) {
		if ( this.noteEl ) {
			this.noteEl.textContent = message || '';
		}
	};

	/**
	 * Total price.
	 */
	Calculator.prototype.renderTotal = function () {
		if ( ! this.totalEl ) {
			return;
		}

		if ( null === this.price || ! this.qtyEl ) {
			this.totalEl.textContent = '–';
			this.renderSaving( null, null );

			return;
		}

		var quantity = math.parseNumber( this.qtyEl.value );
		var total    = math.total( quantity, this.price );

		if ( null === total ) {
			this.totalEl.textContent = '–';
			this.renderSaving( null, null );

			return;
		}

		this.totalEl.textContent = math.formatPrice( total, this.data.priceFormat );
		this.renderSaving( quantity, total );
	};

	/**
	 * Regular total price and savings for discounted items.
	 *
	 * @param {number|null} quantity Quantity.
	 * @param {number|null} total    Total price at the sale price.
	 */
	Calculator.prototype.renderSaving = function ( quantity, total ) {
		if ( ! this.savingEl ) {
			return;
		}

		var regularTotal = null !== this.regular && null !== quantity ? math.total( quantity, this.regular ) : null;

		if ( null === regularTotal || null === total || regularTotal <= total || ! this.data.i18n ) {
			this.savingEl.textContent = '';

			return;
		}

		// The text comes from the translation. It is inserted as text, only
		// the two prices get their own element.
		var values = {
			'%1$s': [ 's', math.formatPrice( regularTotal, this.data.priceFormat ) ],
			'%2$s': [ 'strong', math.formatPrice( regularTotal - total, this.data.priceFormat ) ],
		};
		var el = this.savingEl;

		el.textContent = '';

		this.data.i18n.saving.split( /(%[12]\$s)/ ).forEach( function ( part ) {
			if ( ! values[ part ] ) {
				el.appendChild( document.createTextNode( part ) );

				return;
			}

			var node = document.createElement( values[ part ][ 0 ] );

			node.textContent = values[ part ][ 1 ];
			el.appendChild( node );
		} );
	};

	/**
	 * Is the waste checkbox ticked?
	 *
	 * @return {boolean} true if active.
	 */
	Calculator.prototype.wasteActive = function () {
		return !! ( this.wasteEl && this.wasteEl.checked );
	};

	/**
	 * Starts all calculators on the page.
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

	// Themes that reload the form via AJAX, for example in a quick view.
	if ( $ ) {
		$( document.body ).on( 'wc_variation_form quick_view_displayed', boot );
	}
}( window.jQuery ) );
