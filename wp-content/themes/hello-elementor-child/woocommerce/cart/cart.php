<?php
/**
 * Custom Cart Page — PadelProfi
 * Overrides woocommerce/templates/cart/cart.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Cuando el carrito está vacío, WooCommerce añade un aviso inglés antes de que
// renderice nuestro template personalizado. Lo suprimimos para evitar el duplicado.
if ( WC()->cart && WC()->cart->is_empty() ) {
    wc_clear_notices();
}

do_action( 'woocommerce_before_cart' );

echo pp_render_cart_page();

do_action( 'woocommerce_after_cart' );
