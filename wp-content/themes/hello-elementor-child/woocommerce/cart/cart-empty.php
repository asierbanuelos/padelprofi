<?php
/**
 * Empty cart page — PadelProfi (German override)
 * Overrides woocommerce/templates/cart/cart-empty.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<div class="pp-cart-empty">
	<p class="cart-empty"><?php esc_html_e( 'Dein Warenkorb ist leer.', 'hello-elementor-child' ); ?></p>
	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
	<p class="return-to-shop">
		<a class="button wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Zurück zum Shop', 'hello-elementor-child' ); ?>
		</a>
	</p>
	<?php endif; ?>
</div>
