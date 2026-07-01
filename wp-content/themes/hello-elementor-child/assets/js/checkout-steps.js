/* ==========================================================================
   CHECKOUT MULTI-STEP — PadelProfi
   Navegación por pasos, validación y barra de progreso.
   ========================================================================== */

( function ( $ ) {
	'use strict';

	class MMCheckout {
		constructor() {
			this.currentStep  = 1;
			this.totalSteps   = 4;
			this.panels       = document.querySelectorAll( '.mm-step-panel' );
			this.progressSteps = document.querySelectorAll( '.mm-step[data-step]' );
			this.connectors   = document.querySelectorAll( '.mm-step__connector' );
			this.sidebarBtn   = document.getElementById( 'mm-sidebar-submit' );
			this.termsCheck   = document.getElementById( 'mm-terms-check' );
			this.step4Btn     = document.getElementById( 'mm-step4-submit' );
			this.step4Terms   = document.getElementById( 'mm-step4-terms' );
			this.mobileFooter = null;
			this.mobileTotalEl = null;
			this.mobileBtn    = null;
			// Tracking para DOM prestado al área de acción del paso 4
			this.borrowedPaymentBox       = null;
			this.borrowedPaymentBoxParent = null;
			this.borrowedExpressEl        = null;
			this.borrowedExpressElParent  = null;

			if ( ! this.panels.length ) return;

			this.init();
		}

		init() {
			this.renderStep( this.currentStep );
			this.bindNavigationButtons();
			this.bindProgressBarClicks();
			this.bindShippingMethodChange();
			this.bindEmpresaToggle();
			this.bindCoupon();
			this.bindSidebarSubmit();
			this.bindCouponRemove();
			this.bindCartControls();
			this.bindStep4Submit();
			this.bindStreetSync();
			this.createMobileFooter();
			this.bindWooCommerceUpdates();
			this.bindPaymentPopup();
		}

		/* ------------------------------------------------------------------
		   billing_address_1 = Straße, billing_address_2 = Hausnummer.
		   Ambos son campos nativos de WC/PayPal, no necesitan sincronización.
		   Este método solo dispara update_checkout al escribir para que
		   WC recalcule envíos en tiempo real.
		   ------------------------------------------------------------------ */
		bindStreetSync() {
			const streetInput = document.getElementById( 'billing_address_1' );
			const houseInput  = document.getElementById( 'billing_address_2' );

			if ( ! streetInput || ! houseInput ) return;

			let _syncTimer;
			const sync = () => {
				clearTimeout( _syncTimer );
				_syncTimer = setTimeout( () => {
					$( document.body ).trigger( 'update_checkout' );
				}, 800 );
			};

			streetInput.addEventListener( 'input', sync );
			houseInput.addEventListener( 'input', sync );
		}



		/* ------------------------------------------------------------------
		   Renderizar paso
		   ------------------------------------------------------------------ */
		renderStep( step ) {
			this.panels.forEach( ( panel ) => {
				const panelStep = parseInt( panel.dataset.stepContent );
				const isActive  = panelStep === step;

				if ( isActive ) {
					// Limpiar cualquier override del hack de Stripe y mostrar normal
					panel.style.cssText = '';
					panel.style.display = 'block';
				} else if ( panelStep === 3 && step === 4 ) {
					// Paso 4 activo: mantener el panel de pago (paso 3) en el DOM
					// con dimensiones reales para que Stripe/PayPal no desmonte su iframe.
					// position:fixed + left:0 + width:100vw garantizan un width correcto
					// aunque el panel esté fuera de la pantalla.
					panel.style.cssText = 'display:block!important;position:fixed!important;top:-9999px!important;left:0!important;width:100vw!important;opacity:0!important;pointer-events:none!important;z-index:-1!important;';
				} else {
					panel.style.cssText = '';
					panel.style.display = 'none';
				}

				panel.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
				panel.classList.toggle( 'mm-step-panel--active', isActive );
			} );

			// Toggle full-width layout for step 4 (hides sidebar)
			const layout = document.querySelector( '.mm-checkout-layout' );
			if ( layout ) layout.classList.toggle( 'mm-step4-active', step === 4 );
			if ( typeof window.ppFixLayout === 'function' ) window.ppFixLayout();

			this.updateProgressBar( step );
			this.updateSidebarBtnText();
			this.updateAddressDisplay();
			this.updateReviewSections();
			this.updateMobileFooter();

			// Al entrar en paso 2: recalcular envíos con la dirección introducida en paso 1.
			if ( step === 2 ) {
				$( document.body ).trigger( 'update_checkout' );
			}

			// Al entrar en paso 3: recalcular totales y notificar a Stripe.
			if ( step === 3 ) {
				this.restoreStep4Borrows();
				$( document.body ).trigger( 'update_checkout' );
				$( document.body ).trigger( 'mm_step_3_visible' );
			}

			// Al entrar en paso 4: guardar método y renderizar botón dinámico (síncrono).
			if ( step === 4 ) {
				// Priorizar selección virtual (Klarna/ApplePay/GooglePay) sobre la real
				const vRadio = document.querySelector( 'input[name="mm_virtual_payment"]:checked' );
				if ( vRadio ) {
					this._step4PaymentMethod = vRadio.value;
				} else {
					const r = document.querySelector( 'input[name="payment_method"]:checked' );
					if ( r ) this._step4PaymentMethod = r.value;
				}
				this.renderStep4Action();
				// Notificar a bindStep4Submit para que re-bindee el botón PayPal
				setTimeout( () => document.dispatchEvent( new Event( 'mm_step4_rendered' ) ), 100 );
			}

			window.scrollTo( { top: 0, behavior: 'smooth' } );
		}

		/* ------------------------------------------------------------------
		   Barra de progreso
		   ------------------------------------------------------------------ */
		updateProgressBar( currentStep ) {
			this.progressSteps.forEach( ( el ) => {
				const stepNum = parseInt( el.dataset.step );
				el.classList.remove( 'mm-step--active', 'mm-step--completed' );

				if ( stepNum < currentStep ) {
					el.classList.add( 'mm-step--completed' );
					el.setAttribute( 'aria-current', 'false' );
				} else if ( stepNum === currentStep ) {
					el.classList.add( 'mm-step--active' );
					el.setAttribute( 'aria-current', 'step' );
				}
			} );

			this.connectors.forEach( ( el, index ) => {
				el.classList.toggle( 'mm-step__connector--completed', index < currentStep - 1 );
			} );
		}

		/* ------------------------------------------------------------------
		   Validación por paso
		   ------------------------------------------------------------------ */
		validateStep( step ) {
			const panel = document.querySelector( `[data-step-content="${ step }"]` );
			if ( ! panel ) return true;

			let valid = true;
			let firstInvalid = null;

			if ( step === 1 ) {
				// WooCommerce marca campos requeridos con .validate-required en el wrapper
				const requiredRows = panel.querySelectorAll( '.form-row.validate-required' );

				requiredRows.forEach( ( row ) => {
					const field = row.querySelector( 'input:not([type=hidden]):not([type=checkbox]), select, textarea' );
					if ( ! field ) return;

					const val = field.value.trim();
					if ( ! val ) {
						valid = false;
						row.classList.add( 'woocommerce-invalid', 'woocommerce-invalid--required-field' );
						row.classList.remove( 'woocommerce-validated' );
						if ( ! firstInvalid ) firstInvalid = field;
					} else {
						row.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid--required-field' );
						row.classList.add( 'woocommerce-validated' );
					}
				} );

				// Validar email
				const emailField = document.getElementById( 'billing_email' );
				if ( emailField ) {
					const emailRow = emailField.closest( '.form-row' );
					if ( ! emailField.value.trim() ) {
						valid = false;
						emailRow?.classList.add( 'woocommerce-invalid', 'woocommerce-invalid--required-field' );
						if ( ! firstInvalid ) firstInvalid = emailField;
					} else if ( ! this.isValidEmail( emailField.value ) ) {
						valid = false;
						emailRow?.classList.add( 'woocommerce-invalid' );
						if ( ! firstInvalid ) firstInvalid = emailField;
					} else {
						emailRow?.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid--required-field' );
						emailRow?.classList.add( 'woocommerce-validated' );
					}
				}

				// Limpiar error al escribir en cualquier campo del paso 1
				panel.querySelectorAll( 'input, select, textarea' ).forEach( ( f ) => {
					f.addEventListener( 'input change', () => {
						const r = f.closest( '.form-row' );
						if ( r && f.value.trim() ) {
							r.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid--required-field' );
							r.classList.add( 'woocommerce-validated' );
							panel.querySelector( '.mm-step-error' )?.remove();
						}
					}, { once: false } );
				} );

				// Validar inputs con required en pp-field-wrap (campos custom con label flotante).
				// EXCLUYE billing_address_1 y billing_address_2 (se validan justo debajo).
				panel.querySelectorAll( '.pp-field-wrap input[required], .pp-field-wrap textarea[required]' ).forEach( ( input ) => {
					// Saltar campos ocultos (ej. billing_company cuando tipo es Privatperson)
					if ( input.closest( '[style*="display:none"], [style*="display: none"]' ) ) return;
					// Saltar billing_address_1 y billing_address_2 (se validan justo debajo)
					if ( input.id === 'billing_address_1' || input.id === 'billing_address_2' ) return;

					const val = input.value.trim();
					const wrap = input.closest( '.pp-field-wrap' );
					if ( ! val ) {
						valid = false;
						wrap?.classList.add( 'pp-field--invalid' );
						input.classList.add( 'pp-invalid' );
						if ( ! firstInvalid ) firstInvalid = input;
					} else {
						wrap?.classList.remove( 'pp-field--invalid' );
						input.classList.remove( 'pp-invalid' );
					}
					input.addEventListener( 'input', () => {
						if ( input.value.trim() ) {
							wrap?.classList.remove( 'pp-field--invalid' );
							input.classList.remove( 'pp-invalid' );
						}
					}, { once: false } );
				} );

				// ── Validar Straße y Hausnummer por separado ──────────────────
				const streetInput = document.getElementById( 'billing_address_1' );
				const houseInput  = document.getElementById( 'billing_address_2' );
				[ streetInput, houseInput ].forEach( ( input ) => {
					if ( ! input ) return;
					const wrap = input.closest( '.pp-field-wrap' );
					if ( ! input.value.trim() ) {
						valid = false;
						wrap?.classList.add( 'pp-field--invalid' );
						input.classList.add( 'pp-invalid' );
						if ( ! firstInvalid ) firstInvalid = input;
					} else {
						wrap?.classList.remove( 'pp-field--invalid' );
						input.classList.remove( 'pp-invalid' );
					}
					input.addEventListener( 'input', () => {
						if ( input.value.trim() ) {
							wrap?.classList.remove( 'pp-field--invalid' );
							input.classList.remove( 'pp-invalid' );
						}
					}, { once: false } );
				} );

				// Firmenname requerido solo cuando el tipo es "Unternehmen"
				const customerType = document.getElementById( 'billing_customer_type' )?.value;
				if ( customerType === 'empresa' ) {
					const companyInput = document.getElementById( 'billing_company' );
					const companyWrap  = companyInput?.closest( '.pp-field-wrap' );
					if ( companyInput && ! companyInput.value.trim() ) {
						valid = false;
						companyWrap?.classList.add( 'pp-field--invalid' );
						companyInput.classList.add( 'pp-invalid' );
						if ( ! firstInvalid ) firstInvalid = companyInput;
					}
				}

				// Validar checkbox de privacidad
				const privacyCheck = panel.querySelector( '#billing_privacy_check' );
				if ( privacyCheck && ! privacyCheck.checked ) {
					valid = false;
					privacyCheck.closest( '.pp-privacy-check' )?.classList.add( 'pp-privacy--invalid' );
					if ( ! firstInvalid ) firstInvalid = privacyCheck;
				} else if ( privacyCheck ) {
					privacyCheck.closest( '.pp-privacy-check' )?.classList.remove( 'pp-privacy--invalid' );
				}
				if ( privacyCheck ) {
					privacyCheck.addEventListener( 'change', () => {
						if ( privacyCheck.checked ) {
							privacyCheck.closest( '.pp-privacy-check' )?.classList.remove( 'pp-privacy--invalid' );
						}
					} );
				}

				if ( ! valid ) {
					this.showStepError( mmCheckoutData.i18n.required, panel );
					if ( firstInvalid ) {
						firstInvalid.scrollIntoView( { behavior: 'smooth', block: 'center' } );
						setTimeout( () => firstInvalid.focus(), 350 );
					}
				}
			}

			if ( step === 2 ) {
				// Buscar con name^= para cubrir tanto shipping_method[0] como input.shipping_method
				const shippingInputs = document.querySelectorAll(
					'input[name^="shipping_method"], input.shipping_method'
				);

				if ( shippingInputs.length > 0 ) {
					// Si solo hay un método, WooCommerce puede renderizarlo sin radio (hidden)
					// En ese caso consideramos el paso válido
					const visibleRadios = Array.from( shippingInputs ).filter(
						( i ) => i.type === 'radio'
					);

					if ( visibleRadios.length > 0 ) {
						const anyChecked = visibleRadios.some( ( i ) => i.checked );
						if ( ! anyChecked ) {
							valid = false;
							this.showStepError( mmCheckoutData.i18n.required, panel );
						}
					}
					// Si son hidden o no hay radios visibles = solo un método = válido automáticamente
				}
				// Si no hay ningún input de envío = producto digital o envío no necesario = válido
			}

			if ( step === 3 ) {
				// Aceptar también opciones virtuales (Klarna, Apple Pay, Google Pay)
				const paymentSelected = document.querySelector( 'input[name="payment_method"]:checked' )
					|| document.querySelector( 'input[name="mm_virtual_payment"]:checked' );
				if ( ! paymentSelected ) {
					valid = false;
					this.showStepError( mmCheckoutData.i18n.required, panel );
				}
			}

			return valid;
		}

		/* ------------------------------------------------------------------
		   Avanzar al siguiente paso
		   ------------------------------------------------------------------ */
		nextStep( fromStep ) {
			const step = fromStep || this.currentStep;
			if ( ! this.validateStep( step ) ) return;

			if ( this.currentStep < this.totalSteps ) {
				this.currentStep++;
				this.renderStep( this.currentStep );
			}
		}

		/* ------------------------------------------------------------------
		   Retroceder al paso anterior
		   ------------------------------------------------------------------ */
		prevStep( targetStep ) {
			if ( targetStep !== undefined ) {
				this.currentStep = targetStep;
			} else if ( this.currentStep > 1 ) {
				this.currentStep--;
			}
			this.renderStep( this.currentStep );
		}

		/* ------------------------------------------------------------------
		   Botones de navegación
		   ------------------------------------------------------------------ */
		bindNavigationButtons() {
			document.querySelectorAll( '.mm-btn-next' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					const fromStep = parseInt( btn.dataset.step );
					this.nextStep( fromStep );
				} );
			} );

			document.querySelectorAll( '.mm-btn-prev' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					const target = btn.dataset.targetStep ? parseInt( btn.dataset.targetStep ) : undefined;
					this.prevStep( target );
				} );
			} );
		}

		/* ------------------------------------------------------------------
		   Clic en pasos completados de la barra de progreso
		   ------------------------------------------------------------------ */
		bindProgressBarClicks() {
			this.progressSteps.forEach( ( el ) => {
				el.addEventListener( 'click', () => {
					if ( el.classList.contains( 'mm-step--completed' ) ) {
						const stepNum = parseInt( el.dataset.step );
						this.currentStep = stepNum;
						this.renderStep( stepNum );
					}
				} );
			} );
		}

		/* ------------------------------------------------------------------
		   Botón principal del sidebar
		   ------------------------------------------------------------------ */
		bindSidebarSubmit() {
			if ( ! this.sidebarBtn ) return;

			this.sidebarBtn.addEventListener( 'click', () => {
				if ( this.currentStep < this.totalSteps ) {
					this.nextStep();
					return;
				}

				// Paso 4: usar el mismo handleStep4Submit
				this.handleStep4Submit();
			} );

			// Actualizar texto del botón según el paso
			this.updateSidebarBtnText();
		}

		/* ------------------------------------------------------------------
		   Botón "Jetzt bestellen" dentro del paso 4
		   ------------------------------------------------------------------ */
		bindStep4Submit() {
			const actionArea = document.getElementById( 'mm-step4-action-area' );
			if ( ! actionArea ) return;

			// Listener en el área de acción (captura clicks en botón estándar e imagen PayPal)
			actionArea.addEventListener( 'click', ( e ) => {
				if ( e.target.closest( '#mm-step4-submit' ) || e.target.closest( '#mm-step4-paypal' ) ) {
					e.preventDefault();
					e.stopPropagation();
					this.handleStep4Submit();
				}
			} );

			// Listener directo en el botón PayPal como fallback
			// (por si el botón se renderiza después del bind o tiene pointer-events bloqueados)
			const bindPaypalBtn = () => {
				const paypalBtn = document.getElementById( 'mm-step4-paypal' );
				if ( ! paypalBtn || paypalBtn._mmBound ) return;
				paypalBtn._mmBound = true;
				paypalBtn.style.setProperty( 'pointer-events', 'auto', 'important' );
				paypalBtn.style.setProperty( 'cursor', 'pointer', 'important' );
				// La imagen interior no debe capturar eventos
				const img = paypalBtn.querySelector( 'img' );
				if ( img ) img.style.setProperty( 'pointer-events', 'none', 'important' );
				paypalBtn.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					this.handleStep4Submit();
				} );
			};

			// Intentar bind inmediato y también cuando el paso 4 se renderiza
			bindPaypalBtn();
			document.addEventListener( 'mm_step4_rendered', bindPaypalBtn );
			// MutationObserver como último recurso si el botón aparece tarde en el DOM
			const obs = new MutationObserver( () => bindPaypalBtn() );
			obs.observe( actionArea, { childList: true, subtree: true, attributes: true } );
		}

		handleStep4Submit() {
			if ( this.step4Terms && ! this.step4Terms.checked ) {
				this.showStepError( mmCheckoutData.i18n.termsError );
				this.step4Terms.focus();
				return;
			}
			const wcTerms = document.getElementById( 'terms' );
			if ( wcTerms ) wcTerms.checked = true;
			if ( this.termsCheck ) this.termsCheck.checked = true;

			// El panel de pago (paso 3) ya está accesible off-screen con dimensiones
			// reales gracias a renderStep — no se necesita ningún hack adicional.

			const mid      = ( this._step4PaymentMethod || '' ).toLowerCase();
			const isPayPal = /ppcp|paypal/i.test( mid );
			const isKlarna = /mm_klarna|stripe_klarna/i.test( mid );

			// Inyectar campos shipping en el formulario antes de enviar.
			// billing_address_1 ya contiene "Straße Hausnummer" combinados por bindStreetSync.
			const injectShipping = () => {
				const form = document.querySelector( 'form.checkout' );
				if ( ! form ) return;
				const v = ( id ) => ( document.getElementById( id )?.value || '' ).trim();
				// billing_address_1 = Straße, billing_address_2 = Hausnummer
				// Se mapean directamente a shipping sin transformación
				const map = {
					shipping_first_name: v( 'billing_first_name' ),
					shipping_last_name:  v( 'billing_last_name' ),
					shipping_address_1:  v( 'billing_address_1' ),
					shipping_address_2:  v( 'billing_address_2' ),
					shipping_city:       v( 'billing_city' ),
					shipping_postcode:   v( 'billing_postcode' ),
					shipping_country:    v( 'billing_country' ) || 'DE',
				};
				Object.entries( map ).forEach( ( [ name, value ] ) => {
					let el = form.querySelector( `input[name="${ name }"][data-pp-sync]` );
					if ( ! el ) {
						el = Object.assign( document.createElement( 'input' ), { type: 'hidden', name } );
						el.setAttribute( 'data-pp-sync', '1' );
						form.appendChild( el );
					}
					el.value = value;
				} );
			};

			const doSubmit = () => {
				injectShipping();
				const placeOrder = document.getElementById( 'place_order' );
				if ( placeOrder ) placeOrder.click();
				else document.querySelector( 'form.checkout' )?.submit();
			};

			if ( isPayPal ) {
				// PayPal PPCP crea la orden leyendo la dirección de la sesión de WC.
				// Esperar a que update_checkout (async) complete antes de proceder,
				// de lo contrario la sesión aún no tiene la dirección y PayPal falla.
				let submitted = false;
				const onUpdated = () => {
					if ( submitted ) return;
					submitted = true;
					doSubmit();
				};
				$( document.body ).one( 'updated_checkout', onUpdated );
				$( document.body ).trigger( 'update_checkout' );
				// Fallback: si updated_checkout no dispara en 4s, proceder igualmente
				setTimeout( () => {
					$( document.body ).off( 'updated_checkout', onUpdated );
					if ( ! submitted ) { submitted = true; doSubmit(); }
				}, 4000 );
			} else if ( isKlarna ) {
				if ( mid.includes( 'stripe_klarna' ) ) {
					// Gateway Klarna separado: seleccionar radio y enviar — Stripe redirige a Klarna
					const klarnaGwRadio = document.querySelector( 'input[name="payment_method"][value="stripe_klarna"]' );
					if ( klarnaGwRadio ) { klarnaGwRadio.checked = true; $( klarnaGwRadio ).trigger( 'change' ); }
					setTimeout( doSubmit, 400 );
				} else {
					// Klarna via Stripe UPE: activar pestaña Klarna en el UPE con reintentos
					const stripeRadio = document.querySelector( 'input[name="payment_method"][value="stripe"]' );
					if ( stripeRadio ) { stripeRadio.checked = true; $( stripeRadio ).trigger( 'change' ); }
					let activated = false;
					const tryActivate = ( attempt = 0 ) => {
						if ( activated ) return;
						activated = this._activateKlarnaTab();
						if ( ! activated && attempt < 5 ) setTimeout( () => tryActivate( attempt + 1 ), 200 );
					};
					tryActivate();
					setTimeout( doSubmit, 1500 );
				}
			} else {
				doSubmit();
			}
		}

		/* ------------------------------------------------------------------
		   Footer móvil fijo: Total + botón de acción (creado en el body)
		   ------------------------------------------------------------------ */
		createMobileFooter() {
			if ( window.innerWidth > 768 ) return;

			const footer = document.createElement( 'div' );
			footer.id = 'mm-mobile-footer';
			footer.className = 'mm-mobile-footer';
			footer.innerHTML = `
				<div id="mm-mobile-drawer" class="mm-mobile-drawer">
					<div class="mm-mobile-drawer__inner">
						<div class="mm-mobile-drawer__items" id="mm-drawer-items"></div>
						<div class="mm-mobile-drawer__totals" id="mm-drawer-totals"></div>
					</div>
				</div>
				<button type="button" id="mm-drawer-toggle" class="mm-mobile-footer__toggle" aria-expanded="false">
					<svg class="mm-footer-toggle-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
					<span class="mm-mobile-footer__toggle-label">Bestellung anzeigen</span>
					<span class="mm-mobile-footer__toggle-price" id="mm-mobile-total">—</span>
				</button>
				<button type="button" id="mm-mobile-btn" class="mm-mobile-footer__btn">
					Weiter zur Lieferung
				</button>
			`;
			document.body.appendChild( footer );

			// Toggle del drawer
			const drawerEl  = document.getElementById( 'mm-mobile-drawer' );
			const toggleBtn = document.getElementById( 'mm-drawer-toggle' );
			if ( toggleBtn && drawerEl ) {
				toggleBtn.addEventListener( 'click', () => {
					const isOpen = drawerEl.classList.contains( 'mm-mobile-drawer--open' );
					drawerEl.classList.toggle( 'mm-mobile-drawer--open', ! isOpen );
					toggleBtn.classList.toggle( 'mm-toggle--open', ! isOpen );
					toggleBtn.setAttribute( 'aria-expanded', String( ! isOpen ) );
					const label = toggleBtn.querySelector( '.mm-mobile-footer__toggle-label' );
					if ( label ) label.textContent = isOpen ? 'Bestellung anzeigen' : 'Bestellung ausblenden';
				} );
			}

			this.mobileFooter  = footer;
			this.mobileTotalEl = document.getElementById( 'mm-mobile-total' );
			this.mobileBtn     = document.getElementById( 'mm-mobile-btn' );

			// Botón avanzar
			this.mobileBtn.addEventListener( 'click', () => {
				if ( this.currentStep < this.totalSteps ) {
					this.nextStep();
				} else {
					this.handleStep4Submit();
				}
			} );

			// Botón Express Pay (Apple Pay / Google Pay)
			this.updateMobileFooter();
		}

		/* Comprueba si los campos mínimos de dirección están rellenos */
		isAddressReady() {
			const required = [ 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_address_1', 'billing_address_2' ];
			return required.every( ( id ) => ( document.getElementById( id )?.value || '' ).trim() !== '' );
		}

		updateMobileFooter() {
			if ( ! this.mobileFooter ) return;

			const isStep4 = this.currentStep === 4;

			// En paso 4 el step4-footer toma el relevo → ocultar footer móvil
			this.mobileFooter.style.display = isStep4 ? 'none' : 'block';
			if ( isStep4 ) return;

			// Sincronizar contenido del drawer con el sidebar
			const drawerItems  = document.getElementById( 'mm-drawer-items' );
			const drawerTotals = document.getElementById( 'mm-drawer-totals' );
			const sidebarItems  = document.getElementById( 'mm-sidebar-items' );
			const sidebarTotals = document.getElementById( 'mm-sidebar-totals' );
			if ( drawerItems  && sidebarItems  ) drawerItems.innerHTML  = sidebarItems.innerHTML;
			if ( drawerTotals && sidebarTotals ) drawerTotals.innerHTML = sidebarTotals.innerHTML;

			// Actualizar precio total en el toggle
			if ( this.mobileTotalEl ) {
				const totalEl = document.querySelector( '#mm-sidebar-totals .mm-summary-row--total strong:last-child' );
				if ( totalEl ) this.mobileTotalEl.innerHTML = totalEl.innerHTML;
			}

			// Actualizar texto del botón avanzar
			if ( this.mobileBtn ) {
				const texts = {
					1: 'Weiter zur Lieferung',
					2: 'Weiter zur Zahlung',
					3: 'Bestellung prüfen',
				};
				this.mobileBtn.textContent = texts[ this.currentStep ] || 'Weiter';
			}

		}

		updateSidebarBtnText() {
			if ( ! this.sidebarBtn ) return;
			const texts = {
				1: 'Weiter zur Lieferung',
				2: 'Weiter zur Zahlung',
				3: 'Bestellung prüfen',
				4: 'Jetzt bestellen',
			};
			const text = texts[ this.currentStep ] || 'Continuar';
			this.sidebarBtn.textContent = text;
		}

		/* ------------------------------------------------------------------
		   Mostrar dirección en paso 2 y 4
		   Usa billing_address_1 (hidden, ya combinado) para mostrar la dirección.
		   ------------------------------------------------------------------ */
		updateAddressDisplay() {
			const parts = [];
			const get   = ( id ) => ( document.getElementById( id )?.value || '' ).trim();

			const addr1    = get( 'billing_address_1' );
			const addr2    = get( 'billing_address_2' );
			const postcode = get( 'billing_postcode' );
			const city     = get( 'billing_city' );

			if ( addr1 ) parts.push( addr1 );
			if ( addr2 ) parts.push( addr2 );
			if ( postcode || city ) parts.push( [ postcode, city ].filter( Boolean ).join( ' ' ) );

			const formatted = parts.join( ', ' );

			document.querySelectorAll( '#mm-address-display, #mm-review-address' ).forEach( ( el ) => {
				el.textContent = formatted || '—';
			} );
		}

		/* ------------------------------------------------------------------
		   Actualizar secciones de revisión en paso 4
		   ------------------------------------------------------------------ */
		updateReviewSections() {
			if ( this.currentStep !== 4 ) return;

			// Método de envío elegido
			const shippingChecked = document.querySelector( 'input[name^="shipping_method"]:checked, input.shipping_method:checked' );
			const reviewShipping  = document.getElementById( 'mm-review-shipping' );
			if ( reviewShipping ) {
				if ( shippingChecked ) {
					const label = shippingChecked.closest( '.mm-shipping-option' )
						?.querySelector( '.mm-shipping-option__name' )?.textContent
						|| document.querySelector( `label[for="${ shippingChecked.id }"]` )?.textContent?.trim()
						|| shippingChecked.value;
					reviewShipping.textContent = label;
				} else {
					// Un solo método sin radio visible — buscar el label asociado
					const hiddenShipping = document.querySelector( 'input[name^="shipping_method"]' );
					if ( hiddenShipping ) {
						const labelEl = document.querySelector( `label[for="${ hiddenShipping.id }"]` );
						const labelText = labelEl?.textContent?.trim();
						reviewShipping.textContent = labelText || hiddenShipping.value || '—';
					} else {
						reviewShipping.textContent = '—';
					}
				}
			}

			// Método de pago elegido
			const paymentChecked = document.querySelector( 'input[name="payment_method"]:checked' );
			const reviewPayment  = document.getElementById( 'mm-review-payment' );
			if ( reviewPayment && paymentChecked ) {
				const label = document.querySelector( `label[for="${ paymentChecked.id }"]` )?.textContent?.trim()
					|| paymentChecked.value;
				reviewPayment.textContent = label;
			}

			this.updateAddressDisplay();
		}

		/* ------------------------------------------------------------------
		   Cambio de método de envío → actualizar clases y totales
		   ------------------------------------------------------------------ */
		bindShippingMethodChange() {
			document.addEventListener( 'change', ( e ) => {
				if ( e.target.classList.contains( 'shipping_method' ) || e.target.name?.startsWith( 'shipping_method' ) ) {
					document.querySelectorAll( '.mm-shipping-option' ).forEach( ( opt ) => {
						opt.classList.remove( 'mm-shipping-option--selected' );
					} );
					e.target.closest( '.mm-shipping-option' )?.classList.add( 'mm-shipping-option--selected' );

					// Notificar a WooCommerce para actualizar totales
					$( document.body ).trigger( 'update_checkout' );
				}
			} );
		}

		/* ------------------------------------------------------------------
		   Toggle de factura de empresa
		   ------------------------------------------------------------------ */
		bindEmpresaToggle() {
			const checkbox = document.querySelector( '#billing_empresa' );
			const fields   = document.querySelector( '.mm-empresa-fields' );
			if ( ! checkbox || ! fields ) return;

			const toggle = () => {
				const show = checkbox.checked;
				fields.style.display  = show ? 'block' : 'none';
				fields.setAttribute( 'aria-hidden', ! show );
				fields.classList.toggle( 'mm-empresa-fields--visible', show );
			};

			checkbox.addEventListener( 'change', toggle );
			toggle();
		}

		/* ------------------------------------------------------------------
		   Cupón — AJAX
		   ------------------------------------------------------------------ */
		bindCoupon() {
			const btn   = document.getElementById( 'mm-apply-coupon' );
			const input = document.getElementById( 'mm-coupon-code' );
			const msg   = document.querySelector( '.mm-coupon-message' );
			if ( ! btn ) return;

			const apply = () => {
				const code = input?.value.trim();
				if ( ! code ) {
					this.setCouponMsg( msg, mmCheckoutData.i18n.couponEmpty, 'error' );
					return;
				}

				btn.disabled = true;
				btn.textContent = '...';

				$.post( mmCheckoutData.ajaxUrl, {
					action:      'mm_apply_coupon',
					nonce:       mmCheckoutData.nonce,
					coupon_code: code,
				}, ( res ) => {
					btn.disabled    = false;
					btn.textContent = 'Añadir';

					if ( res.success ) {
						this.setCouponMsg( msg, res.data.message, 'ok' );
						if ( input ) input.value = '';
						$( document.body ).trigger( 'update_checkout' );
					} else {
						this.setCouponMsg( msg, res.data.message, 'error' );
					}
				} );
			};

			btn.addEventListener( 'click', apply );
			input?.addEventListener( 'keydown', ( e ) => { if ( e.key === 'Enter' ) { e.preventDefault(); apply(); } } );
		}

		/* ------------------------------------------------------------------
		   Quitar cupón — AJAX
		   ------------------------------------------------------------------ */
		bindCouponRemove() {
			document.addEventListener( 'click', ( e ) => {
				if ( ! e.target.classList.contains( 'mm-coupon-remove' ) ) return;
				const code = e.target.dataset.coupon;

				$.post( mmCheckoutData.ajaxUrl, {
					action:      'mm_remove_coupon',
					nonce:       mmCheckoutData.nonce,
					coupon_code: code,
				}, ( res ) => {
					if ( res.success ) {
						$( document.body ).trigger( 'update_checkout' );
					}
				} );
			} );
		}

		/* ------------------------------------------------------------------
		   Controles de cantidad y eliminar en el sidebar del checkout
		   ------------------------------------------------------------------ */
		bindCartControls() {
			function refreshSidebar( data ) {
				if ( data.items_html !== undefined ) {
					[ 'mm-sidebar-items', 'mm-step4-items' ].forEach( ( id ) => {
						const el = document.getElementById( id );
						if ( el ) el.innerHTML = data.items_html;
					} );
				}
				if ( data.totals_html !== undefined ) {
					[ 'mm-sidebar-totals', 'mm-step4-totals' ].forEach( ( id ) => {
						const el = document.getElementById( id );
						if ( el ) el.innerHTML = data.totals_html;
					} );
				}
				$( document.body ).trigger( 'wc_fragment_refresh' );
				$( document.body ).trigger( 'update_checkout' );
			}

			function postCart( action, key, qty ) {
				return $.post( mmCheckoutData.ajaxUrl, {
					action:   action,
					nonce:    mmCheckoutData.nonce,
					cart_key: key,
					quantity: qty || 1,
				} );
			}

			$( document ).on( 'click', '.mm-qty-minus, .mm-qty-plus', function () {
				const $btn  = $( this );
				const key   = $btn.data( 'key' );
				const $item = $btn.closest( '.mm-order-item' );
				const $val  = $item.find( '.mm-qty-value' );
				let qty     = parseInt( $val.text(), 10 ) || 1;

				if ( $btn.hasClass( 'mm-qty-minus' ) ) {
					qty = Math.max( 1, qty - 1 );
				} else {
					qty = qty + 1;
				}

				$item.css( 'opacity', '.5' );

				postCart( 'mm_update_cart_qty', key, qty ).done( function ( res ) {
					$item.css( 'opacity', '' );
					if ( res.success ) refreshSidebar( res.data );
				} ).fail( function () {
					$item.css( 'opacity', '' );
				} );
			} );

			$( document ).on( 'click', '.mm-item-remove', function () {
				const key   = $( this ).data( 'key' );
				const $item = $( this ).closest( '.mm-order-item' );
				$item.css( 'opacity', '.4' );

				postCart( 'mm_remove_cart_item', key ).done( function ( res ) {
					if ( res.success ) {
						if ( res.data.empty ) {
							window.location.reload();
						} else {
							refreshSidebar( res.data );
						}
					}
				} ).fail( function () {
					$item.css( 'opacity', '' );
				} );
			} );
		}

		/* ------------------------------------------------------------------
		   Escuchar actualizaciones de WooCommerce (AJAX checkout)
		   ------------------------------------------------------------------ */
		bindWooCommerceUpdates() {
			// Guardar método de pago en tiempo real cuando el usuario lo cambia en paso 3
			document.addEventListener( 'change', ( e ) => {
				if ( e.target.name === 'payment_method' && e.target.checked ) {
					this._step4PaymentMethod = e.target.value;
				}
			} );

			$( document.body ).on( 'updated_checkout', () => {
				// Guardar selección antes de que WC re-renderice la lista
				const savedVirtual  = /^mm_(klarna|apple_pay|google_pay)$/.test( this._step4PaymentMethod )
					? this._step4PaymentMethod : null;
				const savedKlarnaGw = this._step4PaymentMethod === 'stripe_klarna' ? 'stripe_klarna' : null;

				this.rebindShippingOptions();
				this.updateSidebarBtnText();
				this.updateMobileFooter();

				// Re-inyectar opciones de pago extra (WC las borra al re-renderizar)
				this.injectKlarnaOption();
				this.injectApplePayOption();

				// WooCommerce oculta cada .payment_box hasta que se hace click en
				// su radio. En el paso 3, forzamos la selección para que Stripe
				// (y otros gateways) monten sus elementos en el contenedor ya visible.
				if ( this.currentStep === 3 ) {
					const $methods  = $( 'input[name="payment_method"]' );
					const $selected = $methods.filter( ':checked' );
					const $target   = $selected.length ? $selected : $methods.first();
					if ( $target.length ) {
						$target.prop( 'checked', true ).trigger( 'click' );
					}
					// Restaurar selección si el usuario la tenía activa
					if ( savedVirtual ) {
						this._step4PaymentMethod = savedVirtual;
						const vRadio = document.querySelector(
							`input[name="mm_virtual_payment"][value="${ savedVirtual }"]`
						);
						if ( vRadio ) {
							vRadio.checked = true;
							this._selectVirtualPayment( vRadio.closest( '.wc_payment_method' ) );
						}
					} else if ( savedKlarnaGw ) {
						// Restaurar gateway Klarna separado (radio real de WC)
						this._step4PaymentMethod = savedKlarnaGw;
						const klarnaGwRadio = document.querySelector( 'input[name="payment_method"][value="stripe_klarna"]' );
						if ( klarnaGwRadio ) { klarnaGwRadio.checked = true; $( klarnaGwRadio ).trigger( 'change' ); }
					}
				}

				// Re-renderizar botón de paso 4. No re-renderizar si el payment_box de tarjeta
				// ya está en el paso 4 (evita bucle: renderStep4Action → mm_step_3_visible
				// → triggerStripeInit → update_checkout → renderStep4Action → ...).
				if ( this.currentStep === 4 && ! this.borrowedPaymentBox ) {
					if ( this._step4PaymentMethod ) {
						const savedRadio = document.querySelector(
							`input[name="payment_method"][value="${ this._step4PaymentMethod }"]`
						);
						if ( savedRadio && ! savedRadio.checked ) savedRadio.checked = true;
					}
					setTimeout( () => this.renderStep4Action(), 100 );
				}
			} );

			$( document.body ).on( 'checkout_error', () => {
				const wcErrors = document.querySelector( '.woocommerce-error' );
				if ( ! wcErrors ) return;

				// Solo volver al paso 1 si el error es de validación de dirección
				// (steps 1-2). Los errores de pago (Stripe decline, PayPal fail)
				// deben quedarse en el paso 3 para que el usuario pueda reintentar.
				if ( this.currentStep <= 2 ) {
					this.currentStep = 1;
					this.renderStep( 1 );
				} else {
					// Error de pago: volver al paso 3 para reintentar
					if ( this.currentStep === 4 ) {
						this.currentStep = 3;
						this.renderStep( 3 );
					}
					// Mover el banner de error al panel activo si no está visible
					const activePanel = document.querySelector( `[data-step-content="${ this.currentStep }"]` );
					if ( activePanel && ! activePanel.contains( wcErrors ) ) {
						activePanel.prepend( wcErrors );
					}
				}
				wcErrors.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			} );
		}

		rebindShippingOptions() {
			document.querySelectorAll( '.mm-shipping-option input.shipping_method' ).forEach( ( radio ) => {
				const option = radio.closest( '.mm-shipping-option' );
				if ( ! option ) return;
				option.classList.toggle( 'mm-shipping-option--selected', radio.checked );
			} );
		}

		/* ------------------------------------------------------------------
		   Utilidades
		   ------------------------------------------------------------------ */
		isValidEmail( email ) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
		}

		setCouponMsg( el, text, type ) {
			if ( ! el ) return;
			el.textContent  = text;
			el.className    = 'mm-coupon-message mm-coupon-message--' + type;
		}

		/* ------------------------------------------------------------------
		   Inyectar Klarna como opción separada (se procesa via Stripe UPE)
		   ------------------------------------------------------------------ */
		injectKlarnaOption() {
			const ul = document.querySelector( '.mm-payment-wrapper .wc_payment_methods' );
			if ( ! ul || ul.querySelector( '.mm-klarna-option' ) ) return;

			// Si stripe_klarna existe como gateway separado, solo aplicar estilo custom y salir
			const klarnaGwLi = ul.querySelector( '.payment_method_stripe_klarna' );
			if ( klarnaGwLi ) {
				klarnaGwLi.classList.add( 'mm-klarna-option' );
				const lbl = klarnaGwLi.querySelector( 'label' );
				if ( lbl && ! lbl.querySelector( '.mm-klarna-logo' ) ) {
					const logoSpan = document.createElement( 'span' );
					logoSpan.innerHTML = `<svg viewBox="0 0 1000 660" xmlns="http://www.w3.org/2000/svg" class="mm-applepay-logo mm-klarna-logo" aria-hidden="true" style="width:72px;height:auto"><rect width="1000" height="660" rx="60" fill="#FFB3C7"/><text x="500" y="430" font-size="360" font-family="Arial,sans-serif" font-weight="900" fill="#000" text-anchor="middle">K</text></svg>`;
					lbl.appendChild( logoSpan );
				}
				return;
			}

			// Buscar radio de Stripe por varios selectores posibles
			const stripeRadio = ul.querySelector( 'input[name="payment_method"][value="stripe"]' )
				|| ul.querySelector( 'input[name="payment_method"][value="stripe_cc"]' )
				|| ul.querySelector( '.payment_method_stripe input[name="payment_method"]' );
			if ( ! stripeRadio ) return;

			// Renombrar el label de Stripe si aún dice "Zahlungsoptionen"
			const stripeLi = stripeRadio.closest( '.wc_payment_method' );
			const stripeLbl = stripeLi?.querySelector( 'label' );
			if ( stripeLbl ) {
				for ( const node of stripeLbl.childNodes ) {
					if ( node.nodeType === Node.TEXT_NODE && node.textContent.trim() ) {
						if ( /zahlungsoptionen|stripe|kreditkarte/i.test( node.textContent ) ) {
							node.textContent = 'Kredit- / Debitkarte ';
						}
						break;
					}
				}
			}

			const li = document.createElement( 'li' );
			li.className = 'wc_payment_method mm-klarna-option';
			li.innerHTML = `
				<input type="radio" id="payment_method_mm_klarna" name="mm_virtual_payment" value="mm_klarna">
				<label for="payment_method_mm_klarna" class="mm-applepay-label">
					Klarna
					<svg viewBox="0 0 1000 660" xmlns="http://www.w3.org/2000/svg" class="mm-applepay-logo mm-klarna-logo" aria-hidden="true" style="width:72px;height:auto"><rect width="1000" height="660" rx="60" fill="#FFB3C7"/><text x="500" y="430" font-size="360" font-family="Arial,sans-serif" font-weight="900" fill="#000" text-anchor="middle">K</text></svg>
				</label>
			`;

			if ( stripeLi?.nextSibling ) {
				ul.insertBefore( li, stripeLi.nextSibling );
			} else {
				ul.appendChild( li );
			}

			const klarnaRadio = li.querySelector( 'input' );
			klarnaRadio.addEventListener( 'change', () => {
				this._step4PaymentMethod = 'mm_klarna';
				this._selectVirtualPayment( li );
			} );
			li.addEventListener( 'click', () => {
				if ( ! klarnaRadio.checked ) {
					klarnaRadio.checked = true;
					klarnaRadio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			} );
		}

		/* ------------------------------------------------------------------
		   Selección visual de método virtual sin disparar update_checkout de WC
		   ------------------------------------------------------------------ */
		_selectVirtualPayment( selectedLi ) {
			// Desmarcar radios reales sin disparar eventos (para que initPaymentUI no los muestre como seleccionados)
			document.querySelectorAll( '.mm-payment-wrapper input[name="payment_method"]' ).forEach( ( r ) => {
				r.checked = false;
			} );
			document.querySelectorAll( '.mm-payment-wrapper .wc_payment_method' ).forEach( ( m ) => {
				m.classList.remove( 'mm-payment--selected' );
			} );
			selectedLi?.classList.add( 'mm-payment--selected' );
		}

		/* ------------------------------------------------------------------
		   Intentar activar la pestaña Klarna en el Stripe UPE
		   ------------------------------------------------------------------ */
		_activateKlarnaTab() {
			const selectors = [ '[class*="Tab"]', '[role="tab"]', '[role="radio"]', 'label', 'button' ];
			for ( const sel of selectors ) {
				for ( const el of document.querySelectorAll( sel ) ) {
					const txt = ( el.textContent || '' ).toLowerCase();
					const lbl = ( el.getAttribute( 'aria-label' ) || '' ).toLowerCase();
					const ttl = ( el.getAttribute( 'title' ) || '' ).toLowerCase();
					if ( txt.includes( 'klarna' ) || lbl.includes( 'klarna' ) || ttl.includes( 'klarna' ) ) {
						el.click();
						return true;
					}
				}
			}
			return false;
		}

		/* ------------------------------------------------------------------
		   Apple Pay y Google Pay — solo en dispositivos que los soportan
		   ------------------------------------------------------------------ */
		injectApplePayOption() {
			const ul = document.querySelector( '.mm-payment-wrapper .wc_payment_methods' );
			if ( ! ul || ul.querySelector( '.mm-applepay-option' ) ) return;

			const hasApplePay  = typeof ApplePaySession !== 'undefined';
			const hasGooglePay = ! hasApplePay && typeof PaymentRequest !== 'undefined';

			const appleLogoSvg = `<svg viewBox="0 0 640 400" xmlns="http://www.w3.org/2000/svg" class="mm-applepay-logo" aria-hidden="true"><rect width="640" height="400" rx="40" fill="#000"/><path d="M227 130c-7 8-18 15-29 14-1-11 4-23 11-30 7-8 19-15 29-14 1 11-4 22-11 30zm11 17c-16-1-30 9-38 9-8 0-20-8-33-8-17 0-33 10-41 25-18 30-5 75 13 100 8 12 19 25 32 25 13 0 18-8 33-8 16 0 20 8 33 8 14 0 25-14 33-26 5-8 8-12 13-21-35-13-40-62-6-81-10-14-27-22-39-23z" fill="#fff"/><path d="M374 116h-10v109h10V116zm-24 88a25 25 0 01-25-25 25 25 0 0125-25 25 25 0 0125 25 25 25 0 01-25 25zm0-60a35 35 0 00-35 35 35 35 0 0035 35 35 35 0 0035-35 35 35 0 00-35-35zM420 139l-2 7h-10l-2-7h-11l13 37h10l13-37h-11zm50 27l-8-27h-11l13 37h10l14-37h-11l-7 27zm60-27l-13 37h11l2-7h13l2 7h11l-13-37h-13zm7 10l4 12h-8l4-12zm50-10v27l-13-27h-11v37h10V149l14 27h10v-37h-10zm53 0v10h-10v27h-10v-27h-10v-10h30z" fill="#fff"/></svg>`;
			const googleLogoSvg = `<svg viewBox="0 0 80 24" xmlns="http://www.w3.org/2000/svg" class="mm-applepay-logo" aria-hidden="true" style="width:64px"><text y="18" font-size="16" fill="#1A73E8" font-family="sans-serif" font-weight="700">G Pay</text></svg>`;

			const makeExpressLi = ( id, value, label, logoSvg, extraClass ) => {
				const li = document.createElement( 'li' );
				li.className = 'wc_payment_method mm-applepay-option' + ( extraClass ? ' ' + extraClass : '' );
				li.innerHTML = `
					<input type="radio" id="${ id }" name="mm_virtual_payment" value="${ value }">
					<label for="${ id }" class="mm-applepay-label">
						${ label } ${ logoSvg }
					</label>
				`;
				ul.appendChild( li );
				const radio = li.querySelector( 'input' );
				radio.addEventListener( 'change', () => {
					this._step4PaymentMethod = value;
					this._selectVirtualPayment( li );
				} );
				li.addEventListener( 'click', () => {
					if ( ! radio.checked ) { radio.checked = true; radio.dispatchEvent( new Event( 'change', { bubbles: true } ) ); }
				} );
			};

			if ( hasApplePay ) {
				makeExpressLi( 'payment_method_mm_apple_pay', 'mm_apple_pay', 'Apple Pay', appleLogoSvg, '' );
			}
			if ( hasGooglePay ) {
				makeExpressLi( 'payment_method_mm_google_pay', 'mm_google_pay', 'Google Pay', googleLogoSvg, 'mm-googlepay-option' );
			}
		}

		/* ------------------------------------------------------------------
		   Renderizar el botón de pago dinámico en el paso 4
		   ------------------------------------------------------------------ */
		renderStep4Action() {
			if ( this._renderingStep4 ) return;
			this._renderingStep4 = true;

			// Limpiar inline visibility por si quedó atascado de una llamada anterior
			document.getElementById( 'mm-step4-action-area' )?.style.removeProperty( 'visibility' );
			document.getElementById( 'mm-ppcp-pool' )?.style.removeProperty( 'visibility' );

			const mid = ( this._step4PaymentMethod || '' ).toLowerCase();
			const actionArea = document.getElementById( 'mm-step4-action-area' );
			const defaultBtn = document.getElementById( 'mm-step4-submit' );
			const paypalBtn  = document.getElementById( 'mm-step4-paypal' );

			if ( ! defaultBtn || ! actionArea ) { this._renderingStep4 = false; return; }

			// Limpiar wrapper PayPal previo
			actionArea.querySelectorAll( '.mm-step4-paypal-box' ).forEach( ( el ) => el.remove() );
			this.restoreStep4Borrows();

			const reviewPaymentText = ( document.getElementById( 'mm-review-payment' )?.textContent || '' ).toLowerCase();
			const isPayPal   = /ppcp|paypal/i.test( mid ) || /paypal/i.test( reviewPaymentText );
			const isApplePay = /mm_apple_pay|apple.*pay/i.test( mid );
			const isGooglePay = /mm_google_pay|google.*pay/i.test( mid );
			const isKlarna   = /mm_klarna|stripe_klarna/i.test( mid );
			const isExpress  = isApplePay || isGooglePay;

			const arrowSvg = `<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="9 18 15 12 9 6"/></svg>`;

			if ( isExpress ) {
				// ── Apple Pay / Google Pay via Stripe Payment Request Button ──────
				const prbId  = isApplePay ? 'wc-stripe-payment-request-button' : 'wc-stripe-express-checkout-element';
				const prbAlt = isApplePay ? 'wc-stripe-express-checkout-element' : 'wc-stripe-payment-request-button';
				const prb    = document.getElementById( prbId ) || document.getElementById( prbAlt );
				const arrowSvgExpress = `<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="9 18 15 12 9 6"/></svg>`;

				if ( prb && prb.children.length ) {
					// Stripe ya ha montado el botón → moverlo al paso 4
					prb.removeAttribute( 'aria-hidden' );
					prb.style.cssText = 'position:static!important;top:auto!important;left:auto!important;width:100%!important;height:auto!important;min-height:48px!important;pointer-events:auto!important;z-index:auto!important;overflow:visible!important;';
					const wrap = document.createElement( 'div' );
					wrap.className = 'mm-step4-express-wrap';
					wrap.appendChild( prb );
					actionArea.insertBefore( wrap, defaultBtn );
					this.borrowedExpressEl       = prb;
					this.borrowedExpressElParent = null;
					defaultBtn.style.setProperty( 'display', 'none', 'important' );
					if ( paypalBtn ) paypalBtn.style.setProperty( 'display', 'none', 'important' );
					// Layout columna para ancho completo
					actionArea.closest( '.mm-step-nav' )?.classList.add( 'mm-step-nav--paypal' );
					// Forzar re-render de Stripe
					window.dispatchEvent( new Event( 'resize' ) );
					setTimeout( () => window.dispatchEvent( new Event( 'resize' ) ), 300 );
				} else {
					// Stripe aún no ha renderizado → botón de texto como fallback
					defaultBtn.style.display = '';
					defaultBtn.className     = 'mm-btn-primary mm-btn-checkout mm-btn-checkout--step4';
					defaultBtn.innerHTML     = `${ isApplePay ? 'Mit Apple Pay bezahlen' : 'Mit Google Pay bezahlen' } ${ arrowSvgExpress }`;
					defaultBtn.removeAttribute( 'aria-label' );
					if ( paypalBtn ) paypalBtn.style.setProperty( 'display', 'none', 'important' );
					// Reintentar cuando Stripe termine de renderizar
					setTimeout( () => {
						if ( this.currentStep === 4 && ! this._renderingStep4 ) this.renderStep4Action();
					}, 1500 );
				}

			} else if ( isPayPal ) {
				// ── PayPal: usar el botón estático con logo oficial ────────────
				if ( paypalBtn ) {
					paypalBtn.innerHTML = `<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAxcHgiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAxMDEgMzIiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaW5ZTWluIG1lZXQiIHhtbG5zPSJodHRwOiYjeDJGOyYjeDJGO3d3dy53My5vcmcmI3gyRjsyMDAwJiN4MkY7c3ZnIj48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDEyLjIzNyAyLjggTCA0LjQzNyAyLjggQyAzLjkzNyAyLjggMy40MzcgMy4yIDMuMzM3IDMuNyBMIDAuMjM3IDIzLjcgQyAwLjEzNyAyNC4xIDAuNDM3IDI0LjQgMC44MzcgMjQuNCBMIDQuNTM3IDI0LjQgQyA1LjAzNyAyNC40IDUuNTM3IDI0IDUuNjM3IDIzLjUgTCA2LjQzNyAxOC4xIEMgNi41MzcgMTcuNiA2LjkzNyAxNy4yIDcuNTM3IDE3LjIgTCAxMC4wMzcgMTcuMiBDIDE1LjEzNyAxNy4yIDE4LjEzNyAxNC43IDE4LjkzNyA5LjggQyAxOS4yMzcgNy43IDE4LjkzNyA2IDE3LjkzNyA0LjggQyAxNi44MzcgMy41IDE0LjgzNyAyLjggMTIuMjM3IDIuOCBaIE0gMTMuMTM3IDEwLjEgQyAxMi43MzcgMTIuOSAxMC41MzcgMTIuOSA4LjUzNyAxMi45IEwgNy4zMzcgMTIuOSBMIDguMTM3IDcuNyBDIDguMTM3IDcuNCA4LjQzNyA3LjIgOC43MzcgNy4yIEwgOS4yMzcgNy4yIEMgMTAuNjM3IDcuMiAxMS45MzcgNy4yIDEyLjYzNyA4IEMgMTMuMTM3IDguNCAxMy4zMzcgOS4xIDEzLjEzNyAxMC4xIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDM1LjQzNyAxMCBMIDMxLjczNyAxMCBDIDMxLjQzNyAxMCAzMS4xMzcgMTAuMiAzMS4xMzcgMTAuNSBMIDMwLjkzNyAxMS41IEwgMzAuNjM3IDExLjEgQyAyOS44MzcgOS45IDI4LjAzNyA5LjUgMjYuMjM3IDkuNSBDIDIyLjEzNyA5LjUgMTguNjM3IDEyLjYgMTcuOTM3IDE3IEMgMTcuNTM3IDE5LjIgMTguMDM3IDIxLjMgMTkuMzM3IDIyLjcgQyAyMC40MzcgMjQgMjIuMTM3IDI0LjYgMjQuMDM3IDI0LjYgQyAyNy4zMzcgMjQuNiAyOS4yMzcgMjIuNSAyOS4yMzcgMjIuNSBMIDI5LjAzNyAyMy41IEMgMjguOTM3IDIzLjkgMjkuMjM3IDI0LjMgMjkuNjM3IDI0LjMgTCAzMy4wMzcgMjQuMyBDIDMzLjUzNyAyNC4zIDM0LjAzNyAyMy45IDM0LjEzNyAyMy40IEwgMzYuMTM3IDEwLjYgQyAzNi4yMzcgMTAuNCAzNS44MzcgMTAgMzUuNDM3IDEwIFogTSAzMC4zMzcgMTcuMiBDIDI5LjkzNyAxOS4zIDI4LjMzNyAyMC44IDI2LjEzNyAyMC44IEMgMjUuMDM3IDIwLjggMjQuMjM3IDIwLjUgMjMuNjM3IDE5LjggQyAyMy4wMzcgMTkuMSAyMi44MzcgMTguMiAyMy4wMzcgMTcuMiBDIDIzLjMzNyAxNS4xIDI1LjEzNyAxMy42IDI3LjIzNyAxMy42IEMgMjguMzM3IDEzLjYgMjkuMTM3IDE0IDI5LjczNyAxNC42IEMgMzAuMjM3IDE1LjMgMzAuNDM3IDE2LjIgMzAuMzM3IDE3LjIgWiI+PC9wYXRoPjxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik0gNTUuMzM3IDEwIEwgNTEuNjM3IDEwIEMgNTEuMjM3IDEwIDUwLjkzNyAxMC4yIDUwLjczNyAxMC41IEwgNDUuNTM3IDE4LjEgTCA0My4zMzcgMTAuOCBDIDQzLjIzNyAxMC4zIDQyLjczNyAxMCA0Mi4zMzcgMTAgTCAzOC42MzcgMTAgQyAzOC4yMzcgMTAgMzcuODM3IDEwLjQgMzguMDM3IDEwLjkgTCA0Mi4xMzcgMjMgTCAzOC4yMzcgMjguNCBDIDM3LjkzNyAyOC44IDM4LjIzNyAyOS40IDM4LjczNyAyOS40IEwgNDIuNDM3IDI5LjQgQyA0Mi44MzcgMjkuNCA0My4xMzcgMjkuMiA0My4zMzcgMjguOSBMIDU1LjgzNyAxMC45IEMgNTYuMTM3IDEwLjYgNTUuODM3IDEwIDU1LjMzNyAxMCBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA2Ny43MzcgMi44IEwgNTkuOTM3IDIuOCBDIDU5LjQzNyAyLjggNTguOTM3IDMuMiA1OC44MzcgMy43IEwgNTUuNzM3IDIzLjYgQyA1NS42MzcgMjQgNTUuOTM3IDI0LjMgNTYuMzM3IDI0LjMgTCA2MC4zMzcgMjQuMyBDIDYwLjczNyAyNC4zIDYxLjAzNyAyNCA2MS4wMzcgMjMuNyBMIDYxLjkzNyAxOCBDIDYyLjAzNyAxNy41IDYyLjQzNyAxNy4xIDYzLjAzNyAxNy4xIEwgNjUuNTM3IDE3LjEgQyA3MC42MzcgMTcuMSA3My42MzcgMTQuNiA3NC40MzcgOS43IEMgNzQuNzM3IDcuNiA3NC40MzcgNS45IDczLjQzNyA0LjcgQyA3Mi4yMzcgMy41IDcwLjMzNyAyLjggNjcuNzM3IDIuOCBaIE0gNjguNjM3IDEwLjEgQyA2OC4yMzcgMTIuOSA2Ni4wMzcgMTIuOSA2NC4wMzcgMTIuOSBMIDYyLjgzNyAxMi45IEwgNjMuNjM3IDcuNyBDIDYzLjYzNyA3LjQgNjMuOTM3IDcuMiA2NC4yMzcgNy4yIEwgNjQuNzM3IDcuMiBDIDY2LjEzNyA3LjIgNjcuNDM3IDcuMiA2OC4xMzcgOCBDIDY4LjYzNyA4LjQgNjguNzM3IDkuMSA2OC42MzcgMTAuMSBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA5MC45MzcgMTAgTCA4Ny4yMzcgMTAgQyA4Ni45MzcgMTAgODYuNjM3IDEwLjIgODYuNjM3IDEwLjUgTCA4Ni40MzcgMTEuNSBMIDg2LjEzNyAxMS4xIEMgODUuMzM3IDkuOSA4My41MzcgOS41IDgxLjczNyA5LjUgQyA3Ny42MzcgOS41IDc0LjEzNyAxMi42IDczLjQzNyAxNyBDIDczLjAzNyAxOS4yIDczLjUzNyAyMS4zIDc0LjgzNyAyMi43IEMgNzUuOTM3IDI0IDc3LjYzNyAyNC42IDc5LjUzNyAyNC42IEMgODIuODM3IDI0LjYgODQuNzM3IDIyLjUgODQuNzM3IDIyLjUgTCA4NC41MzcgMjMuNSBDIDg0LjQzNyAyMy45IDg0LjczNyAyNC4zIDg1LjEzNyAyNC4zIEwgODguNTM3IDI0LjMgQyA4OS4wMzcgMjQuMyA4OS41MzcgMjMuOSA4OS42MzcgMjMuNCBMIDkxLjYzNyAxMC42IEMgOTEuNjM3IDEwLjQgOTEuMzM3IDEwIDkwLjkzNyAxMCBaIE0gODUuNzM3IDE3LjIgQyA4NS4zMzcgMTkuMyA4My43MzcgMjAuOCA4MS41MzcgMjAuOCBDIDgwLjQzNyAyMC44IDc5LjYzNyAyMC41IDc5LjAzNyAxOS44IEMgNzguNDM3IDE5LjEgNzguMjM3IDE4LjIgNzguNDM3IDE3LjIgQyA3OC43MzcgMTUuMSA4MC41MzcgMTMuNiA4Mi42MzcgMTMuNiBDIDgzLjczNyAxMy42IDg0LjUzNyAxNCA4NS4xMzcgMTQuNiBDIDg1LjczNyAxNS4zIDg1LjkzNyAxNi4yIDg1LjczNyAxNy4yIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDA5Y2RlIiBkPSJNIDk1LjMzNyAzLjMgTCA5Mi4xMzcgMjMuNiBDIDkyLjAzNyAyNCA5Mi4zMzcgMjQuMyA5Mi43MzcgMjQuMyBMIDk1LjkzNyAyNC4zIEMgOTYuNDM3IDI0LjMgOTYuOTM3IDIzLjkgOTcuMDM3IDIzLjQgTCAxMDAuMjM3IDMuNSBDIDEwMC4zMzcgMy4xIDEwMC4wMzcgMi44IDk5LjYzNyAyLjggTCA5Ni4wMzcgMi44IEMgOTUuNjM3IDIuOCA5NS40MzcgMyA5NS4zMzcgMy4zIFoiPjwvcGF0aD48L3N2Zz4=" alt="PayPal" height="28" width="88" style="display:block;pointer-events:none;object-fit:contain;" />`;
					defaultBtn.style.setProperty( 'display', 'none', 'important' );
					paypalBtn.style.removeProperty( 'display' );
					actionArea.closest( '.mm-step-nav' )?.classList.add( 'mm-step-nav--paypal' );
				} else {
					// Fallback: transformar el botón estándar en PayPal
					defaultBtn.style.display = '';
					defaultBtn.className     = 'mm-step4-paypal-btn';
					defaultBtn.setAttribute( 'aria-label', 'Mit PayPal bezahlen' );
					defaultBtn.innerHTML     = `<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAxcHgiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAxMDEgMzIiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaW5ZTWluIG1lZXQiIHhtbG5zPSJodHRwOiYjeDJGOyYjeDJGO3d3dy53My5vcmcmI3gyRjsyMDAwJiN4MkY7c3ZnIj48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDEyLjIzNyAyLjggTCA0LjQzNyAyLjggQyAzLjkzNyAyLjggMy40MzcgMy4yIDMuMzM3IDMuNyBMIDAuMjM3IDIzLjcgQyAwLjEzNyAyNC4xIDAuNDM3IDI0LjQgMC44MzcgMjQuNCBMIDQuNTM3IDI0LjQgQyA1LjAzNyAyNC40IDUuNTM3IDI0IDUuNjM3IDIzLjUgTCA2LjQzNyAxOC4xIEMgNi41MzcgMTcuNiA2LjkzNyAxNy4yIDcuNTM3IDE3LjIgTCAxMC4wMzcgMTcuMiBDIDE1LjEzNyAxNy4yIDE4LjEzNyAxNC43IDE4LjkzNyA5LjggQyAxOS4yMzcgNy43IDE4LjkzNyA2IDE3LjkzNyA0LjggQyAxNi44MzcgMy41IDE0LjgzNyAyLjggMTIuMjM3IDIuOCBaIE0gMTMuMTM3IDEwLjEgQyAxMi43MzcgMTIuOSAxMC41MzcgMTIuOSA4LjUzNyAxMi45IEwgNy4zMzcgMTIuOSBMIDguMTM3IDcuNyBDIDguMTM3IDcuNCA4LjQzNyA3LjIgOC43MzcgNy4yIEwgOS4yMzcgNy4yIEMgMTAuNjM3IDcuMiAxMS45MzcgNy4yIDEyLjYzNyA4IEMgMTMuMTM3IDguNCAxMy4zMzcgOS4xIDEzLjEzNyAxMC4xIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDM1LjQzNyAxMCBMIDMxLjczNyAxMCBDIDMxLjQzNyAxMCAzMS4xMzcgMTAuMiAzMS4xMzcgMTAuNSBMIDMwLjkzNyAxMS41IEwgMzAuNjM3IDExLjEgQyAyOS44MzcgOS45IDI4LjAzNyA5LjUgMjYuMjM3IDkuNSBDIDIyLjEzNyA5LjUgMTguNjM3IDEyLjYgMTcuOTM3IDE3IEMgMTcuNTM3IDE5LjIgMTguMDM3IDIxLjMgMTkuMzM3IDIyLjcgQyAyMC40MzcgMjQgMjIuMTM3IDI0LjYgMjQuMDM3IDI0LjYgQyAyNy4zMzcgMjQuNiAyOS4yMzcgMjIuNSAyOS4yMzcgMjIuNSBMIDI5LjAzNyAyMy41IEMgMjguOTM3IDIzLjkgMjkuMjM3IDI0LjMgMjkuNjM3IDI0LjMgTCAzMy4wMzcgMjQuMyBDIDMzLjUzNyAyNC4zIDM0LjAzNyAyMy45IDM0LjEzNyAyMy40IEwgMzYuMTM3IDEwLjYgQyAzNi4yMzcgMTAuNCAzNS44MzcgMTAgMzUuNDM3IDEwIFogTSAzMC4zMzcgMTcuMiBDIDI5LjkzNyAxOS4zIDI4LjMzNyAyMC44IDI2LjEzNyAyMC44IEMgMjUuMDM3IDIwLjggMjQuMjM3IDIwLjUgMjMuNjM3IDE5LjggQyAyMy4wMzcgMTkuMSAyMi44MzcgMTguMiAyMy4wMzcgMTcuMiBDIDIzLjMzNyAxNS4xIDI1LjEzNyAxMy42IDI3LjIzNyAxMy42IEMgMjguMzM3IDEzLjYgMjkuMTM3IDE0IDI5LjczNyAxNC42IEMgMzAuMjM3IDE1LjMgMzAuNDM3IDE2LjIgMzAuMzM3IDE3LjIgWiI+PC9wYXRoPjxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik0gNTUuMzM3IDEwIEwgNTEuNjM3IDEwIEMgNTEuMjM3IDEwIDUwLjkzNyAxMC4yIDUwLjczNyAxMC41IEwgNDUuNTM3IDE4LjEgTCA0My4zMzcgMTAuOCBDIDQzLjIzNyAxMC4zIDQyLjczNyAxMCA0Mi4zMzcgMTAgTCAzOC42MzcgMTAgQyAzOC4yMzcgMTAgMzcuODM3IDEwLjQgMzguMDM3IDEwLjkgTCA0Mi4xMzcgMjMgTCAzOC4yMzcgMjguNCBDIDM3LjkzNyAyOC44IDM4LjIzNyAyOS40IDM4LjczNyAyOS40IEwgNDIuNDM3IDI5LjQgQyA0Mi44MzcgMjkuNCA0My4xMzcgMjkuMiA0My4zMzcgMjguOSBMIDU1LjgzNyAxMC45IEMgNTYuMTM3IDEwLjYgNTUuODM3IDEwIDU1LjMzNyAxMCBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA2Ny43MzcgMi44IEwgNTkuOTM3IDIuOCBDIDU5LjQzNyAyLjggNTguOTM3IDMuMiA1OC44MzcgMy43IEwgNTUuNzM3IDIzLjYgQyA1NS42MzcgMjQgNTUuOTM3IDI0LjMgNTYuMzM3IDI0LjMgTCA2MC4zMzcgMjQuMyBDIDYwLjczNyAyNC4zIDYxLjAzNyAyNCA2MS4wMzcgMjMuNyBMIDYxLjkzNyAxOCBDIDYyLjAzNyAxNy41IDYyLjQzNyAxNy4xIDYzLjAzNyAxNy4xIEwgNjUuNTM3IDE3LjEgQyA3MC42MzcgMTcuMSA3My42MzcgMTQuNiA3NC40MzcgOS43IEMgNzQuNzM3IDcuNiA3NC40MzcgNS45IDczLjQzNyA0LjcgQyA3Mi4yMzcgMy41IDcwLjMzNyAyLjggNjcuNzM3IDIuOCBaIE0gNjguNjM3IDEwLjEgQyA2OC4yMzcgMTIuOSA2Ni4wMzcgMTIuOSA2NC4wMzcgMTIuOSBMIDYyLjgzNyAxMi45IEwgNjMuNjM3IDcuNyBDIDYzLjYzNyA3LjQgNjMuOTM3IDcuMiA2NC4yMzcgNy4yIEwgNjQuNzM3IDcuMiBDIDY2LjEzNyA3LjIgNjcuNDM3IDcuMiA2OC4xMzcgOCBDIDY4LjYzNyA4LjQgNjguNzM3IDkuMSA2OC42MzcgMTAuMSBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA5MC45MzcgMTAgTCA4Ny4yMzcgMTAgQyA4Ni45MzcgMTAgODYuNjM3IDEwLjIgODYuNjM3IDEwLjUgTCA4Ni40MzcgMTEuNSBMIDg2LjEzNyAxMS4xIEMgODUuMzM3IDkuOSA4My41MzcgOS41IDgxLjczNyA5LjUgQyA3Ny42MzcgOS41IDc0LjEzNyAxMi42IDczLjQzNyAxNyBDIDczLjAzNyAxOS4yIDczLjUzNyAyMS4zIDc0LjgzNyAyMi43IEMgNzUuOTM3IDI0IDc3LjYzNyAyNC42IDc5LjUzNyAyNC42IEMgODIuODM3IDI0LjYgODQuNzM3IDIyLjUgODQuNzM3IDIyLjUgTCA4NC41MzcgMjMuNSBDIDg0LjQzNyAyMy45IDg0LjczNyAyNC4zIDg1LjEzNyAyNC4zIEwgODguNTM3IDI0LjMgQyA4OS4wMzcgMjQuMyA4OS41MzcgMjMuOSA4OS42MzcgMjMuNCBMIDkxLjYzNyAxMC42IEMgOTEuNjM3IDEwLjQgOTEuMzM3IDEwIDkwLjkzNyAxMCBaIE0gODUuNzM3IDE3LjIgQyA4NS4zMzcgMTkuMyA4My43MzcgMjAuOCA4MS41MzcgMjAuOCBDIDgwLjQzNyAyMC44IDc5LjYzNyAyMC41IDc5LjAzNyAxOS44IEMgNzguNDM3IDE5LjEgNzguMjM3IDE4LjIgNzguNDM3IDE3LjIgQyA3OC43MzcgMTUuMSA4MC41MzcgMTMuNiA4Mi42MzcgMTMuNiBDIDgzLjczNyAxMy42IDg0LjUzNyAxNCA4NS4xMzcgMTQuNiBDIDg1LjczNyAxNS4zIDg1LjkzNyAxNi4yIDg1LjczNyAxNy4yIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDA5Y2RlIiBkPSJNIDk1LjMzNyAzLjMgTCA5Mi4xMzcgMjMuNiBDIDkyLjAzNyAyNCA5Mi4zMzcgMjQuMyA5Mi43MzcgMjQuMyBMIDk1LjkzNyAyNC4zIEMgOTYuNDM3IDI0LjMgOTYuOTM3IDIzLjkgOTcuMDM3IDIzLjQgTCAxMDAuMjM3IDMuNSBDIDEwMC4zMzcgMy4xIDEwMC4wMzcgMi44IDk5LjYzNyAyLjggTCA5Ni4wMzcgMi44IEMgOTUuNjM3IDIuOCA5NS40MzcgMyA5NS4zMzcgMy4zIFoiPjwvcGF0aD48L3N2Zz4=" alt="PayPal" height="24" style="display:block;pointer-events:none;" />`;
					actionArea.closest( '.mm-step-nav' )?.classList.add( 'mm-step-nav--paypal' );
				}

			} else if ( isKlarna && ! mid.includes( 'stripe_klarna' ) ) {
				// ── Klarna via Stripe UPE ────────────────────────────────────
				// NO mostrar el formulario UPE en paso 4 (feo con todas las tabs).
				// El UPE queda off-screen en paso 3; activamos tab Klarna allí para
				// que stripe.confirmPayment() use Klarna al hacer submit.
				const tryTab = ( n = 0 ) => {
					if ( this._activateKlarnaTab() ) return;
					if ( n < 8 ) setTimeout( () => tryTab( n + 1 ), 300 );
				};
				// Asegurar que el payment_box de Stripe esté visible en el panel off-screen
				const stripeMethodLi = document.querySelector( 'input[name="payment_method"][value="stripe"]' )?.closest( '.wc_payment_method' );
				if ( stripeMethodLi ) {
					const pb = stripeMethodLi.querySelector( '.payment_box' );
					if ( pb ) pb.style.display = 'block';
				}
				setTimeout( () => tryTab(), 300 );
				// Botón Klarna con logo K
				const klarnaLogoSvg = `<svg viewBox="0 0 1000 660" xmlns="http://www.w3.org/2000/svg" style="height:22px;width:auto;vertical-align:middle;margin-right:6px;border-radius:3px" aria-hidden="true"><rect width="1000" height="660" rx="60" fill="#FFB3C7"/><text x="500" y="430" font-size="360" font-family="Arial,sans-serif" font-weight="900" fill="#000" text-anchor="middle">K</text></svg>`;
				defaultBtn.style.display = '';
				defaultBtn.className = 'mm-btn-primary mm-btn-checkout mm-btn-checkout--step4';
				defaultBtn.innerHTML = `${ klarnaLogoSvg } Mit Klarna bezahlen ${ arrowSvg }`;
				defaultBtn.removeAttribute( 'aria-label' );
				if ( paypalBtn ) paypalBtn.style.setProperty( 'display', 'none', 'important' );

			} else {
				// ── Tarjeta (Stripe/WooPayments) / stripe_klarna / BACS / COD ──
				const cardPaymentMethodVal = ( isKlarna && mid.includes( 'stripe_klarna' ) )
					? 'stripe_klarna'
					: this._step4PaymentMethod;
				const cardMethodLi = cardPaymentMethodVal
					? document.querySelector( `input[name="payment_method"][value="${ cardPaymentMethodVal }"]` )?.closest( '.wc_payment_method' )
					: null;
				const cardPaymentBox = cardMethodLi?.querySelector( '.payment_box' );

				if ( cardPaymentBox && cardPaymentBox.children.length ) {
					const cardWrap = document.createElement( 'div' );
					cardWrap.className = 'mm-step4-card-form';
					cardWrap.appendChild( cardPaymentBox );
					cardPaymentBox.style.display = 'block';
					this.borrowedPaymentBox       = cardPaymentBox;
					this.borrowedPaymentBoxParent = cardMethodLi;
					actionArea.insertBefore( cardWrap, defaultBtn );
					actionArea.closest( '.mm-step-nav' )?.classList.add( 'mm-step-nav--paypal' );
					window.dispatchEvent( new Event( 'resize' ) );
					$( document.body ).trigger( 'payment_method_selected' );
				}

				// Botón
				defaultBtn.style.display = '';
				defaultBtn.className = 'mm-btn-primary mm-btn-checkout mm-btn-checkout--step4';
				defaultBtn.innerHTML = isKlarna
					? `Mit Klarna bezahlen ${ arrowSvg }`
					: `Jetzt bestellen ${ arrowSvg }`;
				defaultBtn.removeAttribute( 'aria-label' );
				if ( paypalBtn ) {
					paypalBtn.style.setProperty( 'display', 'none', 'important' );
				}
			}

			this._renderingStep4 = false;
		}

		_buildSubmitBtn( text, extraClass = '' ) {
			const btn = document.createElement( 'button' );
			btn.type      = 'button';
			btn.id        = 'mm-step4-submit';
			btn.className = `mm-btn-primary mm-btn-checkout mm-btn-checkout--step4${ extraClass ? ' ' + extraClass : '' }`;
			btn.innerHTML = `${ text } <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="9 18 15 12 9 6"/></svg>`;
			return btn;
		}

		restoreStep4Borrows() {
			// Limpiar siempre el layout column (PayPal, Apple Pay, Stripe card)
			document.querySelector( '.mm-step-nav--paypal' )?.classList.remove( 'mm-step-nav--paypal' );

			// Devolver payment_box al método de pago de paso 3
			if ( this.borrowedPaymentBox && this.borrowedPaymentBoxParent ) {
				this.borrowedPaymentBox.closest( '.mm-step4-card-form' )?.remove();
				this.borrowedPaymentBoxParent.appendChild( this.borrowedPaymentBox );
			}
			this.borrowedPaymentBox       = null;
			this.borrowedPaymentBoxParent = null;

			// Devolver el botón PayPal / Stripe al su contenedor original
			if ( this.borrowedExpressEl ) {
				const id = this.borrowedExpressEl.id || '';
				if ( id === 'wc-stripe-payment-request-button' || id === 'wc-stripe-express-checkout-element' ) {
					this.borrowedExpressEl.style.cssText = '';
					this.borrowedExpressEl.setAttribute( 'aria-hidden', 'true' );
					document.querySelector( 'form.checkout' )?.appendChild( this.borrowedExpressEl );
				} else if ( this.borrowedExpressElParent ) {
					this.borrowedExpressElParent.querySelector( '.payment_box' )
						?.appendChild( this.borrowedExpressEl )
						?? this.borrowedExpressElParent.appendChild( this.borrowedExpressEl );
				}
				this.borrowedExpressEl       = null;
				this.borrowedExpressElParent = null;
				document.querySelector( '.mm-step-nav--paypal' )?.classList.remove( 'mm-step-nav--paypal' );
			}
		}

		showStepError( message, panel ) {
			const target = panel || document.querySelector( `[data-step-content="${ this.currentStep }"]` );
			if ( ! target ) return;

			let errorEl = target.querySelector( '.mm-step-error' );
			if ( ! errorEl ) {
				errorEl = document.createElement( 'p' );
				errorEl.className = 'mm-step-error woocommerce-error';
				target.prepend( errorEl );
			}
			errorEl.textContent = message;
			errorEl.scrollIntoView( { behavior: 'smooth', block: 'center' } );

			setTimeout( () => { errorEl.remove(); }, 5000 );
		}

		/* ------------------------------------------------------------------
		   Popup de cambio de método de pago (desde paso 4)
		   ------------------------------------------------------------------ */
		bindPaymentPopup() {
			const overlayEl = document.getElementById( 'mm-pay-overlay' );
			const popupEl   = document.getElementById( 'mm-pay-popup' );
			if ( overlayEl && overlayEl.parentElement !== document.body ) {
				document.body.appendChild( overlayEl );
			}
			if ( popupEl && popupEl.parentElement !== document.body ) {
				document.body.appendChild( popupEl );
			}

			document.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Escape' ) this.closePaymentPopup();
			} );

			document.addEventListener( 'click', ( e ) => {
				const btn = e.target.closest( '.mm-btn-edit' );
				if ( ! btn ) return;
				const fromStep   = parseInt( btn.dataset.step );
				const targetStep = parseInt( btn.dataset.targetStep );
				if ( isNaN( fromStep ) || isNaN( targetStep ) ) return;
				e.preventDefault();
				e.stopPropagation();

				if ( fromStep === 4 && targetStep === 3 ) {
					const popupEl = document.getElementById( 'mm-pay-popup' );
					if ( popupEl ) {
						this.openPaymentPopup();
					} else {
						this.currentStep = 3;
						this.renderStep( 3 );
					}
				} else {
					this.currentStep = targetStep;
					this.renderStep( targetStep );
				}
			} );

			const overlay = document.getElementById( 'mm-pay-overlay' );
			overlay?.addEventListener( 'click', () => this.closePaymentPopup() );
			document.querySelector( '.mm-pay-popup__close' )?.addEventListener( 'click', () => this.closePaymentPopup() );
			document.querySelector( '.mm-pay-popup__cancel' )?.addEventListener( 'click', () => this.closePaymentPopup() );
			document.querySelector( '.mm-pay-popup__confirm' )?.addEventListener( 'click', () => {
				this.closePaymentPopup();
				this.updateReviewSections();
				if ( this.currentStep === 4 ) this.renderStep4Action();
			} );
		}

		openPaymentPopup() {
			const popup = document.getElementById( 'mm-pay-popup' );
			const body  = document.getElementById( 'mm-pay-popup-body' );
			if ( ! popup || ! body ) return;

			const pool = document.getElementById( 'mm-ppcp-pool' );
			if ( pool ) pool.style.display = 'none';

			const list = document.createElement( 'ul' );
			list.className = 'mm-pay-popup__list';

			const currentVal = ( document.querySelector( 'input[name="payment_method"]:checked' ) || {} ).value || '';

			document.querySelectorAll( '.wc_payment_method input[name="payment_method"]' ).forEach( ( radio ) => {
				const methodEl = radio.closest( '.wc_payment_method' );
				if ( ! methodEl ) return;

				const labelEl   = methodEl.querySelector( 'label' );
				const imgEl     = labelEl ? labelEl.querySelector( 'img' ) : null;
				const labelText = labelEl
					? Array.from( labelEl.childNodes )
						.filter( ( n ) => n.nodeType === Node.TEXT_NODE )
						.map( ( n ) => n.textContent.trim() )
						.join( '' )
						.trim() || radio.value
					: radio.value;

				const item = document.createElement( 'li' );
				item.className = 'mm-pay-popup__item' + ( radio.value === currentVal ? ' mm-pay-popup__item--selected' : '' );
				item.dataset.methodValue = radio.value;
				item.innerHTML = `
					<div class="mm-pay-popup__item-radio"></div>
					<span class="mm-pay-popup__item-name">${ labelText }</span>
					${ imgEl ? `<img src="${ imgEl.src }" alt="${ imgEl.alt }" class="mm-pay-popup__item-img">` : '' }
				`;

				item.addEventListener( 'click', () => {
					list.querySelectorAll( '.mm-pay-popup__item' ).forEach( ( i ) => i.classList.remove( 'mm-pay-popup__item--selected' ) );
					item.classList.add( 'mm-pay-popup__item--selected' );
					radio.checked = true;
					radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					$( document.body ).trigger( 'payment_method_selected' );
				} );

				list.appendChild( item );
			} );

			body.innerHTML = '';
			body.appendChild( list );

			popup.classList.add( 'mm-pay-popup--active' );
			popup.setAttribute( 'aria-hidden', 'false' );
			document.getElementById( 'mm-pay-overlay' )?.classList.add( 'mm-pay-overlay--active' );
			document.body.classList.add( 'mm-pay-open' );
		}

		closePaymentPopup() {
			document.getElementById( 'mm-pay-popup' )?.classList.remove( 'mm-pay-popup--active' );
			document.getElementById( 'mm-pay-popup' )?.setAttribute( 'aria-hidden', 'true' );
			document.getElementById( 'mm-pay-overlay' )?.classList.remove( 'mm-pay-overlay--active' );
			document.body.classList.remove( 'mm-pay-open' );
			document.documentElement.classList.remove( 'mm-pay-open' );
			const pool = document.getElementById( 'mm-ppcp-pool' );
			if ( pool ) pool.style.display = '';
			document.getElementById( 'mm-step4-action-area' )?.style.removeProperty( 'visibility' );
		}
	}

	// Init cuando el DOM esté listo
	$( function () {
		if ( document.body.classList.contains( 'mm-checkout-page' ) ) {
			window.mmCheckout = new MMCheckout();
		}
	} );

} )( jQuery );