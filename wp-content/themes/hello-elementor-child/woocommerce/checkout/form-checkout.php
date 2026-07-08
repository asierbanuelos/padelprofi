<?php
/**
 * Checkout Form — PadelProfi Multi-Step
 * Override: woocommerce/checkout/form-checkout.php
 *
 * @package hello-elementor-child
 * @version MM-2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'woocommerce_blocks_checkout_order_meta', '__return_false' );
add_filter( 'woocommerce_checkout_registration_required', '__return_false', 99 );

$checkout = WC()->checkout();

// ── CARRITO VACÍO ────────────────────────────────────────────────────────────
if ( WC()->cart->is_empty() ) : ?>
<div class="pp-empty-cart">
	<div class="pp-empty-cart__icon">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
			<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
			<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
		</svg>
	</div>
	<h2>Keine Produkte im Warenkorb</h2>
	<p>Entdecke alle unsere Angebote.</p>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pp-empty-cart__cta">Weiter einkaufen</a>
	<div class="pp-empty-cart__slider">
		<p class="pp-empty-cart__slider-title">Das könnte dich auch interessieren</p>
		<?php echo do_shortcode( '[carousel_slide id="39989"]' ); ?>
	</div>
</div>
<?php return; endif; ?>

<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

<div class="mm-checkout-wrapper" style="width:min(1180px, calc(100vw - 40px))!important;max-width:min(1180px, calc(100vw - 40px))!important;margin-left:auto!important;margin-right:auto!important;">

	<!-- ENLACE VOLVER AL CARRITO -->
	<div class="mm-back-to-cart">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="mm-back-to-cart__link">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
			<?php esc_html_e( 'Warenkorb', 'hello-elementor-child' ); ?>
		</a>
	</div>

	<!-- BARRA DE PROGRESO -->
	<nav class="mm-progress-bar" aria-label="Checkout-Schritte">
		<?php
		$steps = [ 1 => 'Adresse', 2 => 'Versand', 3 => 'Zahlung', 4 => 'Übersicht' ];
		$check_svg = '<svg class="mm-step__check" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="16"><polyline points="20 6 9 17 4 12"/></svg>';
		foreach ( $steps as $n => $label ) :
			if ( $n > 1 ) echo '<div class="mm-step__connector" aria-hidden="true"></div>';
			$active = $n === 1 ? ' mm-step--active' : '';
		?>
		<div class="mm-step<?php echo $active; ?>" data-step="<?php echo $n; ?>" aria-current="<?php echo $n === 1 ? 'step' : 'false'; ?>">
			<div class="mm-step__circle">
				<span class="mm-step__number"><?php echo $n; ?></span>
				<?php echo $check_svg; ?>
			</div>
			<span class="mm-step__label"><?php echo $label; ?></span>
		</div>
		<?php endforeach; ?>
	</nav>

	<!-- Logos métodos de pago -->
	<style>.mm-pay-logos img{height:22px;width:auto;}@media(max-width:768px){.mm-pay-logos img{height:15px;}}</style>
	<div class="mm-pay-logos" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px;padding:10px 0 18px;">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/logo-visa-bw.svg" alt="Visa" loading="lazy">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/logo-mastercard-bw.svg" alt="Mastercard" loading="lazy">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/logo-paypal-bw.svg" alt="PayPal" loading="lazy">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/apple_pay.svg" alt="Apple Pay" loading="lazy">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/Logo-g-pay.svg" alt="Google Pay" loading="lazy">
		<img src="https://padelprofideutschland.de/wp-content/uploads/2023/11/png-clipart-klarna-logo-tech-companies-removebg-preview.png" alt="Klarna" loading="lazy">
	</div>

	<form name="checkout" method="post"
	      class="checkout woocommerce-checkout"
	      action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
	      enctype="multipart/form-data">

		<div class="mm-checkout-layout" style="width:100%!important;position:relative!important;">
			<div class="mm-checkout-main" style="min-width:0!important;">

				<!-- ── SCHRITT 1: LIEFERADRESSE ── -->
				<section class="mm-step-panel" data-step-content="1" aria-labelledby="mm-title-1">
					<h2 class="mm-step-title" id="mm-title-1">
						<span class="mm-step-title__num">1</span>
						Lieferadresse
					</h2>

					<!-- Toggle -->
					<div class="pp-customer-type">
						<button type="button" class="pp-type-btn pp-type-btn--active" data-type="particular">Privatperson / Selbstständige</button>
						<button type="button" class="pp-type-btn" data-type="empresa">Unternehmen</button>
					</div>
					<input type="hidden" name="billing_customer_type" id="billing_customer_type" value="particular" />

					<!-- Empresa -->
					<div class="pp-empresa-row" style="display:none;">
						<div class="pp-field-wrap pp-field-wrap--full">
							<input type="text" id="billing_company" name="billing_company" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_company') ); ?>" autocomplete="organization" />
							<label for="billing_company">Firmenname <span class="pp-required">*</span></label>
						</div>
						<div class="pp-field-wrap pp-field-wrap--full">
							<input type="text" id="billing_vat" name="billing_vat" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_vat') ); ?>" autocomplete="off" />
							<label for="billing_vat">USt-IdNr. (optional)</label>
						</div>
					</div>

					<!-- Anrede -->
					<div class="pp-salutation-row">
						<label class="pp-salutation-label">Anrede</label>
						<div class="pp-salutation-options">
							<?php foreach ( ['Herr' => 'Hr.', 'Frau' => 'Fr.', 'Keine' => 'Keine Angabe'] as $val => $lbl ) : ?>
							<label class="pp-radio-label">
								<input type="radio" name="billing_salutation" value="<?php echo $val; ?>" <?php checked( $checkout->get_value('billing_salutation') ?: 'Herr', $val ); ?> />
								<span class="pp-radio-custom"></span>
								<?php echo $lbl; ?>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Nombre + Apellidos -->
					<div class="pp-fields-row pp-fields-row--2">
						<div class="pp-field-wrap">
							<input type="text" id="billing_first_name" name="billing_first_name" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_first_name') ); ?>" autocomplete="given-name" required />
							<label for="billing_first_name">Vorname <span class="pp-required">*</span></label>
						</div>
						<div class="pp-field-wrap">
							<input type="text" id="billing_last_name" name="billing_last_name" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_last_name') ); ?>" autocomplete="family-name" required />
							<label for="billing_last_name">Nachname <span class="pp-required">*</span></label>
						</div>
					</div>

					<!-- NIF -->
					<div class="pp-nif-row">
						<div class="pp-field-wrap pp-field-wrap--full">
							<input type="text" id="billing_nif" name="billing_nif" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_nif') ); ?>" autocomplete="off" />
							<label for="billing_nif">NIF / NIE (optional)</label>
						</div>
					</div>

					<!-- Telefon + Email -->
					<div class="pp-fields-row pp-fields-row--2">
						<div class="pp-field-wrap">
							<input type="tel" id="billing_phone" name="billing_phone" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_phone') ); ?>" autocomplete="tel" required />
							<label for="billing_phone">Telefonnummer <span class="pp-required">*</span></label>
						</div>
						<div class="pp-field-wrap">
							<input type="email" id="billing_email" name="billing_email" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_email') ); ?>" autocomplete="email" required />
							<label for="billing_email">E-Mail-Adresse <span class="pp-required">*</span></label>
						</div>
					</div>

					<!-- Straße → billing_address_1 (obligatorio, PayPal y WC lo leen directamente) -->
					<div class="pp-fields-row pp-fields-row--street">
						<div class="pp-field-wrap pp-field-wrap--street">
							<input type="text" id="billing_address_1" name="billing_address_1" placeholder=" "
							       value="<?php echo esc_attr( $checkout->get_value('billing_address_1') ); ?>"
							       autocomplete="address-line1" required />
							<label for="billing_address_1">Straße <span class="pp-required">*</span></label>
						</div>
						<!-- Hausnummer → billing_address_2 (obligatorio) -->
						<div class="pp-field-wrap pp-field-wrap--housenumber">
							<input type="text" id="billing_address_2" name="billing_address_2" placeholder=" "
							       value="<?php echo esc_attr( $checkout->get_value('billing_address_2') ); ?>"
							       autocomplete="off" required />
							<label for="billing_address_2">Hausnummer <span class="pp-required">*</span></label>
						</div>
					</div>

					<!-- Etage — OPCIONAL, campo custom que no interfiere con PayPal -->
					<div class="pp-field-wrap pp-field-wrap--full">
						<input type="text" id="billing_etage" name="billing_etage" placeholder=" "
						       value="<?php echo esc_attr( $checkout->get_value('billing_etage') ); ?>"
						       autocomplete="address-line3" />
						<label for="billing_etage">Etage, Tür und weitere Angaben <span class="pp-optional">(optional)</span></label>
					</div>

					<!-- PLZ + Stadt — se rellenan solos al elegir dirección en Google Places -->
					<div class="pp-fields-row pp-fields-row--postcode">
						<div class="pp-field-wrap">
							<input type="text" id="billing_postcode" name="billing_postcode" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_postcode') ); ?>" autocomplete="postal-code" required />
							<label for="billing_postcode">PLZ <span class="pp-required">*</span></label>
						</div>
						<div class="pp-field-wrap pp-field-wrap--city">
							<input type="text" id="billing_city" name="billing_city" placeholder=" " value="<?php echo esc_attr( $checkout->get_value('billing_city') ); ?>" autocomplete="address-level2" required />
							<label for="billing_city">Stadt <span class="pp-required">*</span></label>
						</div>
					</div>

					<!-- País oculto -->
					<input type="hidden" name="billing_country" id="billing_country" value="<?php echo esc_attr( $checkout->get_value('billing_country') ?: 'DE' ); ?>" />
					<input type="hidden" name="shipping_country" value="<?php echo esc_attr( $checkout->get_value('billing_country') ?: 'DE' ); ?>" />

					<!-- Datenschutz -->
					<div class="pp-privacy-check">
						<label class="pp-check-label">
							<input type="checkbox" name="billing_privacy_check" id="billing_privacy_check" required />
							<span class="pp-checkmark"></span>
							<span>Ich habe die <a href="<?php echo esc_url( get_privacy_policy_url() ?: '#' ); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen und akzeptiert</span>
						</label>
					</div>


				</section>

				<!-- ── SCHRITT 2: VERSAND ── -->
				<section class="mm-step-panel" data-step-content="2" aria-labelledby="mm-title-2">
					<h2 class="mm-step-title" id="mm-title-2"><span class="mm-step-title__num">2</span> Versandmethode wählen</h2>

					<div class="mm-confirmed-address">
						<div class="mm-confirmed-address__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
						</div>
						<div class="mm-confirmed-address__text">
							<span class="mm-confirmed-address__label">Lieferung an:</span>
							<span class="mm-confirmed-address__value" id="mm-address-display"></span>
						</div>
						<button type="button" class="mm-btn-edit" data-step="2" data-target-step="1">Bearbeiten</button>
					</div>

					<?php if ( WC()->cart->needs_shipping() ) : ?>
					<div class="mm-shipping-methods">
						<h3 class="mm-shipping-methods__title">Versandoptionen</h3>
						<div class="mm-wc-shipping-wrapper"><?php wc_cart_totals_shipping_html(); ?></div>
					</div>
					<?php else : ?>
					<div class="mm-no-shipping-msg">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
						Für diese Bestellung ist kein Versand erforderlich.
					</div>
					<?php endif; ?>


				</section>

				<!-- ── SCHRITT 3: ZAHLUNG ── -->
				<section class="mm-step-panel" data-step-content="3" aria-labelledby="mm-title-3">
					<h2 class="mm-step-title" id="mm-title-3"><span class="mm-step-title__num">3</span> Zahlungsmethode</h2>

					<div class="mm-payment-wrapper">
						<?php
						$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
						WC()->payment_gateways()->set_current_gateway( $available_gateways );
						wc_get_template( 'checkout/payment.php', [
							'checkout'           => WC()->checkout(),
							'available_gateways' => $available_gateways,
							'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
						] );
						?>
					</div>

					<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
					<div class="mm-order-notes">
						<?php woocommerce_form_field( 'order_comments', [
							'type'        => 'textarea',
							'class'       => [ 'notes', 'form-row-wide' ],
							'label'       => 'Anmerkungen zur Bestellung (optional)',
							'placeholder' => 'Anmerkungen zu deiner Bestellung, z.B. besondere Hinweise für die Lieferung.',
						], $checkout->get_value( 'order_comments' ) ); ?>
					</div>
					<?php endif; ?>

					<div class="mm-step-nav">
						<button type="button" class="mm-btn-prev mm-btn-secondary" data-step="3" data-target-step="2">
							<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="15 18 9 12 15 6"/></svg>
							Zurück
						</button>

					</div>
				</section>

				<!-- ── SCHRITT 4: ÜBERSICHT ── -->
				<section class="mm-step-panel" data-step-content="4" aria-labelledby="mm-title-4">
					<h2 class="mm-step-title" id="mm-title-4"><span class="mm-step-title__num">4</span> Bestellübersicht</h2>

					<!-- Produkte + Totals -->
					<div class="mm-step4-block">
						<div class="mm-step4-block__title">Bestellte Produkte</div>
						<div class="mm-order-summary__items" id="mm-step4-items">
							<?php echo mm_render_sidebar_items(); ?>
						</div>
						<div class="mm-order-summary__totals mm-step4-totals" id="mm-step4-totals">
							<?php echo mm_render_sidebar_totals(); ?>
						</div>
					</div>

					<!-- Info-Karten: Adresse / Versand / Zahlung -->
					<div class="mm-review-cards">
						<div class="mm-review-card">
							<div class="mm-review-card__header">
								<span class="mm-review-card__title">Lieferadresse</span>
								<button type="button" class="mm-btn-edit" data-step="4" data-target-step="1">Bearbeiten</button>
							</div>
							<div class="mm-review-card__body" id="mm-review-address"></div>
						</div>
						<div class="mm-review-card">
							<div class="mm-review-card__header">
								<span class="mm-review-card__title">Versandmethode</span>
								<button type="button" class="mm-btn-edit" data-step="4" data-target-step="2">Bearbeiten</button>
							</div>
							<div class="mm-review-card__body" id="mm-review-shipping"></div>
						</div>
						<div class="mm-review-card">
							<div class="mm-review-card__header">
								<span class="mm-review-card__title">Zahlungsmethode</span>
								<button type="button" class="mm-btn-edit" data-step="4" data-target-step="3">Bearbeiten</button>
							</div>
							<div class="mm-review-card__body" id="mm-review-payment"></div>
						</div>
					</div>

					<!-- Formulario de pago con tarjeta (slot en contenido scrollable) -->
					<div id="mm-step4-card-slot" style="display:none;"></div>

					<!-- AGB + Submit -->
					<div class="mm-step4-footer">
						<div class="mm-step-nav">
							<button type="button" class="mm-btn-prev mm-btn-secondary" data-step="4" data-target-step="3">
								<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="15 18 9 12 15 6"/></svg>
								Zurück
							</button>
							<!-- Botones de pago — JS muestra el correcto según método -->
							<div id="mm-step4-action-area" class="mm-step4-action-area">
								<!-- Estándar (Klarna, tarjeta, etc.) -->
								<button type="button" id="mm-step4-submit" class="mm-btn-primary mm-btn-checkout mm-btn-checkout--step4">
									Jetzt bestellen
									<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="9 18 15 12 9 6"/></svg>
								</button>
								<!-- PayPal (oculto hasta que JS lo active) -->
								<button type="button" id="mm-step4-paypal" class="mm-step4-paypal-btn" style="display:none;" aria-label="Mit PayPal bezahlen">
									<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAxcHgiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAxMDEgMzIiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaW5ZTWluIG1lZXQiIHhtbG5zPSJodHRwOiYjeDJGOyYjeDJGO3d3dy53My5vcmcmI3gyRjsyMDAwJiN4MkY7c3ZnIj48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDEyLjIzNyAyLjggTCA0LjQzNyAyLjggQyAzLjkzNyAyLjggMy40MzcgMy4yIDMuMzM3IDMuNyBMIDAuMjM3IDIzLjcgQyAwLjEzNyAyNC4xIDAuNDM3IDI0LjQgMC44MzcgMjQuNCBMIDQuNTM3IDI0LjQgQyA1LjAzNyAyNC40IDUuNTM3IDI0IDUuNjM3IDIzLjUgTCA2LjQzNyAxOC4xIEMgNi41MzcgMTcuNiA2LjkzNyAxNy4yIDcuNTM3IDE3LjIgTCAxMC4wMzcgMTcuMiBDIDE1LjEzNyAxNy4yIDE4LjEzNyAxNC43IDE4LjkzNyA5LjggQyAxOS4yMzcgNy43IDE4LjkzNyA2IDE3LjkzNyA0LjggQyAxNi44MzcgMy41IDE0LjgzNyAyLjggMTIuMjM3IDIuOCBaIE0gMTMuMTM3IDEwLjEgQyAxMi43MzcgMTIuOSAxMC41MzcgMTIuOSA4LjUzNyAxMi45IEwgNy4zMzcgMTIuOSBMIDguMTM3IDcuNyBDIDguMTM3IDcuNCA4LjQzNyA3LjIgOC43MzcgNy4yIEwgOS4yMzcgNy4yIEMgMTAuNjM3IDcuMiAxMS45MzcgNy4yIDEyLjYzNyA4IEMgMTMuMTM3IDguNCAxMy4zMzcgOS4xIDEzLjEzNyAxMC4xIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDM1LjQzNyAxMCBMIDMxLjczNyAxMCBDIDMxLjQzNyAxMCAzMS4xMzcgMTAuMiAzMS4xMzcgMTAuNSBMIDMwLjkzNyAxMS41IEwgMzAuNjM3IDExLjEgQyAyOS44MzcgOS45IDI4LjAzNyA5LjUgMjYuMjM3IDkuNSBDIDIyLjEzNyA5LjUgMTguNjM3IDEyLjYgMTcuOTM3IDE3IEMgMTcuNTM3IDE5LjIgMTguMDM3IDIxLjMgMTkuMzM3IDIyLjcgQyAyMC40MzcgMjQgMjIuMTM3IDI0LjYgMjQuMDM3IDI0LjYgQyAyNy4zMzcgMjQuNiAyOS4yMzcgMjIuNSAyOS4yMzcgMjIuNSBMIDI5LjAzNyAyMy41IEMgMjguOTM3IDIzLjkgMjkuMjM3IDI0LjMgMjkuNjM3IDI0LjMgTCAzMy4wMzcgMjQuMyBDIDMzLjUzNyAyNC4zIDM0LjAzNyAyMy45IDM0LjEzNyAyMy40IEwgMzYuMTM3IDEwLjYgQyAzNi4yMzcgMTAuNCAzNS44MzcgMTAgMzUuNDM3IDEwIFogTSAzMC4zMzcgMTcuMiBDIDI5LjkzNyAxOS4zIDI4LjMzNyAyMC44IDI2LjEzNyAyMC44IEMgMjUuMDM3IDIwLjggMjQuMjM3IDIwLjUgMjMuNjM3IDE5LjggQyAyMy4wMzcgMTkuMSAyMi44MzcgMTguMiAyMy4wMzcgMTcuMiBDIDIzLjMzNyAxNS4xIDI1LjEzNyAxMy42IDI3LjIzNyAxMy42IEMgMjguMzM3IDEzLjYgMjkuMTM3IDE0IDI5LjczNyAxNC42IEMgMzAuMjM3IDE1LjMgMzAuNDM3IDE2LjIgMzAuMzM3IDE3LjIgWiI+PC9wYXRoPjxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik0gNTUuMzM3IDEwIEwgNTEuNjM3IDEwIEMgNTEuMjM3IDEwIDUwLjkzNyAxMC4yIDUwLjczNyAxMC41IEwgNDUuNTM3IDE4LjEgTCA0My4zMzcgMTAuOCBDIDQzLjIzNyAxMC4zIDQyLjczNyAxMCA0Mi4zMzcgMTAgTCAzOC42MzcgMTAgQyAzOC4yMzcgMTAgMzcuODM3IDEwLjQgMzguMDM3IDEwLjkgTCA0Mi4xMzcgMjMgTCAzOC4yMzcgMjguNCBDIDM3LjkzNyAyOC44IDM4LjIzNyAyOS40IDM4LjczNyAyOS40IEwgNDIuNDM3IDI5LjQgQyA0Mi44MzcgMjkuNCA0My4xMzcgMjkuMiA0My4zMzcgMjguOSBMIDU1LjgzNyAxMC45IEMgNTYuMTM3IDEwLjYgNTUuODM3IDEwIDU1LjMzNyAxMCBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA2Ny43MzcgMi44IEwgNTkuOTM3IDIuOCBDIDU5LjQzNyAyLjggNTguOTM3IDMuMiA1OC44MzcgMy43IEwgNTUuNzM3IDIzLjYgQyA1NS42MzcgMjQgNTUuOTM3IDI0LjMgNTYuMzM3IDI0LjMgTCA2MC4zMzcgMjQuMyBDIDYwLjczNyAyNC4zIDYxLjAzNyAyNCA2MS4wMzcgMjMuNyBMIDYxLjkzNyAxOCBDIDYyLjAzNyAxNy41IDYyLjQzNyAxNy4xIDYzLjAzNyAxNy4xIEwgNjUuNTM3IDE3LjEgQyA3MC42MzcgMTcuMSA3My42MzcgMTQuNiA3NC40MzcgOS43IEMgNzQuNzM3IDcuNiA3NC40MzcgNS45IDczLjQzNyA0LjcgQyA3Mi4yMzcgMy41IDcwLjMzNyAyLjggNjcuNzM3IDIuOCBaIE0gNjguNjM3IDEwLjEgQyA2OC4yMzcgMTIuOSA2Ni4wMzcgMTIuOSA2NC4wMzcgMTIuOSBMIDYyLjgzNyAxMi45IEwgNjMuNjM3IDcuNyBDIDYzLjYzNyA3LjQgNjMuOTM3IDcuMiA2NC4yMzcgNy4yIEwgNjQuNzM3IDcuMiBDIDY2LjEzNyA3LjIgNjcuNDM3IDcuMiA2OC4xMzcgOCBDIDY4LjYzNyA4LjQgNjguNzM3IDkuMSA2OC42MzcgMTAuMSBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA5MC45MzcgMTAgTCA4Ny4yMzcgMTAgQyA4Ni45MzcgMTAgODYuNjM3IDEwLjIgODYuNjM3IDEwLjUgTCA4Ni40MzcgMTEuNSBMIDg2LjEzNyAxMS4xIEMgODUuMzM3IDkuOSA4My41MzcgOS41IDgxLjczNyA5LjUgQyA3Ny42MzcgOS41IDc0LjEzNyAxMi42IDczLjQzNyAxNyBDIDczLjAzNyAxOS4yIDczLjUzNyAyMS4zIDc0LjgzNyAyMi43IEMgNzUuOTM3IDI0IDc3LjYzNyAyNC42IDc5LjUzNyAyNC42IEMgODIuODM3IDI0LjYgODQuNzM3IDIyLjUgODQuNzM3IDIyLjUgTCA4NC41MzcgMjMuNSBDIDg0LjQzNyAyMy45IDg0LjczNyAyNC4zIDg1LjEzNyAyNC4zIEwgODguNTM3IDI0LjMgQyA4OS4wMzcgMjQuMyA4OS41MzcgMjMuOSA4OS42MzcgMjMuNCBMIDkxLjYzNyAxMC42IEMgOTEuNjM3IDEwLjQgOTEuMzM3IDEwIDkwLjkzNyAxMCBaIE0gODUuNzM3IDE3LjIgQyA4NS4zMzcgMTkuMyA4My43MzcgMjAuOCA4MS41MzcgMjAuOCBDIDgwLjQzNyAyMC44IDc5LjYzNyAyMC41IDc5LjAzNyAxOS44IEMgNzguNDM3IDE5LjEgNzguMjM3IDE4LjIgNzguNDM3IDE3LjIgQyA3OC43MzcgMTUuMSA4MC41MzcgMTMuNiA4Mi42MzcgMTMuNiBDIDgzLjczNyAxMy42IDg0LjUzNyAxNCA4NS4xMzcgMTQuNiBDIDg1LjczNyAxNS4zIDg1LjkzNyAxNi4yIDg1LjczNyAxNy4yIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDA5Y2RlIiBkPSJNIDk1LjMzNyAzLjMgTCA5Mi4xMzcgMjMuNiBDIDkyLjAzNyAyNCA5Mi4zMzcgMjQuMyA5Mi43MzcgMjQuMyBMIDk1LjkzNyAyNC4zIEMgOTYuNDM3IDI0LjMgOTYuOTM3IDIzLjkgOTcuMDM3IDIzLjQgTCAxMDAuMjM3IDMuNSBDIDEwMC4zMzcgMy4xIDEwMC4wMzcgMi44IDk5LjYzNyAyLjggTCA5Ni4wMzcgMi44IEMgOTUuNjM3IDIuOCA5NS40MzcgMyA5NS4zMzcgMy4zIFoiPjwvcGF0aD48L3N2Zz4=" alt="PayPal" height="24" style="display:block;pointer-events:none;" />
								</button>
							</div>
						</div>
					</div>

				</section>

			</div><!-- /.mm-checkout-main -->

			<!-- ── SIDEBAR ── -->
			<aside class="mm-checkout-sidebar" aria-label="Bestellzusammenfassung">
				<div class="mm-order-summary">
					<h3 class="mm-order-summary__title">Zusammenfassung</h3>

					<div class="mm-order-summary__items" id="mm-sidebar-items">
						<?php echo mm_render_sidebar_items(); ?>
					</div>
					<div class="mm-order-summary__totals" id="mm-sidebar-totals">
						<?php echo mm_render_sidebar_totals(); ?>
					</div>

					<div class="mm-coupon-field">
						<div class="mm-coupon-input-group">
							<input type="text" id="mm-coupon-code" placeholder="Gutscheincode" aria-label="Gutscheincode eingeben" autocomplete="off" />
							<button type="button" id="mm-apply-coupon" class="mm-btn-coupon">Hinzufügen</button>
						</div>
						<div class="mm-coupon-message" aria-live="polite" role="status"></div>
					</div>



					<button type="button" id="mm-sidebar-submit" class="mm-btn-primary mm-btn-checkout">
						Weiter zur Lieferung
					</button>

				</div>
			</aside>

		</div><!-- /.mm-checkout-layout -->

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

		<!-- Contenedores Stripe para Apple Pay / Google Pay — ocultos, Stripe los monta -->
		<div id="wc-stripe-payment-request-button" class="mm-stripe-prb-pool" aria-hidden="true"></div>
		<div id="wc-stripe-express-checkout-element" class="mm-stripe-prb-pool" aria-hidden="true"></div>

		<?php
		/* Pool PayPal PPCP — oculto fuera de pantalla, el plugin renderiza su botón aquí.
		 * JS lo mueve al área de acción del paso 4 cuando PayPal está seleccionado.
		 * Necesita dimensiones reales para que el SDK de PayPal pueda montar. */
		$_gw_pool = WC()->payment_gateways()->get_available_payment_gateways();
		if ( isset( $_gw_pool['ppcp-gateway'] ) || isset( $_gw_pool['ppcp'] ) ) : ?>
		<div id="mm-ppcp-pool" aria-hidden="true" style="position:fixed;top:-9999px;left:-9999px;width:300px;height:80px;overflow:visible;z-index:-1;">
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		</div>
		<?php endif; ?>

	</form>
</div><!-- /.mm-checkout-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {

	/* ── Toggle Particular / Empresa ── */
	var btns      = document.querySelectorAll('.pp-type-btn');
	var nifRow    = document.querySelector('.pp-nif-row');
	var empRow    = document.querySelector('.pp-empresa-row');
	var typeInput = document.getElementById('billing_customer_type');
	btns.forEach(function(btn){
		btn.addEventListener('click', function(){
			btns.forEach(function(b){ b.classList.remove('pp-type-btn--active'); });
			btn.classList.add('pp-type-btn--active');
			var t = btn.dataset.type;
			typeInput.value = t;
			if(nifRow) nifRow.style.display = t==='empresa' ? 'none' : 'block';
			if(empRow) empRow.style.display = t==='empresa' ? 'block' : 'none';
		});
	});

	/* ── FIX LAYOUT: forzar grid con máxima prioridad ──
	   Elementor inyecta display:flex !important via JS/CSS de mayor
	   especificidad. Los estilos inline superan cualquier hoja de estilos.
	   Se expone globalmente para que checkout-steps.js pueda llamarlo al
	   cambiar de paso (especialmente al entrar/salir del paso 4).
	─────────────────────────────────────────────────────────────────────── */
	window.ppFixLayout = function() {
		var layout  = document.querySelector('.mm-checkout-layout');
		var main    = document.querySelector('.mm-checkout-main');
		var sidebar = document.querySelector('.mm-checkout-sidebar');
		if ( !layout ) return;

		var isStep4  = layout.classList.contains('mm-step4-active');
		var isMobile = window.innerWidth <= 768;

		layout.style.setProperty('width', '100%', 'important');
		layout.style.setProperty('position', 'relative', 'important');

		if(isMobile) {
			// Móvil: columna única
			layout.style.setProperty('display', 'block', 'important');
			layout.style.setProperty('min-height', '0', 'important');
			if(main) {
				main.style.setProperty('width', '100%', 'important');
				main.style.setProperty('max-width', 'none', 'important');
				main.style.setProperty('min-width', '0', 'important');
				main.style.removeProperty('margin-left');
				main.style.removeProperty('margin-right');
			}
			if(sidebar) {
				if(isStep4) {
					sidebar.style.setProperty('display', 'none', 'important');
				} else {
					sidebar.style.removeProperty('display');
				}
			}

		} else if(isStep4) {
			// Desktop paso 4: columna única centrada, sidebar oculto
			layout.style.setProperty('display', 'block', 'important');
			layout.style.setProperty('min-height', '0', 'important');
			if(main) {
				main.style.setProperty('width', '100%', 'important');
				main.style.setProperty('max-width', '800px', 'important');
				main.style.setProperty('min-width', '0', 'important');
				main.style.setProperty('margin-left', 'auto', 'important');
				main.style.setProperty('margin-right', 'auto', 'important');
			}
			if(sidebar) {
				sidebar.style.setProperty('display', 'none', 'important');
			}

		} else {
			// Desktop normal: grid de 2 columnas (form | sidebar)
			layout.style.setProperty('display', 'grid', 'important');
			layout.style.setProperty('grid-template-columns', '1fr 420px', 'important');
			layout.style.setProperty('grid-gap', '32px', 'important');
			layout.style.setProperty('align-items', 'start', 'important');
			layout.style.setProperty('min-height', '0', 'important');
			if(main) {
				main.style.setProperty('width', '100%', 'important');
				main.style.setProperty('max-width', 'none', 'important');
				main.style.setProperty('min-width', '0', 'important');
				main.style.removeProperty('margin-left');
				main.style.removeProperty('margin-right');
			}
			if(sidebar) {
				sidebar.style.removeProperty('display');
				sidebar.style.setProperty('position', 'static', 'important');
				sidebar.style.setProperty('width', '100%', 'important');
				sidebar.style.setProperty('min-width', '0', 'important');
			}
		}
	};

	window.ppFixLayout();
	window.addEventListener('resize', window.ppFixLayout);

});
</script>

<!-- ── POPUP Zahlungsmethode ── -->
<div id="mm-pay-overlay" class="mm-pay-overlay" aria-hidden="true"></div>
<div id="mm-pay-popup" class="mm-pay-popup" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Zahlungsmethode ändern">
	<div class="mm-pay-popup__header">
		<h3 class="mm-pay-popup__title">Zahlungsmethode ändern</h3>
		<button type="button" class="mm-pay-popup__close" aria-label="Schließen">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
			</svg>
		</button>
	</div>
	<div class="mm-pay-popup__body" id="mm-pay-popup-body">
		<!-- Populated by JS: cloned payment method list from step 3 -->
	</div>
	<div class="mm-pay-popup__footer">
		<button type="button" class="mm-btn-secondary mm-pay-popup__cancel">Abbrechen</button>
		<button type="button" class="mm-btn-primary mm-pay-popup__confirm">Übernehmen</button>
	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>