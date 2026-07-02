<?php
/**
 * Cart Page - PadelProfi
 * AJAX handlers + helpers for the custom cart page template.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
// 1. Assets
// ============================================================================

add_action( 'wp_enqueue_scripts', 'pp_enqueue_cart_page_assets' );
function pp_enqueue_cart_page_assets() {
	if ( ! is_cart() && get_page_template_slug() !== 'template-cart.php' ) return;

	$uri = get_stylesheet_directory_uri();
	$dir = get_stylesheet_directory();

	wp_enqueue_style(
		'pp-cart-page',
		$uri . '/assets/css/cart-page.css',
		[],
		filemtime( $dir . '/assets/css/cart-page.css' )
	);

	wp_enqueue_script(
		'pp-cart-page',
		$uri . '/assets/js/cart-page.js',
		[ 'jquery' ],
		filemtime( $dir . '/assets/js/cart-page.js' ),
		true
	);

	wp_localize_script( 'pp-cart-page', 'ppCartPage', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'pp_cart_page_nonce' ),
	] );
}

// ============================================================================
// 2. Helper - resumen del pedido (reutilizable para respuestas AJAX)
// ============================================================================

function pp_cart_summary_html() {
	$cart = WC()->cart;
	if ( ! $cart ) return '';

	$subtotal = $cart->get_cart_subtotal();
	$total    = $cart->get_total();

	$discount_html = '';
	foreach ( $cart->get_coupons() as $code => $coupon ) {
		$discount = $cart->get_coupon_discount_amount( $code );
		$discount_html .= '<div class="pp-summary-row pp-summary-coupon">'
			. '<span>' . esc_html( strtoupper( $code ) ) . '</span>'
			. '<span>-' . wc_price( $discount ) . '</span>'
			. '<button class="pp-remove-coupon" data-coupon="' . esc_attr( $code ) . '">&times;</button>'
			. '</div>';
	}

	$shipping_html = '';
	if ( $cart->needs_shipping() && $cart->show_shipping() ) {
		$chosen_methods = (array) ( WC()->session ? WC()->session->get( 'chosen_shipping_methods', [] ) : [] );
		$packages       = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			$chosen = isset( $chosen_methods[ $i ] ) ? $chosen_methods[ $i ] : '';
			foreach ( $package['rates'] as $rate ) {
				if ( $rate->id === $chosen ) {
					$cost          = (float) $rate->get_cost();
					$shipping_html = $cost > 0
						? wc_price( $cost )
						: '<span class="pp-free-shipping">' . esc_html__( 'Kostenlos', 'hello-elementor-child' ) . '</span>';
				}
			}
		}
		if ( '' === $shipping_html ) {
			$shipping_html = esc_html__( 'Wird berechnet', 'hello-elementor-child' );
		}
	} else {
		$shipping_html = '<span class="pp-free-shipping">' . esc_html__( 'Kostenlos', 'hello-elementor-child' ) . '</span>';
	}

	$html  = '<div class="pp-summary-row">';
	$html .= '<span>' . esc_html__( 'Zwischensumme', 'hello-elementor-child' ) . '</span>';
	$html .= '<span>' . $subtotal . '</span>';
	$html .= '</div>';
	$html .= $discount_html;
	$html .= '<div class="pp-summary-row">';
	$html .= '<span>' . esc_html__( 'Versandkosten', 'hello-elementor-child' ) . '</span>';
	$html .= '<span>' . ( $shipping_html ? $shipping_html : '--' ) . '</span>';
	$html .= '</div>';
	$html .= '<div class="pp-summary-row pp-summary-total">';
	$html .= '<span>' . esc_html__( 'Gesamt', 'hello-elementor-child' ) . '</span>';
	$html .= '<span>' . $total . '</span>';
	$html .= '</div>';
	$html .= '<p class="pp-summary-tax">' . esc_html__( 'inkl. MwSt.', 'hello-elementor-child' ) . '</p>';

	return $html;
}

// ============================================================================
// 3. AJAX - Actualizar cantidad
// ============================================================================

add_action( 'wp_ajax_pp_cart_update_qty',        'pp_ajax_cart_update_qty' );
add_action( 'wp_ajax_nopriv_pp_cart_update_qty', 'pp_ajax_cart_update_qty' );
function pp_ajax_cart_update_qty() {
	check_ajax_referer( 'pp_cart_page_nonce', 'nonce' );

	$key   = sanitize_text_field( isset( $_POST['cart_item_key'] ) ? $_POST['cart_item_key'] : '' );
	$delta = (int) ( isset( $_POST['delta'] ) ? $_POST['delta'] : 0 );

	if ( ! $key || ! $delta || ! WC()->cart ) {
		wp_send_json_error( [ 'msg' => 'invalid' ] );
	}

	$cart  = WC()->cart;
	$items = $cart->get_cart();

	if ( ! isset( $items[ $key ] ) ) {
		wp_send_json_error( [ 'msg' => 'key not found' ] );
	}

	$current = (int) $items[ $key ]['quantity'];
	$new_qty = max( 0, $current + $delta );

	if ( $new_qty === 0 ) {
		$cart->remove_cart_item( $key );
		wp_send_json_success( [
			'removed'      => true,
			'summary_html' => pp_cart_summary_html(),
			'count'        => $cart->get_cart_contents_count(),
		] );
	}

	$cart->set_quantity( $key, $new_qty, true );
	$cart->calculate_totals();

	$items    = $cart->get_cart();
	$line_sub = isset( $items[ $key ] ) ? $items[ $key ]['line_subtotal'] : 0;

	wp_send_json_success( [
		'qty'          => $new_qty,
		'line_price'   => wc_price( $line_sub ),
		'summary_html' => pp_cart_summary_html(),
		'count'        => $cart->get_cart_contents_count(),
	] );
}

// ============================================================================
// 4. AJAX - Eliminar item
// ============================================================================

add_action( 'wp_ajax_pp_cart_remove_item',        'pp_ajax_cart_remove_item' );
add_action( 'wp_ajax_nopriv_pp_cart_remove_item', 'pp_ajax_cart_remove_item' );
function pp_ajax_cart_remove_item() {
	check_ajax_referer( 'pp_cart_page_nonce', 'nonce' );

	$key = sanitize_text_field( isset( $_POST['cart_item_key'] ) ? $_POST['cart_item_key'] : '' );
	if ( ! $key || ! WC()->cart ) {
		wp_send_json_error( [ 'msg' => 'invalid' ] );
	}

	WC()->cart->remove_cart_item( $key );
	WC()->cart->calculate_totals();

	wp_send_json_success( [
		'summary_html' => pp_cart_summary_html(),
		'count'        => WC()->cart->get_cart_contents_count(),
		'is_empty'     => WC()->cart->is_empty(),
	] );
}

// ============================================================================
// 5. AJAX - Aplicar / quitar cupon
// ============================================================================

add_action( 'wp_ajax_pp_cart_apply_coupon',        'pp_ajax_cart_apply_coupon' );
add_action( 'wp_ajax_nopriv_pp_cart_apply_coupon', 'pp_ajax_cart_apply_coupon' );
function pp_ajax_cart_apply_coupon() {
	check_ajax_referer( 'pp_cart_page_nonce', 'nonce' );

	$code = sanitize_text_field( wp_unslash( isset( $_POST['coupon_code'] ) ? $_POST['coupon_code'] : '' ) );
	if ( ! $code ) {
		wp_send_json_error( [ 'msg' => esc_html__( 'Bitte gib einen Gutscheincode ein.', 'hello-elementor-child' ) ] );
	}

	wc_clear_notices();
	WC()->cart->apply_coupon( $code );

	$notices = wc_get_notices( 'error' );
	if ( ! empty( $notices ) ) {
		$msg = is_array( $notices[0] ) ? ( isset( $notices[0]['notice'] ) ? $notices[0]['notice'] : '' ) : $notices[0];
		wc_clear_notices();
		wp_send_json_error( [ 'msg' => wp_strip_all_tags( $msg ) ] );
	}

	wc_clear_notices();
	wp_send_json_success( [ 'summary_html' => pp_cart_summary_html() ] );
}

add_action( 'wp_ajax_pp_cart_remove_coupon',        'pp_ajax_cart_remove_coupon' );
add_action( 'wp_ajax_nopriv_pp_cart_remove_coupon', 'pp_ajax_cart_remove_coupon' );
function pp_ajax_cart_remove_coupon() {
	check_ajax_referer( 'pp_cart_page_nonce', 'nonce' );

	$code = sanitize_text_field( wp_unslash( isset( $_POST['coupon_code'] ) ? $_POST['coupon_code'] : '' ) );
	if ( ! $code ) wp_send_json_error();

	WC()->cart->remove_coupon( $code );
	WC()->cart->calculate_totals();

	wp_send_json_success( [ 'summary_html' => pp_cart_summary_html() ] );
}

// ============================================================================
// 6. AJAX - Aniadir cross-sell al carrito
// ============================================================================

add_action( 'wp_ajax_pp_cart_add_crosssell',        'pp_ajax_cart_add_crosssell' );
add_action( 'wp_ajax_nopriv_pp_cart_add_crosssell', 'pp_ajax_cart_add_crosssell' );
function pp_ajax_cart_add_crosssell() {
	check_ajax_referer( 'pp_cart_page_nonce', 'nonce' );

	$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );
	if ( ! $product_id ) wp_send_json_error();

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		wp_send_json_error( [ 'msg' => esc_html__( 'Nicht verfuegbar', 'hello-elementor-child' ) ] );
	}

	$added = WC()->cart->add_to_cart( $product_id, 1 );
	if ( ! $added ) {
		wp_send_json_error( [ 'msg' => esc_html__( 'Konnte nicht hinzugefuegt werden', 'hello-elementor-child' ) ] );
	}

	WC()->cart->calculate_totals();
	wp_send_json_success( [
		'summary_html' => pp_cart_summary_html(),
		'count'        => WC()->cart->get_cart_contents_count(),
	] );
}

// ============================================================================
// 7. Render - pagina completa del carrito
//    El template HTML esta en inc/cart-page-tpl.php (se carga solo al renderizar)
// ============================================================================

function pp_render_cart_page() {
	$cart = WC()->cart;
	if ( ! $cart ) return '';

	if ( $cart->is_empty() ) {
		return '<div class="pp-cart-empty">'
			. '<p>' . esc_html__( 'Dein Warenkorb ist leer.', 'hello-elementor-child' ) . '</p>'
			. '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button">'
			. esc_html__( 'Zurueck zum Shop', 'hello-elementor-child' )
			. '</a></div>';
	}

	ob_start();
	include get_stylesheet_directory() . '/inc/cart-page-tpl.php';
	return ob_get_clean();
}
