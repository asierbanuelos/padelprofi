/* ==========================================================================
   CART PAGE — PadelProfi
   Maneja cantidad, eliminar ítem, cupones y añadir cross-sells vía AJAX.
   ========================================================================== */

( function ( $ ) {
	'use strict';

	const nonce   = ppCartPage.nonce;
	const ajaxUrl = ppCartPage.ajaxUrl;

	// ── Helpers ────────────────────────────────────────────────────────────

	function updateSummary( html ) {
		$( '#pp-summary-rows' ).html( html );
	}

	function updateCount( count ) {
		const noun = count === 1 ? 'Artikel' : 'Artikel';
		$( '#pp-cart-count' ).text( count + ' ' + noun );
		// Actualizar icono del menú
		$( '.pp-cart-menu__count' ).text( count ).toggle( count > 0 );
	}

	function setLoading( $el, on ) {
		$el.toggleClass( 'pp-cart-loading', on );
	}

	// ── Cantidad + / - ─────────────────────────────────────────────────────

	$( document ).on( 'click', '.pp-cqty-minus, .pp-cqty-plus', function () {
		const $btn  = $( this );
		const key   = $btn.data( 'key' );
		const delta = $btn.hasClass( 'pp-cqty-plus' ) ? 1 : -1;
		const $item = $( '#pp-cart-item-' + key );

		setLoading( $item, true );
		$item.find( '.pp-cqty-btn' ).prop( 'disabled', true );

		$.post( ajaxUrl, {
			action:        'pp_cart_update_qty',
			nonce:         nonce,
			cart_item_key: key,
			delta:         delta,
		} ).done( function ( res ) {
			if ( ! res.success ) return;

			if ( res.data.removed ) {
				$item.slideUp( 220, function () { $( this ).remove(); } );
			} else {
				$( '#pp-qty-'  + key ).text( res.data.qty );
				$( '#pp-line-' + key ).html( res.data.line_price );
				$item.find( '.pp-cqty-minus' ).prop( 'disabled', res.data.qty <= 1 );
				$item.find( '.pp-cqty-plus'  ).prop( 'disabled', false );
			}

			updateSummary( res.data.summary_html );
			updateCount( res.data.count );
		} ).fail( function () {
			$item.find( '.pp-cqty-btn' ).prop( 'disabled', false );
		} ).always( function () {
			setLoading( $item, false );
		} );
	} );

	// ── Eliminar ítem ───────────────────────────────────────────────────────

	$( document ).on( 'click', '.pp-cart-item__remove', function () {
		const $btn  = $( this );
		const key   = $btn.data( 'key' );
		const $item = $( '#pp-cart-item-' + key );

		if ( ! confirm( '¿Möchtest du diesen Artikel entfernen?' ) ) return;

		setLoading( $item, true );

		$.post( ajaxUrl, {
			action:        'pp_cart_remove_item',
			nonce:         nonce,
			cart_item_key: key,
		} ).done( function ( res ) {
			if ( ! res.success ) return;

			$item.slideUp( 220, function () { $( this ).remove(); } );
			updateSummary( res.data.summary_html );
			updateCount( res.data.count );

			if ( res.data.is_empty ) {
				location.reload();
			}
		} );
	} );

	// ── Cupón — aplicar ────────────────────────────────────────────────────

	$( '#pp-coupon-apply' ).on( 'click', function () {
		const code = $( '#pp-coupon-input' ).val().trim();
		const $msg = $( '#pp-coupon-msg' );

		if ( ! code ) {
			$msg.text( 'Bitte gib einen Gutscheincode ein.' ).removeClass( 'pp-coupon-msg--ok' ).addClass( 'pp-coupon-msg--err' );
			return;
		}

		$( this ).prop( 'disabled', true ).text( 'Laden…' );

		$.post( ajaxUrl, {
			action:      'pp_cart_apply_coupon',
			nonce:       nonce,
			coupon_code: code,
		} ).done( function ( res ) {
			if ( res.success ) {
				updateSummary( res.data.summary_html );
				$( '#pp-coupon-input' ).val( '' );
				$msg.text( 'Gutschein angewendet!' ).removeClass( 'pp-coupon-msg--err' ).addClass( 'pp-coupon-msg--ok' );
			} else {
				$msg.text( res.data.msg || 'Ungültiger Code.' ).removeClass( 'pp-coupon-msg--ok' ).addClass( 'pp-coupon-msg--err' );
			}
		} ).always( function () {
			$( '#pp-coupon-apply' ).prop( 'disabled', false ).text( 'Einlösen' );
		} );
	} );

	$( '#pp-coupon-input' ).on( 'keypress', function ( e ) {
		if ( e.which === 13 ) $( '#pp-coupon-apply' ).trigger( 'click' );
	} );

	// ── Cupón — quitar ─────────────────────────────────────────────────────

	$( document ).on( 'click', '.pp-remove-coupon', function () {
		const code = $( this ).data( 'coupon' );

		$.post( ajaxUrl, {
			action:      'pp_cart_remove_coupon',
			nonce:       nonce,
			coupon_code: code,
		} ).done( function ( res ) {
			if ( res.success ) {
				updateSummary( res.data.summary_html );
				$( '#pp-coupon-msg' ).text( '' );
			}
		} );
	} );

	// ── Flechas del slider de cross-sells ──────────────────────────────────────

	$( document ).on( 'click', '.pp-cs-arrow', function () {
		var $list = $( this ).closest( '.pp-cart-cs' ).find( '.pp-cart-cs__list' );
		var list  = $list[0];
		if ( ! list ) return;
		var card     = list.querySelector( '.pp-cs-card' );
		var cardStep = card ? ( card.offsetWidth + 12 ) * 2 : 400;
		var dir      = $( this ).hasClass( 'pp-cs-arrow--prev' ) ? -1 : 1;
		list.scrollBy( { left: dir * cardStep, behavior: 'smooth' } );
	} );

	// ── Añadir cross-sell al carrito ────────────────────────────────────────

	$( document ).on( 'click', '.pp-cs-card__btn[data-product-id]', function () {
		const $btn = $( this );
		const pid  = $btn.data( 'product-id' );

		$btn.prop( 'disabled', true ).text( 'Laden…' );

		$.post( ajaxUrl, {
			action:     'pp_cart_add_crosssell',
			nonce:      nonce,
			product_id: pid,
		} ).done( function ( res ) {
			if ( res.success ) {
				$btn.addClass( 'pp-added' ).text( '✓ Hinzugefügt' );
				updateSummary( res.data.summary_html );
				updateCount( res.data.count );
				setTimeout( function () {
					$btn.removeClass( 'pp-added' ).text( 'In den Warenkorb' ).prop( 'disabled', false );
				}, 2500 );
			} else {
				$btn.prop( 'disabled', false ).text( 'Fehler' );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false ).text( 'In den Warenkorb' );
		} );
	} );

} )( jQuery );
