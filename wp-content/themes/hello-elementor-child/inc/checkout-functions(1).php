<?php
/**
 * Checkout Functions - MediaMarkt Style
 * Hooks, filtros y assets para el checkout multi-step de PadelProfi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// 1. ASSETS — Solo en checkout y carrito
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'mm_enqueue_checkout_assets', 9999 );
function mm_enqueue_checkout_assets() {
	$uri = get_stylesheet_directory_uri();
	$dir = get_stylesheet_directory();

	// Thank you page
	if ( is_order_received_page() ) {
		wp_enqueue_style(
			'pp-thankyou',
			$uri . '/assets/css/thankyou.css',
			[],
			filemtime( $dir . '/assets/css/thankyou.css' )
		);
		return;
	}

	if ( ! is_checkout() && ! is_cart() ) {
		return;
	}

	wp_enqueue_style(
		'mm-checkout',
		$uri . '/assets/css/checkout.css',
		[],
		filemtime( $dir . '/assets/css/checkout.css' )
	);

	if ( is_checkout() ) {
		wp_enqueue_script(
			'mm-checkout-steps',
			$uri . '/assets/js/checkout-steps.js',
			[ 'jquery', 'wc-checkout' ],
			filemtime( $dir . '/assets/js/checkout-steps.js' ),
			true
		);

		wp_enqueue_script(
			'mm-checkout-payment',
			$uri . '/assets/js/checkout-payment.js',
			[ 'jquery', 'mm-checkout-steps' ],
			filemtime( $dir . '/assets/js/checkout-payment.js' ),
			true
		);

		wp_localize_script( 'mm-checkout-steps', 'mmCheckoutData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mm_checkout_nonce' ),
			'i18n'    => [
				'required'     => 'Bitte fülle alle Pflichtfelder aus.',
				'termsError'   => 'Bitte akzeptiere die AGB.',
				'couponOk'     => 'Gutschein erfolgreich angewendet.',
				'couponError'  => 'Gutschein ungültig oder bereits verwendet.',
				'couponEmpty'  => 'Bitte gib einen Gutscheincode ein.',
			],
		] );
	}
}

// ---------------------------------------------------------------------------
// 2. CAMPOS PERSONALIZADOS — NIF/NIE + reorden
// ---------------------------------------------------------------------------

add_filter( 'woocommerce_checkout_fields', 'mm_custom_checkout_fields' );
function mm_custom_checkout_fields( $fields ) {

	// Reordenar campos
	$fields['billing']['billing_first_name']['priority'] = 10;
	$fields['billing']['billing_last_name']['priority']  = 20;
	$fields['billing']['billing_email']['priority']      = 25;
	$fields['billing']['billing_phone']['priority']      = 30;
	$fields['billing']['billing_address_1']['priority']  = 40;
	$fields['billing']['billing_address_2']['priority'] = 45;
	$fields['billing']['billing_address_2']['required'] = false; // nunca obligatorio aunque algún plugin lo fuerce
	$fields['billing']['billing_postcode']['priority']   = 50;
	$fields['billing']['billing_city']['priority']       = 60;
	$fields['billing']['billing_country']['priority']    = 70;

	// Beschriftungen und Platzhalter auf Deutsch
	$fields['billing']['billing_first_name']['label']       = 'Vorname';
	$fields['billing']['billing_first_name']['placeholder'] = 'Vorname';
	$fields['billing']['billing_last_name']['label']        = 'Nachname';
	$fields['billing']['billing_last_name']['placeholder']  = 'Nachname';
	$fields['billing']['billing_email']['label']            = 'E-Mail-Adresse';
	$fields['billing']['billing_email']['placeholder']      = 'E-Mail-Adresse';
	$fields['billing']['billing_phone']['label']            = 'Telefon';
	$fields['billing']['billing_phone']['placeholder']      = 'Telefonnummer';
	$fields['billing']['billing_address_1']['label']        = 'Straße und Hausnummer';
	$fields['billing']['billing_address_1']['placeholder']  = 'Straße und Hausnummer';
	$fields['billing']['billing_address_2']['label']        = 'Adresszusatz (optional)';
	$fields['billing']['billing_address_2']['placeholder']  = 'Wohnung, Suite, Zimmer usw.';
	$fields['billing']['billing_postcode']['label']         = 'Postleitzahl';
	$fields['billing']['billing_postcode']['placeholder']   = 'PLZ';
	$fields['billing']['billing_city']['label']             = 'Stadt';
	$fields['billing']['billing_city']['placeholder']       = 'Stadt';
	$fields['billing']['billing_country']['label']          = 'Land';

	// Nicht benötigte Felder entfernen
	unset( $fields['billing']['billing_company'] ); // wird über Firmenrechnung-Toggle hinzugefügt
	unset( $fields['billing']['billing_state'] );   // Bundesland — nicht notwendig

	return $fields;
}

// ---------------------------------------------------------------------------
// 3. FACTURA DE EMPRESA — Toggle tras el formulario de facturación
// ---------------------------------------------------------------------------

add_action( 'woocommerce_after_checkout_billing_form', 'mm_add_empresa_toggle' );
function mm_add_empresa_toggle( $checkout ) {
	echo '<div class="mm-empresa-wrap">';

	woocommerce_form_field( 'billing_empresa', [
		'type'  => 'checkbox',
		'label' => 'Möchtest du eine Firmenrechnung?',
		'class' => [ 'form-row-wide', 'mm-empresa-checkbox' ],
	], $checkout->get_value( 'billing_empresa' ) );

	echo '<div class="mm-empresa-fields" aria-hidden="true">';
	woocommerce_form_field( 'billing_company', [
		'type'        => 'text',
		'label'       => 'Firmenname',
		'placeholder' => 'Firmenname / Handelsname',
		'class'       => [ 'form-row-wide' ],
	], $checkout->get_value( 'billing_company' ) );
	woocommerce_form_field( 'billing_vat', [
		'type'        => 'text',
		'label'       => 'USt-IdNr.',
		'placeholder' => 'Umsatzsteuer-Identifikationsnummer',
		'class'       => [ 'form-row-wide' ],
	], $checkout->get_value( 'billing_vat' ) );
	echo '</div>';
	echo '</div>';
}

// ---------------------------------------------------------------------------
// 4. GUARDAR CAMPOS CUSTOM EN EL PEDIDO
// ---------------------------------------------------------------------------

add_action( 'woocommerce_checkout_update_order_meta', 'mm_save_custom_checkout_fields' );
function mm_save_custom_checkout_fields( $order_id ) {
	if ( ! empty( $_POST['billing_nif'] ) ) {
		update_post_meta( $order_id, '_billing_nif', sanitize_text_field( $_POST['billing_nif'] ) );
	}
	if ( ! empty( $_POST['billing_vat'] ) ) {
		update_post_meta( $order_id, '_billing_vat', sanitize_text_field( $_POST['billing_vat'] ) );
	}
}

// Mostrar NIF en el admin del pedido
add_action( 'woocommerce_admin_order_data_after_billing_address', 'mm_display_nif_in_admin' );
function mm_display_nif_in_admin( $order ) {
	$nif = get_post_meta( $order->get_id(), '_billing_nif', true );
	if ( $nif ) {
		echo '<p><strong>' . esc_html__( 'NIF/NIE', 'hello-elementor-child' ) . ':</strong> ' . esc_html( $nif ) . '</p>';
	}
}

// ---------------------------------------------------------------------------
// 5. ETIQUETA DE ENVÍO — Añadir fecha estimada de entrega
// ---------------------------------------------------------------------------

add_filter( 'woocommerce_cart_shipping_method_full_label', 'mm_add_delivery_date_label', 10, 2 );
function mm_add_delivery_date_label( $label, $method ) {
	// Calcular 2 días laborables
	$count = 0;
	$ts    = time();
	while ( $count < 2 ) {
		$ts  = strtotime( '+1 day', $ts );
		$dow = (int) date( 'N', $ts );
		if ( $dow < 6 ) {
			$count++;
		}
	}
	$delivery_date = date_i18n( 'd/m/Y', $ts );
	$label        .= '<span class="mm-delivery-date-label">'
		. sprintf( 'Voraussichtliche Lieferung: %s', $delivery_date )
		. '</span>';
	return $label;
}

// ---------------------------------------------------------------------------
// 6. ORDENAR PASARELAS DE PAGO
// ---------------------------------------------------------------------------

add_filter( 'woocommerce_available_payment_gateways', 'mm_order_payment_gateways' );
function mm_order_payment_gateways( $gateways ) {
	$preferred = [ 'stripe', 'woocommerce_payments', 'ppec_paypal', 'paypal', 'bizum', 'bacs', 'cheque', 'cod' ];
	$ordered   = [];
	foreach ( $preferred as $id ) {
		if ( isset( $gateways[ $id ] ) ) {
			$ordered[ $id ] = $gateways[ $id ];
		}
	}
	foreach ( $gateways as $id => $gw ) {
		if ( ! isset( $ordered[ $id ] ) ) {
			$ordered[ $id ] = $gw;
		}
	}
	return $ordered;
}

// ---------------------------------------------------------------------------
// 7. BODY CLASS para el checkout
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// INTEGRACIÓN CON WOOCOMMERCE — Procesar pedido correctamente
// ---------------------------------------------------------------------------

// Combinar calle + número y copiar billing → shipping antes de procesar el pedido.
// PayPal PPCP necesita la dirección de envío en WC()->customer ANTES del submit.

// Helper: copia billing → shipping y actualiza WC()->customer
function mm_apply_shipping_from_billing( array $data ) {
	// billing_address_1 ya incluye calle + número (campo billing_streetnumber eliminado del form)
	$address = $data['billing_address_1'] ?? '';

	if ( ! WC()->customer ) return;

	WC()->customer->set_shipping_first_name( sanitize_text_field( $data['billing_first_name'] ?? '' ) );
	WC()->customer->set_shipping_last_name(  sanitize_text_field( $data['billing_last_name']  ?? '' ) );
	WC()->customer->set_shipping_address_1(  sanitize_text_field( $address ) );
	WC()->customer->set_shipping_address_2(  sanitize_text_field( $data['billing_address_2'] ?? '' ) );
	WC()->customer->set_shipping_city(       sanitize_text_field( $data['billing_city']       ?? '' ) );
	WC()->customer->set_shipping_postcode(   sanitize_text_field( $data['billing_postcode']   ?? '' ) );
	WC()->customer->set_shipping_country(    sanitize_text_field( $data['billing_country']    ?? 'DE' ) );
	WC()->customer->save();
}

// Disparado durante el AJAX update_order_review (PayPal PPCP lee el customer aquí)
add_action( 'woocommerce_checkout_update_order_review', 'mm_sync_address_on_review' );
function mm_sync_address_on_review( $post_data_string ) {
	parse_str( $post_data_string, $data );
	mm_apply_shipping_from_billing( $data );
}

// PayPal PPCP: cuando crea la orden de PayPal necesita la dirección en la sesión.
// Este hook asegura que la dirección esté disponible incluso si update_checkout
// no se disparó recientemente (habitual en móvil con PayPal en paso 4).
add_action( 'woocommerce_before_calculate_totals', 'mm_ensure_shipping_from_session', 5 );
function mm_ensure_shipping_from_session() {
	if ( ! WC()->customer || ! WC()->session ) return;

	// Solo actuar si el cliente no tiene dirección de envío pero sí de facturación
	if ( WC()->customer->get_shipping_address_1() ) return;

	$billing = [
		'billing_first_name' => WC()->customer->get_billing_first_name(),
		'billing_last_name'  => WC()->customer->get_billing_last_name(),
		'billing_address_1'  => WC()->customer->get_billing_address_1(),
		'billing_address_2'  => WC()->customer->get_billing_address_2(),
		'billing_city'       => WC()->customer->get_billing_city(),
		'billing_postcode'   => WC()->customer->get_billing_postcode(),
		'billing_country'    => WC()->customer->get_billing_country() ?: 'DE',
	];

	if ( ! empty( $billing['billing_address_1'] ) ) {
		mm_apply_shipping_from_billing( $billing );
	}
}

// Disparado durante el submit del formulario (segunda línea de defensa)
add_action( 'woocommerce_checkout_process', 'mm_sync_address_on_submit' );
function mm_sync_address_on_submit() {
	foreach ( [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state' ] as $field ) {
		if ( empty( $_POST[ 'shipping_' . $field ] ) && ! empty( $_POST[ 'billing_' . $field ] ) ) {
			$_POST[ 'shipping_' . $field ] = $_POST[ 'billing_' . $field ];
		}
	}
	mm_apply_shipping_from_billing( array_map( 'wp_unslash', $_POST ) );
}

// Filtro sobre los datos procesados por WooCommerce — copia billing → shipping
add_filter( 'woocommerce_checkout_posted_data', 'mm_fix_checkout_posted_data' );
function mm_fix_checkout_posted_data( $data ) {
	// Sanitizar PLZ: eliminar espacios, guiones y cualquier carácter no numérico.
	// En móvil el teclado/autofill puede introducir "1 3469" o "13-469" que WooCommerce rechaza.
	foreach ( [ 'billing_postcode', 'shipping_postcode' ] as $key ) {
		if ( ! empty( $data[ $key ] ) ) {
			$country = $data['billing_country'] ?? 'DE';
			if ( in_array( $country, [ 'DE', 'AT', 'CH' ], true ) ) {
				$data[ $key ] = preg_replace( '/[^0-9]/', '', $data[ $key ] );
			}
		}
	}

	foreach ( [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state' ] as $field ) {
		if ( empty( $data[ 'shipping_' . $field ] ) && ! empty( $data[ 'billing_' . $field ] ) ) {
			$data[ 'shipping_' . $field ] = $data[ 'billing_' . $field ];
		}
	}
	return $data;
}

// Guardar campos custom en el pedido al crearlo
add_action( 'woocommerce_checkout_create_order', 'mm_save_order_custom_fields', 10, 2 );
function mm_save_order_custom_fields( $order, $data ) {
	if ( ! empty( $_POST['billing_nif'] ) ) {
		$order->update_meta_data( '_billing_nif', sanitize_text_field( wp_unslash( $_POST['billing_nif'] ) ) );
	}
	if ( ! empty( $_POST['billing_vat'] ) ) {
		$order->update_meta_data( '_billing_vat', sanitize_text_field( wp_unslash( $_POST['billing_vat'] ) ) );
	}
}

// Mostrar NIF/CIF en el panel de administración del pedido
add_action( 'woocommerce_admin_order_data_after_billing_address', 'mm_display_nif_admin', 10, 1 );
function mm_display_nif_admin( $order ) {
	$nif = $order->get_meta( '_billing_nif' );
	$vat = $order->get_meta( '_billing_vat' );
	if ( $nif ) echo '<p><strong>NIF/NIE:</strong> ' . esc_html( $nif ) . '</p>';
	if ( $vat ) echo '<p><strong>CIF empresa:</strong> ' . esc_html( $vat ) . '</p>';
}

// Añadir NIF al email de confirmación
add_filter( 'woocommerce_email_order_meta_fields', 'mm_nif_email_field', 10, 3 );
function mm_nif_email_field( $fields, $sent_to_admin, $order ) {
	$nif = $order->get_meta( '_billing_nif' );
	if ( $nif ) {
		$fields['billing_nif'] = [
			'label' => 'NIF / NIE',
			'value' => esc_html( $nif ),
		];
	}
	return $fields;
}

// "Sendung 1" / "Paket 1" Titel entfernen
add_filter( 'woocommerce_shipping_package_name', '__return_empty_string' );

add_filter( 'body_class', 'mm_checkout_body_class' );
function mm_checkout_body_class( $classes ) {
	if ( is_checkout() && ! is_order_received_page() ) {
		$classes[] = 'mm-checkout-page';
	}
	return $classes;
}

// ---------------------------------------------------------------------------
// 8. FRAGMENTOS WooCommerce — Actualizar sidebar al cambiar envío/cupón
// ---------------------------------------------------------------------------

add_filter( 'woocommerce_update_order_review_fragments', 'mm_checkout_sidebar_fragments' );
function mm_checkout_sidebar_fragments( $fragments ) {
	$totals_html = mm_render_sidebar_totals();

	$fragments['#mm-sidebar-totals'] = '<div class="mm-order-summary__totals" id="mm-sidebar-totals">' . $totals_html . '</div>';
	$fragments['#mm-step4-totals']   = '<div class="mm-order-summary__totals mm-step4-totals" id="mm-step4-totals">' . $totals_html . '</div>';

	// Actualizar la sección de métodos de envío del paso 2 vía fragmento AJAX.
	// WooCommerce ya ha calculado el envío antes de llegar aquí, no hace falta recalcular.
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		ob_start();
		wc_cart_totals_shipping_html();
		$shipping_html = ob_get_clean();
		if ( $shipping_html ) {
			$fragments['.mm-wc-shipping-wrapper'] = '<div class="mm-wc-shipping-wrapper">' . $shipping_html . '</div>';
		}
	}

	return $fragments;
}

// ---------------------------------------------------------------------------
// 9. AJAX — Aplicar cupón desde el sidebar
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_mm_apply_coupon',        'mm_ajax_apply_coupon' );
add_action( 'wp_ajax_nopriv_mm_apply_coupon', 'mm_ajax_apply_coupon' );
function mm_ajax_apply_coupon() {
    check_ajax_referer( 'mm_checkout_nonce', 'nonce' );

    $code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) );

    if ( empty( $code ) ) {
        wp_send_json_error( [ 'message' => 'Bitte gib einen Gutscheincode ein.' ] );
    }

    if ( WC()->cart->has_discount( $code ) ) {
        wp_send_json_error( [ 'message' => 'Dieser Gutschein wurde bereits angewendet.' ] );
    }

    $result = WC()->cart->apply_coupon( $code );
    WC()->cart->calculate_totals();

    if ( $result ) {
        wp_send_json_success( [
            'message'     => 'Gutschein erfolgreich angewendet!',
            'totals_html' => mm_render_sidebar_totals(),
        ] );
    } else {
        $notices = wc_get_notices( 'error' );
        $msg = ! empty( $notices )
            ? wp_strip_all_tags( $notices[0]['notice'] )
            : 'Ungültiger Gutscheincode.';
        wc_clear_notices();
        wp_send_json_error( [ 'message' => $msg ] );
    }
}
// ---------------------------------------------------------------------------
// SIDEBAR HELPERS — Renderizan items y totales (reutilizados en AJAX)
// ---------------------------------------------------------------------------

function mm_render_sidebar_items() {
	ob_start();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) continue;

		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'thumbnail' );
		$qty   = (int) $cart_item['quantity'];
		$max   = $product->get_max_purchase_quantity();
		$max   = ( $max < 0 ) ? 99 : $max;
		?>
		<div class="mm-order-item" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">
			<?php if ( $thumb ) : ?>
				<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
					<img class="mm-order-item__img"
					     src="<?php echo esc_url( $thumb[0] ); ?>"
					     alt="<?php echo esc_attr( $product->get_name() ); ?>"
					     width="60" height="60" loading="lazy" />
				</a>
			<?php endif; ?>
			<div class="mm-order-item__info">
				<p class="mm-order-item__name"><?php echo esc_html( $product->get_name() ); ?></p>
				<div class="mm-order-item__controls">
					<button type="button" class="mm-qty-btn mm-qty-minus"
					        data-key="<?php echo esc_attr( $cart_item_key ); ?>"
					        <?php disabled( $qty, 1 ); ?> aria-label="Reducir cantidad">−</button>
					<span class="mm-qty-value"><?php echo esc_html( $qty ); ?></span>
					<button type="button" class="mm-qty-btn mm-qty-plus"
					        data-key="<?php echo esc_attr( $cart_item_key ); ?>"
					        <?php disabled( $qty, $max ); ?> aria-label="Aumentar cantidad">+</button>
				</div>
				<strong class="mm-order-item__price">
					<?php echo WC()->cart->get_product_subtotal( $product, $qty ); ?>
				</strong>
			</div>
			<button type="button" class="mm-item-remove"
			        data-key="<?php echo esc_attr( $cart_item_key ); ?>"
			        aria-label="<?php esc_attr_e( 'Eliminar producto', 'hello-elementor-child' ); ?>">×</button>
		</div>
		<?php
	}
	return ob_get_clean();
}

function mm_render_sidebar_totals() {
	ob_start();
	$ship_total = WC()->cart->get_shipping_total();
	?>
	<div class="mm-summary-row">
		<span>Zwischensumme</span>
		<span><?php echo WC()->cart->get_cart_subtotal(); ?></span>
	</div>
	<?php if ( WC()->cart->needs_shipping() ) : ?>
	<div class="mm-summary-row mm-summary-row--shipping">
		<span>Versand</span>
		<span><?php echo $ship_total > 0 ? wc_price( $ship_total ) : '<span class="mm-free">Kostenlos</span>'; ?></span>
	</div>
	<?php endif; ?>
	<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
	<div class="mm-summary-row mm-summary-row--coupon">
		<span>
			Gutschein: <strong><?php echo esc_html( $code ); ?></strong>
			<button type="button" class="mm-coupon-remove" data-coupon="<?php echo esc_attr( $code ); ?>">×</button>
		</span>
		<span class="mm-discount">−<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code, WC()->cart->display_cart_ex_tax ) ); ?></span>
	</div>
	<?php endforeach; ?>
	<div class="mm-summary-row mm-summary-row--total">
		<strong>Gesamt</strong>
		<strong><?php echo WC()->cart->get_total(); ?></strong>
	</div>
	<p class="mm-iva-note">Inkl. MwSt.</p>
	<?php
	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// AJAX — Actualizar cantidad de un item del carrito
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_mm_update_cart_qty',        'mm_ajax_update_cart_qty' );
add_action( 'wp_ajax_nopriv_mm_update_cart_qty', 'mm_ajax_update_cart_qty' );
function mm_ajax_update_cart_qty() {
	check_ajax_referer( 'mm_checkout_nonce', 'nonce' );

	$key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ?? '' ) );
	$qty = max( 1, intval( $_POST['quantity'] ?? 1 ) );

	if ( ! $key || ! isset( WC()->cart->get_cart()[ $key ] ) ) {
		wp_send_json_error( [ 'message' => __( 'Producto no encontrado.', 'hello-elementor-child' ) ] );
	}

	WC()->cart->set_quantity( $key, $qty, true );
	WC()->cart->calculate_totals();

	wp_send_json_success( [
		'items_html'  => mm_render_sidebar_items(),
		'totals_html' => mm_render_sidebar_totals(),
		'cart_count'  => WC()->cart->get_cart_contents_count(),
	] );
}

// ---------------------------------------------------------------------------
// AJAX — Eliminar un item del carrito
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_mm_remove_cart_item',        'mm_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_mm_remove_cart_item', 'mm_ajax_remove_cart_item' );
function mm_ajax_remove_cart_item() {
	check_ajax_referer( 'mm_checkout_nonce', 'nonce' );

	$key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ?? '' ) );

	if ( $key ) {
		WC()->cart->remove_cart_item( $key );
		WC()->cart->calculate_totals();
	}

	if ( WC()->cart->is_empty() ) {
		wp_send_json_success( [
			'empty'    => true,
			'redirect' => wc_get_checkout_url(),
		] );
	}

	wp_send_json_success( [
		'empty'       => false,
		'items_html'  => mm_render_sidebar_items(),
		'totals_html' => mm_render_sidebar_totals(),
		'cart_count'  => WC()->cart->get_cart_contents_count(),
	] );
}

// ---------------------------------------------------------------------------
// 9. AJAX — Eliminar cupón desde el sidebar
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_mm_remove_coupon',        'mm_ajax_remove_coupon' );
add_action( 'wp_ajax_nopriv_mm_remove_coupon', 'mm_ajax_remove_coupon' );
function mm_ajax_remove_coupon() {
	check_ajax_referer( 'mm_checkout_nonce', 'nonce' );

	$code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) );
	WC()->cart->remove_coupon( $code );
	WC()->cart->calculate_totals();

	wp_send_json_success( [ 'message' => __( 'Cupón eliminado.', 'hello-elementor-child' ) ] );
}
