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



// ---------------------------------------------------------------------------

// 10. Meta Pixel + Conversions API

// ---------------------------------------------------------------------------

if ( ! function_exists( 'ppd_meta_pixel_id' ) ) {

	function ppd_meta_pixel_id() {

		return '1612349459614065';

	}



	function ppd_meta_token() {

		return 'EAAM4KzARH0gBRjsiU9ZAMZBkH4TWe1toOZB8qM2yPp9GZAdtPUZArgb8wbW4ZAunjmxPy9DOxgdRx9HKTSVZAqRZC4uSQqiZAjSBhzLNoWf05to2UWEhhhLWX43LpZBeAligz02oX5aTx0FCTrwxmZAak0PBYbNGgDZAuSXIuczf6OOf2QZBUkrmtyV058VueZAonUisrLCgZDZD';

	}



	function ppd_meta_money( $value ) {

		return (float) number_format( (float) $value, 2, '.', '' );

	}



	function ppd_meta_hash( $value ) {

		$value = strtolower( trim( (string) $value ) );

		return '' === $value ? null : hash( 'sha256', $value );

	}



	function ppd_meta_url() {

		return esc_url_raw(

			( is_ssl() ? 'https' : 'http' ) . '://' . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '/' )

		);

	}



	function ppd_meta_cookie( $key ) {
		return isset( $_COOKIE[ $key ] ) ? sanitize_text_field( (string) $_COOKIE[ $key ] ) : '';
	}

	function ppd_meta_fbp() {
		$fbp = ppd_meta_cookie( '_fbp' );

		if ( preg_match( '/^fb\.1\.\d+\.\d+$/', $fbp ) ) {
			return $fbp;
		}

		return '';
	}

	function ppd_meta_fbc() {
		$fbc = ppd_meta_cookie( '_fbc' );

		if ( preg_match( '/^fb\.1\.\d+\.[A-Za-z0-9_-]+$/', $fbc ) ) {
			return $fbc;
		}

		$fbclid = isset( $_GET['fbclid'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['fbclid'] ) ) : '';

		if ( $fbclid && preg_match( '/^[A-Za-z0-9_-]+$/', $fbclid ) ) {
			return 'fb.1.' . time() . '.' . $fbclid;
		}

		return '';
	}

	function ppd_meta_user_data() {
		$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
		$ip_address = sanitize_text_field( explode( ',', (string) $ip_address )[0] );

		return array_filter(
			array(
				'client_ip_address' => $ip_address,
				'client_user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
				'fbp'               => ppd_meta_fbp(),
				'fbc'               => ppd_meta_fbc(),
			)
		);
	}

	function ppd_meta_product_identifier( $product ) {

		if ( ! $product ) {

			return '';

		}



		$sku = $product->get_sku();

		return $sku ? (string) $sku : (string) $product->get_id();

	}



	function ppd_meta_event_id( $event_name, $seed = '' ) {

		$seed = $seed ? (string) $seed : ppd_meta_url() . '|' . microtime( true ) . '|' . wp_rand();

		return 'ppd_' . strtolower( $event_name ) . '_' . hash( 'sha256', $seed );

	}



	function ppd_meta_product_data( $product, $quantity = 1 ) {

		$quantity = max( 1, (int) $quantity );

		$product_id = ppd_meta_product_identifier( $product );

		$price = function_exists( 'wc_get_price_to_display' ) ? wc_get_price_to_display( $product ) : $product->get_price();

		$price = ppd_meta_money( $price );



		return array(

			'content_type' => 'product',

			'content_ids'  => array( $product_id ),

			'contents'     => array(

				array(

					'id'         => $product_id,

					'quantity'   => $quantity,

					'item_price' => $price,

				),

			),

			'num_items'    => $quantity,

			'value'        => ppd_meta_money( $price * $quantity ),

			'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'EUR',

		);

	}



	function ppd_meta_checkout_data() {

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {

			return array();

		}



		$contents = array();

		$content_ids = array();

		$num_items = 0;



		foreach ( WC()->cart->get_cart() as $cart_item ) {

			$product = $cart_item['data'] ?? null;

			if ( ! $product ) {

				continue;

			}



			$quantity = max( 1, (int) ( $cart_item['quantity'] ?? 1 ) );

			$product_id = ppd_meta_product_identifier( $product );

			$line_total = isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : (float) $product->get_price() * $quantity;

			$item_price = $quantity > 0 ? ppd_meta_money( $line_total / $quantity ) : ppd_meta_money( $line_total );



			$content_ids[] = $product_id;

			$num_items += $quantity;

			$contents[] = array(

				'id'         => $product_id,

				'quantity'   => $quantity,

				'item_price' => $item_price,

			);

		}



		return array(

			'content_type' => 'product',

			'content_ids'  => array_values( array_unique( array_filter( $content_ids ) ) ),

			'contents'     => $contents,

			'num_items'    => $num_items,

			'value'        => ppd_meta_money( WC()->cart->get_total( 'edit' ) ),

			'currency'     => get_woocommerce_currency(),

		);

	}



	function ppd_meta_order_data( $order ) {

		$contents = array();

		$content_ids = array();

		$num_items = 0;



		foreach ( $order->get_items() as $item ) {

			$product = $item->get_product();

			if ( ! $product ) {

				continue;

			}



			$quantity = max( 1, (int) $item->get_quantity() );

			$product_id = ppd_meta_product_identifier( $product );

			$line_total = (float) $order->get_line_total( $item, true, true );

			$item_price = $quantity > 0 ? ppd_meta_money( $line_total / $quantity ) : ppd_meta_money( $line_total );



			$content_ids[] = $product_id;

			$num_items += $quantity;

			$contents[] = array(

				'id'         => $product_id,

				'quantity'   => $quantity,

				'item_price' => $item_price,

			);

		}



		return array(

			'content_type'   => 'product',

			'content_ids'    => array_values( array_unique( array_filter( $content_ids ) ) ),

			'contents'       => $contents,

			'num_items'      => $num_items,

			'value'          => ppd_meta_money( $order->get_total() ),

			'currency'       => $order->get_currency(),

			'order_id'       => (string) $order->get_id(),

			'transaction_id' => (string) $order->get_order_number(),

		);

	}



	function ppd_meta_order_user_data( $order ) {
		$customer_id = (int) $order->get_customer_id();

		return array_merge(
			ppd_meta_user_data(),
			array_filter(
				array(
					'em'          => ppd_meta_hash( $order->get_billing_email() ),
					'ph'          => ppd_meta_hash( preg_replace( '/[^0-9+]/', '', (string) $order->get_billing_phone() ) ),
					'fn'          => ppd_meta_hash( $order->get_billing_first_name() ),
					'ln'          => ppd_meta_hash( $order->get_billing_last_name() ),
					'ct'          => ppd_meta_hash( $order->get_billing_city() ),
					'zp'          => ppd_meta_hash( $order->get_billing_postcode() ),
					'country'     => ppd_meta_hash( $order->get_billing_country() ),
					'external_id' => $customer_id > 0 ? ppd_meta_hash( (string) $customer_id ) : null,
				)
			)
		);
	}

	function ppd_meta_send( $event_name, $event_id, $custom_data, $user_data = array() ) {

		if ( ! ppd_meta_token() ) {

			return false;

		}



		$payload = array(

			'data' => array(

				array(

					'event_name'       => $event_name,

					'event_time'       => time(),

					'event_id'         => $event_id,

					'action_source'    => 'website',

					'event_source_url' => ppd_meta_url(),

					'user_data'        => array_filter( $user_data ? $user_data : ppd_meta_user_data() ),

					'custom_data'      => array_filter( $custom_data ),

				),

			),

			'access_token' => ppd_meta_token(),

		);



		$response = wp_remote_post(

			'https://graph.facebook.com/v25.0/' . rawurlencode( ppd_meta_pixel_id() ) . '/events',

			array(

				'timeout' => 8,

				'headers' => array( 'Content-Type' => 'application/json' ),

				'body'    => wp_json_encode( $payload ),

			)

		);



		if ( is_wp_error( $response ) ) {

			error_log( 'PPD Meta CAPI error: ' . $response->get_error_message() );

			return false;

		}



		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {

			error_log( 'PPD Meta CAPI HTTP ' . $status_code . ': ' . wp_remote_retrieve_body( $response ) );

			return false;

		}



		return true;

	}



	function ppd_meta_checkout_event_id() {

		if ( function_exists( 'WC' ) && WC()->session ) {

			$event_id = WC()->session->get( 'ppd_meta_checkout_event_id' );

			if ( $event_id ) {

				return (string) $event_id;

			}



			$customer_seed = WC()->session->get_customer_id();

			$event_id = ppd_meta_event_id( 'InitiateCheckout', 'checkout|' . $customer_seed );

			WC()->session->set( 'ppd_meta_checkout_event_id', $event_id );

			return $event_id;

		}



		return ppd_meta_event_id( 'InitiateCheckout' );

	}



	function ppd_meta_purchase_event_id( $order ) {

		return 'ppd_purchase_' . hash( 'sha256', $order->get_id() . '|' . $order->get_order_key() );

	}

}



add_action(

	'wp_head',

	function() {

		if ( is_admin() ) {

			return;

		}

		?>

		<script>

		(function(w) {

			if ( typeof w.wfacp_analytics_data !== 'undefined' || typeof w.wfacp_checkout_data !== 'undefined' ) {

				w.ppd_meta_disable_funnelkit_meta_tracking = true;

			}



			function disableAnalyticsPayload(payload) {

				if (!payload || typeof payload !== 'object') {

					return payload;

				}

				payload.shouldRender = '0';

				payload.conversion_api = 'false';

				payload.pixel = payload.pixel || {};

				payload.pixel.id = '';

				payload.pixel.settings = Object.assign({}, payload.pixel.settings || {}, {

					page_view: 'false',

					checkout: 'false',

					payment: 'false',

					shipping: 'false',

					custom: 'false',

					add_to_cart: 'false'

				});

				return payload;

			}



			function disableCheckoutPayload(payload) {

				if (!payload || typeof payload !== 'object') {

					return payload;

				}

				payload.track_facebook = 'no';

				return payload;

			}



			var analyticsPayload = disableAnalyticsPayload(w.wfacp_analytics_data);

			Object.defineProperty(w, 'wfacp_analytics_data', {

				configurable: true,

				get: function() { return analyticsPayload; },

				set: function(value) { analyticsPayload = disableAnalyticsPayload(value); }

			});



			var checkoutPayload = disableCheckoutPayload(w.wfacp_checkout_data);

			Object.defineProperty(w, 'wfacp_checkout_data', {

				configurable: true,

				get: function() { return checkoutPayload; },

				set: function(value) { checkoutPayload = disableCheckoutPayload(value); }

			});

		})(window);

		window.ppdMetaPixelId = <?php echo wp_json_encode( ppd_meta_pixel_id() ); ?>;

		(function(w, d) {

			if (w.fbq) {

				return;

			}

			var n = function() {

				n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);

			};

			if (!w._fbq) {

				w._fbq = n;

			}

			n.push = n;

			n.loaded = true;

			n.version = '2.0';

			n.queue = [];

			var t = d.createElement('script');

			t.async = true;

			t.src = 'https://connect.facebook.net/en_US/fbevents.js';

			var s = d.getElementsByTagName('script')[0];

			s.parentNode.insertBefore(t, s);

			w.fbq = n;

			w.fbq('init', window.ppdMetaPixelId);

			w.fbq('track', 'PageView');

		})(window, document);

		</script>

		<?php

	},

	1

);



add_action(

	'wp_footer',

	function() {

		if ( is_admin() ) {

			return;

		}



		$browser_events = array();



		if ( function_exists( 'is_product' ) && is_product() ) {

			$product = wc_get_product( get_the_ID() );

			if ( $product ) {

				$event_id = ppd_meta_event_id( 'ViewContent', 'product|' . get_the_ID() );

				$custom_data = ppd_meta_product_data( $product, 1 );

				$browser_events[] = array(

					'name' => 'ViewContent',

					'id'   => $event_id,

					'data' => $custom_data,

				);

				ppd_meta_send( 'ViewContent', $event_id, $custom_data );

			}

		}



		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {

			$event_id = ppd_meta_checkout_event_id();

			$custom_data = ppd_meta_checkout_data();

			if ( ! empty( $custom_data ) ) {

				$browser_events[] = array(

					'name' => 'InitiateCheckout',

					'id'   => $event_id,

					'data' => $custom_data,

				);

				ppd_meta_send( 'InitiateCheckout', $event_id, $custom_data );

			}

		}



		if ( empty( $browser_events ) ) {

			return;

		}

		?>

		<script>

		(function(events) {

			if (!window.fbq || !Array.isArray(events)) {

				return;

			}

			events.forEach(function(eventItem) {

				window.fbq('track', eventItem.name, eventItem.data || {}, {eventID: eventItem.id});

			});

		})(<?php echo wp_json_encode( $browser_events ); ?>);

		</script>

		<?php

	},

	20

);



add_action(

	'woocommerce_add_to_cart',

	function( $cart_item_key, $product_id, $quantity, $variation_id ) {

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product ) {

			return;

		}



		$event_id = ppd_meta_event_id( 'AddToCart', 'cart|' . $cart_item_key );

		ppd_meta_send( 'AddToCart', $event_id, ppd_meta_product_data( $product, $quantity ) );

	},

	20,

	4

);



add_action(

	'woocommerce_thankyou',

	function( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {

			return;

		}



		$event_id = ppd_meta_purchase_event_id( $order );

		$custom_data = ppd_meta_order_data( $order );

		?>

		<script>

		(function(eventData, eventId) {

			if (!window.fbq) {

				return;

			}

			window.fbq('track', 'Purchase', eventData || {}, {eventID: eventId});

		})(<?php echo wp_json_encode( $custom_data ); ?>, <?php echo wp_json_encode( $event_id ); ?>);

		</script>

		<?php



		if ( 'yes' === $order->get_meta( '_ppd_meta_purchase_sent', true ) ) {

			return;

		}



		if ( ppd_meta_send( 'Purchase', $event_id, $custom_data, ppd_meta_order_user_data( $order ) ) ) {

			$order->update_meta_data( '_ppd_meta_purchase_sent', 'yes' );

			$order->save();

		}

	},

	20

);



// ---------------------------------------------------------------------------

// 11. Google Analytics 4 Ecommerce

// ---------------------------------------------------------------------------

if ( ! function_exists( 'ppd_ga4_measurement_id' ) ) {

	function ppd_ga4_measurement_id() {

		return 'G-ZK15KMG2LM';

	}



	function ppd_ga4_item_from_product( $product, $quantity = 1 ) {

		if ( ! $product ) {

			return array();

		}



		$quantity = max( 1, (int) $quantity );

		$item_id = ppd_meta_product_identifier( $product );

		$price = function_exists( 'wc_get_price_to_display' ) ? wc_get_price_to_display( $product ) : $product->get_price();

		$categories = array();

		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {

			foreach ( array_values( $terms ) as $index => $term ) {

				if ( $index > 4 ) {

					break;

				}

				$categories[ $index ] = $term->name;

			}

		}



		$item = array(

			'item_id'   => $item_id,

			'item_name' => $product->get_name(),

			'price'     => ppd_meta_money( $price ),

			'quantity'  => $quantity,

		);



		if ( ! empty( $categories[0] ) ) {

			$item['item_category'] = $categories[0];

		}

		if ( ! empty( $categories[1] ) ) {

			$item['item_category2'] = $categories[1];

		}

		if ( ! empty( $categories[2] ) ) {

			$item['item_category3'] = $categories[2];

		}

		if ( ! empty( $categories[3] ) ) {

			$item['item_category4'] = $categories[3];

		}

		if ( ! empty( $categories[4] ) ) {

			$item['item_category5'] = $categories[4];

		}



		return array_filter( $item, static function( $value ) {

			return '' !== $value && null !== $value;

		} );

	}



	function ppd_ga4_checkout_payload() {

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {

			return array();

		}



		$items = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {

			$product = $cart_item['data'] ?? null;

			if ( ! $product ) {

				continue;

			}

			$items[] = ppd_ga4_item_from_product( $product, (int) ( $cart_item['quantity'] ?? 1 ) );

		}



		return array(

			'currency' => get_woocommerce_currency(),

			'value'    => ppd_meta_money( WC()->cart->get_total( 'edit' ) ),

			'items'    => array_values( array_filter( $items ) ),

		);

	}



	function ppd_ga4_order_payload( $order ) {

		$items = array();

		foreach ( $order->get_items() as $item ) {

			$product = $item->get_product();

			if ( ! $product ) {

				continue;

			}

			$items[] = ppd_ga4_item_from_product( $product, (int) $item->get_quantity() );

		}



		return array(

			'transaction_id' => (string) $order->get_order_number(),

			'currency'       => $order->get_currency(),

			'value'          => ppd_meta_money( $order->get_total() ),

			'tax'            => ppd_meta_money( $order->get_total_tax() ),

			'shipping'       => ppd_meta_money( $order->get_shipping_total() ),

			'items'          => array_values( array_filter( $items ) ),

		);

	}



	function ppd_ga4_product_payload( $product, $quantity = 1 ) {

		$quantity = max( 1, (int) $quantity );

		$price = function_exists( 'wc_get_price_to_display' ) ? wc_get_price_to_display( $product ) : $product->get_price();

		$price = ppd_meta_money( $price );



		return array(

			'currency' => get_woocommerce_currency(),

			'value'    => ppd_meta_money( $price * $quantity ),

			'items'    => array( ppd_ga4_item_from_product( $product, $quantity ) ),

		);

	}

}



add_action(

	'wp_head',

	function() {

		if ( is_admin() ) {

			return;

		}

		?>

		<script>
		window.dataLayer = window.dataLayer || [];
		window.ppdPushGa4Event = window.ppdPushGa4Event || function(eventName, payload) {
			window.dataLayer.push({ ecommerce: null });
			window.dataLayer.push({
				event: eventName,
				ecommerce: payload || {}
			});
		};
		</script>
		<?php
	},
	30
);



add_action(

	'wp_footer',

	function() {

		if ( is_admin() ) {

			return;

		}



		$ga_events = array();



		if ( function_exists( 'is_product' ) && is_product() ) {

			$product = wc_get_product( get_the_ID() );

			if ( $product ) {

				$ga_events[] = array(

					'name'   => 'view_item',

					'params' => ppd_ga4_product_payload( $product, 1 ),

				);

			}

		}



		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {

			$payload = ppd_ga4_checkout_payload();

			if ( ! empty( $payload['items'] ) ) {

				$ga_events[] = array(

					'name'   => 'begin_checkout',

					'params' => $payload,

				);

			}

		}



		if ( empty( $ga_events ) ) {

			return;

		}

		?>

		<script>
		(function(events) {
			if (typeof window.ppdPushGa4Event !== 'function' || !Array.isArray(events)) {
				return;
			}
			events.forEach(function(eventItem) {
				window.ppdPushGa4Event(eventItem.name, eventItem.params || {});
			});
		})(<?php echo wp_json_encode( $ga_events ); ?>);
		</script>
		<?php

	},

	40

);



add_action(

	'wp_footer',

	function() {

		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {

			return;

		}



		$product = wc_get_product( get_the_ID() );

		if ( ! $product ) {

			return;

		}

		?>

		<script>

		(function(basePayload) {
			if (typeof window.ppdPushGa4Event !== 'function' || !basePayload || !Array.isArray(basePayload.items) || !basePayload.items.length) {
				return;
			}


			function clonePayload(quantity) {

				var payload = JSON.parse(JSON.stringify(basePayload));

				var qty = Math.max(1, parseInt(quantity || 1, 10) || 1);

				if (payload.items[0]) {

					payload.items[0].quantity = qty;

					if (typeof payload.items[0].price === 'number') {

						payload.value = Number((payload.items[0].price * qty).toFixed(2));

					}

				}

				return payload;

			}



			document.addEventListener('click', function(event) {

				var button = event.target.closest('button.single_add_to_cart_button, #sticky-buy-now');

				var form, quantityInput, quantity;

				if (!button) {

					return;

				}



				form = button.closest('form.cart');
				quantityInput = form ? form.querySelector('input.qty') : null;
				quantity = quantityInput ? quantityInput.value : 1;
				window.ppdPushGa4Event('add_to_cart', clonePayload(quantity));
			}, true);
		})(<?php echo wp_json_encode( ppd_ga4_product_payload( $product, 1 ) ); ?>);
		</script>
		<?php

	},

	45

);



add_action(

	'wp_footer',

	function() {

		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() || ! function_exists( 'WC' ) || ! WC()->cart ) {

			return;

		}

		$payload = ppd_ga4_checkout_payload();

		if ( empty( $payload['items'] ) ) {

			return;

		}

		?>

		<script>
		if (typeof window.ppdPushGa4Event === 'function') {
			window.ppdPushGa4Event('view_cart', <?php echo wp_json_encode( $payload ); ?>);
		}
		</script>
		<?php

	},

	41

);



add_action(

	'wp_footer',

	function() {

		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {

			return;

		}



		$payload = ppd_ga4_checkout_payload();

		if ( empty( $payload['items'] ) ) {

			return;

		}

		?>

		<script>

		(function(basePayload) {

			var sent = false;

			if (typeof window.ppdPushGa4Event !== 'function' || !basePayload || !Array.isArray(basePayload.items) || !basePayload.items.length) {
				return;
			}


			function emit(method) {

				var payload;

				if (sent) {

					return;

				}

				sent = true;

				payload = JSON.parse(JSON.stringify(basePayload));
				if (method) {
					payload.payment_type = method;
				}
				window.ppdPushGa4Event('add_payment_info', payload);
			}


			document.addEventListener('change', function(event) {

				var input = event.target.closest('input[name="payment_method"], input[name^="payment_method"]');

				if (!input || !input.checked) {

					return;

				}

				emit(input.value || '');

			}, true);



			document.addEventListener('click', function(event) {

				var button = event.target.closest('#place_order, .wfacp-order-place-btn, button[name="woocommerce_checkout_place_order"]');

				var selected;

				if (!button) {

					return;

				}

				selected = document.querySelector('input[name="payment_method"]:checked, input[name^="payment_method"]:checked');

				emit(selected ? (selected.value || '') : '');

			}, true);

		})(<?php echo wp_json_encode( $payload ); ?>);

		</script>

		<?php

	},

	42

);



add_action(

	'woocommerce_thankyou',

	function( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {

			return;

		}

		$payload = ppd_ga4_order_payload( $order );

		?>

		<script>
		(function(payload) {
			if (typeof window.ppdPushGa4Event !== 'function') {
				return;
			}
			window.ppdPushGa4Event('purchase', payload || {});
		})(<?php echo wp_json_encode( $payload ); ?>);
		</script>
		<?php

	},

	40

);

