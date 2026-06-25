/* ==========================================================================
   CHECKOUT PAYMENT — PadelProfi
   Manejo de UI para métodos de pago + fix inicialización Stripe/WooPayments
   ========================================================================== */
( function ( $ ) {
	'use strict';

	function initPaymentUI() {
		const paymentMethods = document.querySelectorAll( '.wc_payment_method' );

		const updateSelected = () => {
			paymentMethods.forEach( ( m ) => {
				const r = m.querySelector( 'input[type="radio"]' );
				m.classList.toggle( 'mm-payment--selected', !! ( r && r.checked ) );
			} );
		};

		paymentMethods.forEach( ( method ) => {
			const radio = method.querySelector( 'input[type="radio"]' );
			if ( ! radio ) return;

			// Seleccionar al cambiar el radio
			radio.addEventListener( 'change', updateSelected );

			// Seleccionar al hacer click en cualquier parte del card
			method.addEventListener( 'click', () => {
				if ( ! radio.checked ) {
					radio.checked = true;
					radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			} );

			// Estado inicial
			if ( radio.checked ) {
				method.classList.add( 'mm-payment--selected' );
			}
		} );
	}

	/**
	 * Fix para WooCommerce Payments / Stripe:
	 * Cuando el paso 3 se hace visible, forzamos que Stripe
	 * reinicialice su iframe (que falló porque el panel estaba oculto).
	 */
	function triggerStripeInit() {
		// Disparar resize para que Stripe detecte las dimensiones del contenedor
		window.dispatchEvent( new Event( 'resize' ) );

		// Forzar update_checkout para que WooCommerce vuelva a renderizar
		// el payment element de Stripe
		$( document.body ).trigger( 'update_checkout' );

		// Si WooCommerce Payments usa su propio evento de montaje
		$( document.body ).trigger( 'payment_method_selected' );

		// Intentar montar manualmente el elemento de Stripe si está disponible
		setTimeout( function () {
			// WooCommerce Payments expone wc.wcpay o similar
			if ( window.wc && window.wc.wcpay ) {
				try {
					$( document.body ).trigger( 'wc_payment_block_render' );
				} catch ( e ) {}
			}

			// Stripe Elements clásico — forzar resize en los iframes
			document.querySelectorAll( '#wc-stripe-payment-element, #wc-woopayments-card-element, .wc-payment-element' ).forEach( function ( el ) {
				el.dispatchEvent( new Event( 'focus' ) );
			} );

			// Re-inicializar la UI de selección
			initPaymentUI();

			// Seleccionar el primer método de pago si ninguno está seleccionado
			const firstRadio = document.querySelector( 'input[name="payment_method"]' );
			if ( firstRadio && ! document.querySelector( 'input[name="payment_method"]:checked' ) ) {
				firstRadio.checked = true;
				firstRadio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		}, 300 );
	}

	// Escuchar evento custom que dispara checkout-steps.js al llegar al paso 3
	$( document.body ).on( 'mm_step_3_visible', function () {
		triggerStripeInit();
	} );

	// Re-inicializar cuando WooCommerce actualice el checkout
	$( document.body ).on( 'updated_checkout payment_method_selected', function () {
		initPaymentUI();
	} );

	$( function () {
		initPaymentUI();
	} );

} )( jQuery );