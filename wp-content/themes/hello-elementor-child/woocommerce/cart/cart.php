<?php
/**
 * Custom Cart Page — PadelProfi
 * Overrides woocommerce/templates/cart/cart.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'woocommerce_before_cart' );

echo pp_render_cart_page();

do_action( 'woocommerce_after_cart' );
