<?php
/**
 * Thankyou Page — PadelProfi (rediseño)
 * Override: woocommerce/checkout/thankyou.php
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'woocommerce_before_thankyou', $order_id );
?>

<?php if ( ! $order ) : ?>
<div class="pp-ty-body" style="text-align:center;padding:60px 20px;">
	<p style="font-size:16px;color:#666;">Vielen Dank für deine Bestellung!</p>
	<a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>" class="pp-ty-btn pp-ty-btn--primary" style="margin-top:20px;display:inline-flex;">Zum Shop</a>
</div>
<?php return; endif; ?>

<?php if ( $order->has_status( 'failed' ) ) : ?>
<div style="background:#fff7f7;border-left:4px solid #e2401c;padding:16px 20px;max-width:800px;margin:24px auto;border-radius:8px;font-family:'Open Sans',sans-serif;">
	<strong style="color:#e2401c;">Zahlung fehlgeschlagen.</strong>
	<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" style="margin-left:12px;color:#FE6100;font-weight:700;">Erneut versuchen</a>
</div>
<?php endif; ?>

<?php
$countries = WC()->countries->get_countries();

/* ── Billing ── */
$b_name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$b_addr1   = $order->get_billing_address_1();
$b_addr2   = $order->get_billing_address_2();
$b_post    = $order->get_billing_postcode();
$b_city    = $order->get_billing_city();
$b_country = $countries[ $order->get_billing_country() ] ?? $order->get_billing_country();

/* ── Shipping ── */
$s_first   = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
$s_last    = $order->get_shipping_last_name()  ?: $order->get_billing_last_name();
$s_name    = trim( $s_first . ' ' . $s_last );
$s_addr1   = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
$s_addr2   = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
$s_post    = $order->get_shipping_postcode()  ?: $order->get_billing_postcode();
$s_city    = $order->get_shipping_city()      ?: $order->get_billing_city();
$s_ctry    = $countries[ $order->get_shipping_country() ?: $order->get_billing_country() ] ?? '';
?>

<!-- ── HERO ── -->
<div class="pp-ty-hero-section">
	<div class="pp-ty-check" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="34" height="34">
			<polyline points="20 6 9 17 4 12"/>
		</svg>
	</div>
	<h1>Vielen Dank, <?php echo esc_html( $order->get_billing_first_name() ); ?>!</h1>
	<p class="pp-ty-subtitle">Deine Bestellung ist bestätigt und wird bearbeitet.</p>
	<div class="pp-ty-order-badge">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9 9 15 9"/><polyline points="9 13 15 13"/><polyline points="9 17 13 17"/></svg>
		Bestellung <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>
	</div>
</div>

<!-- ── EMAIL NOTICE ── -->
<div class="pp-ty-email-notice">
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" style="vertical-align:middle;margin-right:6px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
	Eine Bestätigungs-E-Mail wurde an <strong><?php echo esc_html( $order->get_billing_email() ); ?></strong> gesendet.
</div>

<!-- ── BODY ── -->
<div class="pp-ty-body">
<div class="pp-ty-grid">

	<!-- ── LINKE SPALTE ── -->
	<div>

		<!-- Bestellte Produkte -->
		<div class="pp-ty-card">
			<div class="pp-ty-card__head">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<h2>Bestellte Produkte</h2>
			</div>
			<div class="pp-ty-items">
				<?php foreach ( $order->get_items() as $item_id => $item ) :
					$product = $item->get_product();
					if ( ! $product ) continue;
					$qty     = $item->get_quantity();
					$total   = $order->get_formatted_line_subtotal( $item );
					$img_id  = $product->get_image_id();
					$img_src = $img_id
						? wp_get_attachment_image_url( $img_id, 'thumbnail' )
						: wc_placeholder_img_src( 'thumbnail' );
				?>
				<div class="pp-ty-item">
					<div class="pp-ty-item__img-wrap">
						<img src="<?php echo esc_url( $img_src ); ?>"
						     alt="<?php echo esc_attr( $item->get_name() ); ?>"
						     class="pp-ty-item__img"
						     width="60" height="60" loading="lazy" />
						<span class="pp-ty-item__qty"><?php echo esc_html( $qty ); ?></span>
					</div>
					<span class="pp-ty-item__name"><?php echo esc_html( $item->get_name() ); ?></span>
					<span class="pp-ty-item__price"><?php echo $total; ?></span>
				</div>
				<?php endforeach; ?>
			</div>

			<!-- Totales -->
			<div class="pp-ty-totals">
				<div class="pp-ty-total-row">
					<span>Zwischensumme</span>
					<span><?php echo wc_price( $order->get_subtotal() ); ?></span>
				</div>
				<div class="pp-ty-total-row">
					<span>Lieferung</span>
					<span>
						<?php if ( (float) $order->get_shipping_total() > 0 ) : ?>
							<?php echo wc_price( $order->get_shipping_total() ); ?>
							<span class="pp-ty-via">via <?php echo esc_html( $order->get_shipping_method() ); ?></span>
						<?php else : ?>
							<span class="pp-ty-free">Kostenlos</span>
						<?php endif; ?>
					</span>
				</div>
				<?php foreach ( $order->get_coupons() as $coupon ) : ?>
				<div class="pp-ty-total-row" style="color:#16a34a;">
					<span>Gutschein (<?php echo esc_html( $coupon->get_code() ); ?>)</span>
					<span>−<?php echo wc_price( $coupon->get_discount() ); ?></span>
				</div>
				<?php endforeach; ?>
				<div class="pp-ty-total-row pp-ty-total-row--grand">
					<span>Gesamt</span>
					<span class="pp-ty-grand">
						<?php echo wc_price( $order->get_total() ); ?>
						<?php if ( $order->get_total_tax() > 0 ) : ?>
						<span class="pp-ty-tax">inkl. <?php echo wc_price( $order->get_total_tax() ); ?> MwSt.</span>
						<?php endif; ?>
					</span>
				</div>
			</div>
		</div>

		<!-- Buttons -->
		<div class="pp-ty-actions">
			<a href="<?php echo esc_url( wc_get_account_endpoint_url('orders') ); ?>" class="pp-ty-btn pp-ty-btn--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9 9 15 9"/><polyline points="9 13 15 13"/></svg>
				Meine Bestellungen
			</a>
			<a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>" class="pp-ty-btn pp-ty-btn--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				Weiter einkaufen
			</a>
		</div>

	</div><!-- /.pp-ty-left -->

	<!-- ── RECHTE SPALTE ── -->
	<div>

		<!-- Kontaktdaten -->
		<div class="pp-ty-card">
			<div class="pp-ty-card__head">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				<h2>Kontaktdaten</h2>
			</div>
			<div class="pp-ty-card__body">
				<div class="pp-ty-info-list">
					<div class="pp-ty-info-row">
						<svg class="pp-ty-info-row__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						<div>
							<div class="pp-ty-info-row__label">E-Mail</div>
							<div class="pp-ty-info-row__val"><?php echo esc_html( $order->get_billing_email() ); ?></div>
						</div>
					</div>
					<?php if ( $order->get_billing_phone() ) : ?>
					<div class="pp-ty-info-row">
						<svg class="pp-ty-info-row__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 4.18 2 2 0 0 1 5.09 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L9.91 9a16 16 0 0 0 5.09 5.09l.41-.41a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
						<div>
							<div class="pp-ty-info-row__label">Telefon</div>
							<div class="pp-ty-info-row__val"><?php echo esc_html( $order->get_billing_phone() ); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<div class="pp-ty-info-row">
						<svg class="pp-ty-info-row__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
						<div>
							<div class="pp-ty-info-row__label">Zahlungsart</div>
							<div class="pp-ty-info-row__val"><?php echo esc_html( $order->get_payment_method_title() ); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Adressen -->
		<div class="pp-ty-card">
			<div class="pp-ty-card__head">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
				<h2>Adressen</h2>
			</div>
			<div class="pp-ty-card__body">
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#888;margin-bottom:8px;">Rechnung</div>
						<address class="pp-ty-address">
							<?php echo esc_html( $b_name ); ?><br>
							<?php echo esc_html( $b_addr1 ); ?><br>
							<?php if ( $b_addr2 ) echo esc_html( $b_addr2 ) . '<br>'; ?>
							<?php echo esc_html( $b_post . ' ' . $b_city ); ?><br>
							<?php echo esc_html( $b_country ); ?>
						</address>
					</div>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#888;margin-bottom:8px;">Lieferung</div>
						<address class="pp-ty-address">
							<?php echo esc_html( $s_name ); ?><br>
							<?php echo esc_html( $s_addr1 ); ?><br>
							<?php if ( $s_addr2 ) echo esc_html( $s_addr2 ) . '<br>'; ?>
							<?php echo esc_html( $s_post . ' ' . $s_city ); ?><br>
							<?php echo esc_html( $s_ctry ); ?>
						</address>
					</div>
				</div>
			</div>
		</div>

	</div><!-- /.pp-ty-right -->

</div><!-- /.pp-ty-grid -->
</div><!-- /.pp-ty-body -->

<?php
do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
do_action( 'woocommerce_thankyou', $order->get_id() );
?>
