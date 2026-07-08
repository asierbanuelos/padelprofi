<?php
/**
 * Empty cart page — PadelProfi (German override)
 * Overrides woocommerce/templates/cart/cart-empty.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
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
