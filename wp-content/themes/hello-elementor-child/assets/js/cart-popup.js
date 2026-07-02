/* ==========================================================================
   CART POPUP — PadelProfi
   Panel lateral derecho estilo MediaMarkt.
   Velocidad: caché sessionStorage + prefetch al hover.
   ========================================================================== */

( function ( $ ) {
	'use strict';

	const CACHE_PREFIX = 'pp_popup_';
	const CACHE_TTL    = 10 * 60 * 1000; // 10 minutos

	// -------------------------------------------------------------------------
	// Actualizar icono del carrito — llama directamente a get_refreshed_fragments
	// -------------------------------------------------------------------------
	function refreshCartFragments() {
		var url = ( typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url )
			? wc_add_to_cart_params.wc_ajax_url.replace( '%%endpoint%%', 'get_refreshed_fragments' )
			: '/?wc-ajax=get_refreshed_fragments';

		$.post( url ).done( function ( data ) {
			if ( data && data.fragments ) {
				$.each( data.fragments, function ( key, value ) {
					// replaceWith puede fallar si el selector no existe; usamos .length como guardia
					var $target = $( key );
					if ( $target.length ) {
						$target.replaceWith( value );
					} else {
						// El elemento puede estar cacheado en sessionStorage por WC; forzamos evento
						$( document.body ).trigger( 'wc_fragments_loaded' );
					}
				} );
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Caché sessionStorage
	// -------------------------------------------------------------------------
	function cacheGet( pid ) {
		try {
			const raw = sessionStorage.getItem( CACHE_PREFIX + pid );
			if ( ! raw ) return null;
			const entry = JSON.parse( raw );
			if ( Date.now() - entry.ts > CACHE_TTL ) {
				sessionStorage.removeItem( CACHE_PREFIX + pid );
				return null;
			}
			return entry.data;
		} catch ( e ) { return null; }
	}

	function cacheSet( pid, data ) {
		try {
			sessionStorage.setItem( CACHE_PREFIX + pid, JSON.stringify( { ts: Date.now(), data } ) );
		} catch ( e ) {}
	}

	// -------------------------------------------------------------------------
	// Crear elementos del DOM (una sola vez)
	// -------------------------------------------------------------------------
	function buildPopup() {
		if ( document.getElementById( 'pp-cart-popup' ) ) return;

		// Overlay
		const overlay = document.createElement( 'div' );
		overlay.id        = 'pp-cart-overlay';
		overlay.className = 'pp-cart-overlay';
		document.body.appendChild( overlay );

		// Panel
		const panel = document.createElement( 'div' );
		panel.id        = 'pp-cart-popup';
		panel.className = 'pp-cart-popup';
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-modal', 'true' );
		panel.setAttribute( 'aria-hidden', 'true' );
		panel.innerHTML = `
			<div class="pp-cart-popup__header">
				<svg class="pp-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
					<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
					<polyline points="22 4 12 14.01 9 11.01"/>
				</svg>
				<span class="pp-cart-popup__header-text">${ ppCartPopup.i18n.added }</span>
				<button class="pp-cart-popup__close" aria-label="Cerrar">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
						<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>
			</div>
			<div class="pp-cart-popup__body" id="pp-cart-popup-body">
				<div class="pp-popup-loading">
					<svg class="pp-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="36" height="36">
						<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
					</svg>
				</div>
			</div>
			<div class="pp-cart-popup__footer">
				<button type="button" class="pp-popup-btn--secondary pp-popup-keep-shopping">
					${ ppCartPopup.i18n.keepShopping }
				</button>
				<a href="${ ppCartPopup.cartUrl }" class="pp-popup-btn--primary">
					${ ppCartPopup.i18n.goToCart }
				</a>
			</div>
		`;
		document.body.appendChild( panel );

		// Eventos cerrar — solo botones explícitos, no overlay ni Escape
		panel.querySelector( '.pp-cart-popup__close' ).addEventListener( 'click', closePopup );
		panel.querySelector( '.pp-popup-keep-shopping' ).addEventListener( 'click', closePopup );
	}

	function openPopup() {
		const panel   = document.getElementById( 'pp-cart-popup' );
		const overlay = document.getElementById( 'pp-cart-overlay' );
		if ( ! panel ) return;
		panel.classList.add( 'pp-cart-popup--active' );
		panel.setAttribute( 'aria-hidden', 'false' );
		overlay?.classList.add( 'pp-cart-overlay--active' );
		document.body.classList.add( 'pp-popup-open' );
	}

	function closePopup() {
		const panel   = document.getElementById( 'pp-cart-popup' );
		const overlay = document.getElementById( 'pp-cart-overlay' );
		if ( ! panel ) return;
		panel.classList.remove( 'pp-cart-popup--active' );
		panel.setAttribute( 'aria-hidden', 'true' );
		overlay?.classList.remove( 'pp-cart-overlay--active' );
		document.body.classList.remove( 'pp-popup-open' );
	}

	// -------------------------------------------------------------------------
	// Renderizar contenido del panel (layout MediaMarkt)
	// -------------------------------------------------------------------------
	function renderPopup( data ) {
		const body = document.getElementById( 'pp-cart-popup-body' );
		if ( ! body ) return;

		const product = data.product;
		const related = data.related || [];
		const i18n    = ppCartPopup.i18n;

		// ── Precio del producto añadido ──────────────────────────────────────
		let mainPrice = '';
		if ( product.on_sale && product.regular_price && product.sale_price ) {
			mainPrice = `<div class="pp-popup-product__price">
				${ product.discount_pct > 0 ? `<span class="pp-badge-discount">-${ product.discount_pct }%</span>` : '' }
				<del class="pp-old-price">${ product.regular_price }</del>
				<span class="pp-current-price">${ product.sale_price }</span>
			</div>`;
		} else {
			mainPrice = `<div class="pp-popup-product__price">
				<span class="pp-current-price">${ product.price_html }</span>
			</div>`;
		}

		const initQty = product.cart_qty || 1;

		let html = `
			<div class="pp-cart-popup__product">
				<a href="${ product.url }" class="pp-popup-product__img-wrap">
					<img src="${ product.image }" alt="${ product.name }" width="80" height="80" loading="eager" />
				</a>
				<div class="pp-popup-product__info">
					<a href="${ product.url }" class="pp-popup-product__name">${ product.name }</a>
					${ mainPrice }
					<div class="pp-popup-qty">
						<button class="pp-qty-btn pp-qty-minus" aria-label="Weniger"${ initQty <= 1 ? ' disabled' : '' }>−</button>
						<span class="pp-qty-display">${ initQty }</span>
						<button class="pp-qty-btn pp-qty-plus" aria-label="Mehr">+</button>
					</div>
				</div>
			</div>`;

		// ── Relacionados: una fila completa por producto, botón a la derecha ──
		if ( related.length ) {
			html += `<div class="pp-cart-popup__related-title">${ i18n.related }</div>`;
			html += `<div class="pp-related-list">`;

			related.forEach( function ( rp ) {

				// Rating: estrellas naranjas superpuestas sobre estrellas grises,
				// recortadas al % exacto (ej. 4.5/5 → 90% de ancho = media estrella visual real).
				let ratingHtml = '';
				if ( rp.rating && rp.rating > 0 ) {
					const pct = Math.max( 0, Math.min( 100, ( rp.rating / 5 ) * 100 ) );
					ratingHtml = `<div class="pp-related-item__rating">
						<span class="pp-stars" aria-label="${ rp.rating } de 5">
							<span class="pp-stars__bg">★★★★★</span>
							<span class="pp-stars__fg" style="width:${ pct }%">★★★★★</span>
						</span>
						${ rp.review_count > 0 ? `<span class="pp-related-item__review-count">${ rp.review_count }</span>` : '' }
					</div>`;
				}

				// Precio
				let rpPrice = '';
				if ( rp.on_sale && rp.regular_price && rp.sale_price ) {
					rpPrice = `<div class="pp-related-item__price">
						${ rp.discount_pct > 0 ? `<span class="pp-related-item__sale-badge">-${ rp.discount_pct }%</span>` : '' }
						<del class="pp-related-item__old">${ rp.regular_price }</del>
						<span class="pp-related-item__current-price">${ rp.sale_price }</span>
					</div>`;
				} else {
					rpPrice = `<div class="pp-related-item__price">
						<span class="pp-related-item__current-price">${ rp.price_html }</span>
					</div>`;
				}

				// Botón — siempre a la derecha en su propio contenedor
				const cartIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>`;

				const addBtn = rp.in_stock
					? ( rp.type === 'simple'
						? `<button class="pp-related-add-btn" data-product-id="${ rp.id }">${ cartIcon } ${ i18n.addToCart }</button>`
						: `<a href="${ rp.url }" class="pp-related-add-btn">${ cartIcon } ${ i18n.addToCart }</a>` )
					: `<span class="pp-related-item__outofstock">Sin stock</span>`;

				html += `
					<div class="pp-related-item">
						<a href="${ rp.url }" class="pp-related-item__img-wrap" tabindex="-1">
							<img src="${ rp.image }" alt="${ rp.name }" width="80" height="80" loading="lazy" />
						</a>
						<div class="pp-related-item__info">
							${ ratingHtml }
							<a href="${ rp.url }" class="pp-related-item__name">${ rp.name }</a>
							${ rpPrice }
						</div>
						<div class="pp-related-item__action">
							${ addBtn }
						</div>
					</div>`;
			} );

			html += `</div>`; // cierre .pp-related-list
		}

		body.innerHTML = html;

		// Bind selector de cantidad del producto añadido
		const qtyDisplay = body.querySelector( '.pp-qty-display' );
		const minusBtn   = body.querySelector( '.pp-qty-minus' );
		const plusBtn    = body.querySelector( '.pp-qty-plus' );
		let currentQty   = initQty;

		function updateQty( delta ) {
			if ( delta < 0 && currentQty <= 1 ) return;
			minusBtn.disabled = true;
			plusBtn.disabled  = true;
			$.post( ppCartPopup.ajaxUrl, {
				action:     'pp_update_cart_qty',
				product_id: product.id,
				delta:      delta,
				nonce:      ppCartPopup.nonce,
			} ).done( function ( response ) {
				if ( response.success ) {
					currentQty = response.data.qty;
					qtyDisplay.textContent = currentQty;
					refreshCartFragments();
				}
			} ).always( function () {
				minusBtn.disabled = currentQty <= 1;
				plusBtn.disabled  = false;
			} );
		}

		if ( plusBtn )  plusBtn.addEventListener(  'click', function () { updateQty(  1 ); } );
		if ( minusBtn ) minusBtn.addEventListener( 'click', function () { updateQty( -1 ); } );

		// Bind botones añadir relacionados
		body.querySelectorAll( '.pp-related-add-btn[data-product-id]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( btn.disabled ) return;
				const pid      = btn.dataset.productId;
				const origHtml = btn.innerHTML;
				btn.disabled   = true;
				btn.innerHTML  = ppCartPopup.i18n.loading;

				addToCartAjax( pid, 1, $( btn ) ).always( function () {
					btn.disabled  = false;
					btn.innerHTML = origHtml;
					btn.classList.add( 'pp-added' );
					setTimeout( () => btn.classList.remove( 'pp-added' ), 2000 );
				} );
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// AJAX add to cart — reutilizable
	// -------------------------------------------------------------------------
	function addToCartAjax( pid, qty, $btn ) {
		qty = qty || 1;
		const endpoint = ( typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url )
			? wc_add_to_cart_params.wc_ajax_url.replace( '%%endpoint%%', 'add_to_cart' )
			: '/?wc-ajax=add_to_cart';

		return $.post( endpoint, {
			product_id:    pid,
			quantity:      qty,
			'add-to-cart': pid,
		} ).done( function ( response ) {
			if ( response.error && response.product_url ) {
				window.location = response.product_url;
				return;
			}
			if ( response.fragments ) {
				$.each( response.fragments, function ( key, value ) {
					$( key ).replaceWith( value );
				} );
			}
			$( document.body ).trigger( 'added_to_cart', [
				response.fragments,
				response.cart_hash,
				$btn || $(),
			] );
		} );
	}

	// -------------------------------------------------------------------------
	// Cargar datos del popup (con caché)
	// -------------------------------------------------------------------------
	function fetchPopupData( pid, callback ) {
		const cached = cacheGet( pid );
		if ( cached ) { callback( cached ); return; }

		$.post( ppCartPopup.ajaxUrl, {
			action:     'pp_get_cart_popup',
			product_id: pid,
			nonce:      ppCartPopup.nonce,
		}, function ( response ) {
			if ( response.success ) {
				cacheSet( pid, response.data );
				callback( response.data );
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Prefetch al hacer hover (mejora velocidad percibida)
	// -------------------------------------------------------------------------
	function bindPrefetch() {
		$( document ).on( 'mouseenter', '.mi-btn-add-to-cart-carousel, .ajax_add_to_cart', function () {
			const pid = $( this ).data( 'product_id' ) || $( this ).data( 'product-id' );
			if ( pid && ! cacheGet( pid ) ) {
				fetchPopupData( pid, function () {} );
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Handler: botones del CAROUSEL
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.mi-btn-add-to-cart-carousel', function ( e ) {
		e.preventDefault();

		const $btn    = $( this );
		const pid     = $btn.data( 'product_id' );
		const qty     = $btn.data( 'quantity' ) || 1;
		const origTxt = $btn.html();

		if ( ! pid ) return;

		$btn.prop( 'disabled', true ).text( ppCartPopup.i18n.loading );

		buildPopup();
		openPopup();

		const ajaxCart = addToCartAjax( pid, qty, $btn );
		ajaxCart.done( function () {
			refreshCartFragments();
		} );
		const ajaxData = new Promise( function ( resolve ) {
			fetchPopupData( pid, resolve );
		} );

		Promise.all( [ ajaxData ] ).then( function ( [ data ] ) {
			renderPopup( data );
			$btn.prop( 'disabled', false ).html( origTxt );
		} );
	} );

	// -------------------------------------------------------------------------
	// Handler: botones [pp_add_to_cart] shortcode (.pp-add-btn)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.pp-add-btn', function ( e ) {
		e.preventDefault();

		const $btn    = $( this );
		const pid     = $btn.data( 'product_id' );
		const qty     = $btn.data( 'quantity' ) || 1;
		const origTxt = $btn.html();

		if ( ! pid ) return;

		$btn.prop( 'disabled', true ).text( ppCartPopup.i18n.loading );

		buildPopup();
		openPopup();

		const ajaxCart = addToCartAjax( pid, qty, $btn );
		ajaxCart.done( function () {
			refreshCartFragments();
		} );
		const ajaxData = new Promise( function ( resolve ) {
			fetchPopupData( pid, resolve );
		} );

		Promise.all( [ ajaxData ] ).then( function ( [ data ] ) {
			renderPopup( data );
			$btn.prop( 'disabled', false ).html( origTxt );
		} );
	} );

	// -------------------------------------------------------------------------
	// Handler: otros botones AJAX de WooCommerce (fuera del carousel)
	// -------------------------------------------------------------------------
	$( document.body ).on( 'added_to_cart', function ( event, fragments, cart_hash, $button ) {
		// Siempre actualizar el icono cuando se añade cualquier producto
		refreshCartFragments();

		if ( ! $button || ! $button.length ) return;
		if ( $button.hasClass( 'mi-btn-add-to-cart-carousel' ) ) return;
		if ( $button.hasClass( 'pp-add-btn' ) ) return;

		const pid = $button.data( 'product_id' ) || $button.data( 'product-id' );
		if ( ! pid ) return;

		buildPopup();

		const body = document.getElementById( 'pp-cart-popup-body' );
		if ( body ) {
			body.innerHTML = `<div class="pp-popup-loading">
				<svg class="pp-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="36" height="36">
					<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
				</svg>
			</div>`;
		}

		openPopup();

		fetchPopupData( pid, function ( data ) {
			renderPopup( data );
		} );
	} );

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------
	$( function () {
		buildPopup();
		bindPrefetch();
	} );

} )( jQuery );