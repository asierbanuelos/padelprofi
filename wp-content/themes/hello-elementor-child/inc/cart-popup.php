<?php
/**
 * Cart Popup — PadelProfi
 * - Modal "Añadido al carrito" con productos relacionados por categoría
 * - Panel admin para gestionar qué productos aparecen por categoría
 * - Shortcode [fk_cart_menu]
 * - Página de checkout con carrito vacío
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
// 1. ASSETS — solo en frontend (no admin)
// ============================================================================

add_action( 'wp_enqueue_scripts', 'pp_enqueue_cart_popup_assets' );
function pp_enqueue_cart_popup_assets() {
	$uri = get_stylesheet_directory_uri();
	$dir = get_stylesheet_directory();

	wp_enqueue_style(
		'pp-cart-popup',
		$uri . '/assets/css/cart-popup.css',
		[],
		filemtime( $dir . '/assets/css/cart-popup.css' )
	);

	wp_enqueue_script(
		'pp-cart-popup',
		$uri . '/assets/js/cart-popup.js',
		[ 'jquery' ],
		filemtime( $dir . '/assets/js/cart-popup.js' ),
		true
	);

	wp_localize_script( 'pp-cart-popup', 'ppCartPopup', [
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'pp_cart_popup_nonce' ),
		'checkoutUrl' => wc_get_checkout_url(),
		'cartUrl'     => wc_get_cart_url(),
		'i18n'        => [
			'added'        => 'Dein Produkt wurde erfolgreich zum Warenkorb hinzugefügt',
			'related'      => 'Das könnte dich auch interessieren',
			'keepShopping' => 'Weiter einkaufen',
			'goToCart'     => 'Zum Warenkorb',
			'addToCart'    => 'In den Warenkorb',
			'loading'      => 'Laden…',
		],
	] );
}

// ============================================================================
// 2. AJAX — Obtener contenido del popup
// ============================================================================

add_action( 'wp_ajax_pp_get_cart_popup',        'pp_ajax_get_cart_popup' );
add_action( 'wp_ajax_nopriv_pp_get_cart_popup', 'pp_ajax_get_cart_popup' );
function pp_ajax_get_cart_popup() {
	check_ajax_referer( 'pp_cart_popup_nonce', 'nonce' );

	$product_id = absint( $_POST['product_id'] ?? 0 );
	if ( ! $product_id ) wp_send_json_error( [ 'msg' => 'no product_id' ] );

	$product = wc_get_product( $product_id );
	if ( ! $product ) wp_send_json_error( [ 'msg' => 'product not found' ] );

	// ── Imagen del producto añadido ──────────────────────────────────────────
	$img_src = wc_placeholder_img_src( 'woocommerce_thumbnail' );
	if ( $product->get_image_id() ) {
		$img = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );
		if ( $img ) $img_src = $img[0];
	}

	$regular_raw_main = (float) $product->get_regular_price();
	$sale_raw_main    = (float) $product->get_sale_price();
	$discount_main    = 0;
	if ( $product->is_on_sale() && $regular_raw_main > 0 && $sale_raw_main > 0 ) {
		$discount_main = (int) round( 100 - ( $sale_raw_main / $regular_raw_main * 100 ) );
	}

	// Cantidad actual en el carrito para este producto
	$cart_item_key = '';
	$cart_qty      = 1;
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $key => $ci ) {
			if ( absint( $ci['product_id'] ) === $product_id ) {
				$cart_item_key = $key;
				$cart_qty      = (int) $ci['quantity'];
				break;
			}
		}
	}

	$product_data = [
		'id'            => $product_id,
		'name'          => $product->get_name(),
		'url'           => get_permalink( $product_id ),
		'price_html'    => $product->get_price_html(),
		'image'         => $img_src,
		'regular_price' => $regular_raw_main ? wc_price( $regular_raw_main ) : '',
		'sale_price'    => $sale_raw_main    ? wc_price( $sale_raw_main )    : '',
		'on_sale'       => $product->is_on_sale(),
		'discount_pct'  => $discount_main,
		'cart_qty'      => $cart_qty,
	];

	// ── Contexto del carrito actual ─────────────────────────────────────────
	$cart_product_ids    = [ $product_id ];
	$cart_category_slugs = [];
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $ci ) {
			$cart_product_ids[] = absint( $ci['product_id'] );
			$terms = wp_get_post_terms( $ci['product_id'], 'product_cat', [ 'fields' => 'slugs' ] );
			if ( ! is_wp_error( $terms ) ) {
				$cart_category_slugs = array_merge( $cart_category_slugs, $terms );
			}
		}
	}
	$cart_product_ids    = array_unique( $cart_product_ids );
	$cart_category_slugs = array_unique( $cart_category_slugs );

	// ── Obtener IDs de relacionados ──────────────────────────────────────────
	// Prioridad pensada para que los recomendados sean COMPLEMENTOS, no productos
	// del mismo nivel/categoría que el añadido:
	//   1) Reglas de cross-sell por categoría (configuradas en el admin: pala → pelotas/bolsas)
	//   2) Relacionados manuales configurados para la categoría
	//   3) Relacionados nativos de WooCommerce
	//   4) Fallback: más vendidos de la misma categoría
	$related_ids = pp_get_crosssell_ids( $product_id, $cart_product_ids );

	if ( empty( $related_ids ) ) {
		$related_ids = pp_get_related_product_ids_for_product( $product_id, $cart_product_ids );
	}

	if ( empty( $related_ids ) ) {
		$native      = wc_get_related_products( $product_id, 8 );
		$related_ids = array_values( array_diff( $native, $cart_product_ids ) );
	}

	if ( empty( $related_ids ) ) {
		$product_slugs = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'slugs' ] );
		$product_slugs = is_wp_error( $product_slugs ) ? [] : $product_slugs;
		$fallback      = wc_get_products( [
			'category' => $product_slugs,
			'limit'    => 8,
			'orderby'  => 'popularity',
			'status'   => 'publish',
			'exclude'  => $cart_product_ids,
			'return'   => 'ids',
		] );
		if ( ! is_wp_error( $fallback ) ) $related_ids = $fallback;
	}

	// ── Construir datos de cada relacionado ──────────────────────────────────
	// Iteramos más IDs de los necesarios para poder rellenar 4 con stock
	$related_data = [];

	// Precio efectivo del producto añadido (ya con descuento si lo tiene).
	// Los recomendados nunca deben mostrarse al mismo nivel o más caros que esto.
	$main_price = (float) $product->get_price();

	foreach ( array_slice( (array) $related_ids, 0, 20 ) as $rid ) {
		if ( count( $related_data ) >= 4 ) break;

		$rid = absint( $rid );
		if ( ! $rid || $rid === $product_id ) continue;

		$rp = wc_get_product( $rid );

		// Saltar si no existe, no es comprable o NO tiene stock
		if ( ! $rp || ! $rp->is_purchasable() || ! $rp->is_in_stock() ) continue;

		// Los recomendados deben ser complementos más económicos: descartamos
		// cualquier producto cuyo precio sea igual o superior al añadido.
		$rp_price = (float) $rp->get_price();
		if ( $main_price > 0 && $rp_price > 0 && $rp_price >= $main_price ) continue;

		$rp_img = wc_placeholder_img_src( 'woocommerce_thumbnail' );
		if ( $rp->get_image_id() ) {
			$rp_img_src = wp_get_attachment_image_src( $rp->get_image_id(), 'woocommerce_thumbnail' );
			if ( $rp_img_src ) $rp_img = $rp_img_src[0];
		}

		$reg_raw  = (float) $rp->get_regular_price();
		$sale_raw = (float) $rp->get_sale_price();
		$pct      = 0;
		if ( $rp->is_on_sale() && $reg_raw > 0 && $sale_raw > 0 ) {
			$pct = (int) round( 100 - ( $sale_raw / $reg_raw * 100 ) );
		}

		$related_data[] = [
			'id'                => $rid,
			'name'              => $rp->get_name(),
			'url'               => get_permalink( $rid ),
			'price_html'        => $rp->get_price_html(),
			'image'             => $rp_img,
			'on_sale'           => $rp->is_on_sale(),
			'regular_price'     => $reg_raw  ? wc_price( $reg_raw )  : '',
			'sale_price'        => $sale_raw ? wc_price( $sale_raw ) : '',
			'regular_price_raw' => $reg_raw,
			'sale_price_raw'    => $sale_raw,
			'discount_pct'      => $pct,
			'rating'            => round( $rp->get_average_rating(), 1 ),
			'review_count'      => $rp->get_review_count(),
			'type'              => $rp->get_type(),
			'in_stock'          => true, // siempre true aquí, ya filtramos arriba
		];
	}

	wp_send_json_success( [
		'product' => $product_data,
		'related' => $related_data,
	] );
}

// ============================================================================
// 2b. AJAX — Actualizar cantidad de un producto en el carrito
// ============================================================================

add_action( 'wp_ajax_pp_update_cart_qty',        'pp_ajax_update_cart_qty' );
add_action( 'wp_ajax_nopriv_pp_update_cart_qty', 'pp_ajax_update_cart_qty' );
function pp_ajax_update_cart_qty() {
	check_ajax_referer( 'pp_cart_popup_nonce', 'nonce' );

	$product_id = absint( $_POST['product_id'] ?? 0 );
	$delta      = (int) ( $_POST['delta'] ?? 0 );

	if ( ! $product_id || ! $delta || ! WC()->cart ) {
		wp_send_json_error( [ 'msg' => 'invalid params' ] );
	}

	$cart_item_key = '';
	$current_qty   = 0;
	foreach ( WC()->cart->get_cart() as $key => $ci ) {
		if ( absint( $ci['product_id'] ) === $product_id ) {
			$cart_item_key = $key;
			$current_qty   = (int) $ci['quantity'];
			break;
		}
	}

	if ( ! $cart_item_key ) {
		wp_send_json_error( [ 'msg' => 'item not in cart' ] );
	}

	$new_qty = max( 0, $current_qty + $delta );

	if ( $new_qty === 0 ) {
		WC()->cart->remove_cart_item( $cart_item_key );
	} else {
		WC()->cart->set_quantity( $cart_item_key, $new_qty, true );
	}

	wp_send_json_success( [ 'qty' => $new_qty ] );
}

/**
 * Devuelve los product IDs configurados manualmente para las categorías del producto.
 */
function pp_get_related_product_ids_for_product( $product_id, $exclude_ids = [] ) {
	$settings   = get_option( 'pp_popup_related_products', [] );
	$categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'slugs' ] );
	if ( is_wp_error( $categories ) ) $categories = [];
	$result = [];

	foreach ( $categories as $slug ) {
		if ( ! empty( $settings[ $slug ] ) && is_array( $settings[ $slug ] ) ) {
			$result = array_merge( $result, $settings[ $slug ] );
		}
	}

	if ( empty( $result ) ) {
		$terms = wp_get_post_terms( $product_id, 'product_cat' );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term->parent ) {
					$parent = get_term( $term->parent, 'product_cat' );
					if ( $parent && ! is_wp_error( $parent ) && ! empty( $settings[ $parent->slug ] ) ) {
						$result = array_merge( $result, $settings[ $parent->slug ] );
					}
				}
			}
		}
	}

	$exclude_ids = array_map( 'absint', (array) $exclude_ids );
	$result      = array_unique( array_filter( array_map( 'absint', $result ) ) );
	$result      = array_values( array_diff( $result, $exclude_ids ) );

	return array_slice( $result, 0, 20 ); // devolvemos más para que el filtro de stock tenga margen
}

/**
 * Devuelve IDs de cross-sell basados en reglas de categoría configuradas.
 */
function pp_get_crosssell_ids( $product_id, $exclude_ids = [] ) {
	$rules      = get_option( 'pp_popup_crosssell_rules', [] );
	$categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'slugs' ] );
	if ( is_wp_error( $categories ) ) return [];

	$crosssell_slugs = [];
	foreach ( $categories as $slug ) {
		if ( ! empty( $rules[ $slug ] ) && is_array( $rules[ $slug ] ) ) {
			$crosssell_slugs = array_merge( $crosssell_slugs, $rules[ $slug ] );
		}
	}

	$crosssell_slugs = array_unique( array_filter( $crosssell_slugs ) );
	if ( empty( $crosssell_slugs ) ) return [];

	$exclude_ids = array_map( 'absint', (array) $exclude_ids );

	$results = wc_get_products( [
		'category'   => $crosssell_slugs,
		'limit'      => 20,
		'orderby'    => 'popularity',
		'status'     => 'publish',
		'exclude'    => $exclude_ids,
		'return'     => 'ids',
		'stock_status' => 'instock', // ← solo productos con stock
	] );

	if ( is_wp_error( $results ) ) return [];
	return array_values( $results );
}

// ============================================================================
// 3. ADMIN — Página de gestión del popup
// ============================================================================

add_action( 'admin_menu', 'pp_register_popup_admin_page' );
function pp_register_popup_admin_page() {
	add_submenu_page(
		'woocommerce',
		__( 'Popup Carrito', 'hello-elementor-child' ),
		__( 'Popup Carrito', 'hello-elementor-child' ),
		'manage_woocommerce',
		'pp-cart-popup-settings',
		'pp_render_popup_admin_page'
	);
}

add_action( 'admin_init', 'pp_save_popup_settings' );
function pp_save_popup_settings() {
	if (
		! isset( $_POST['pp_popup_save'] ) ||
		! check_admin_referer( 'pp_popup_settings', 'pp_popup_nonce' )
	) {
		return;
	}

	$categories  = pp_get_managed_categories();
	$new_options = [];

	foreach ( $categories as $cat_slug => $cat_label ) {
		$ids = [];
		if ( ! empty( $_POST[ 'pp_related_' . $cat_slug ] ) ) {
			$raw = is_array( $_POST[ 'pp_related_' . $cat_slug ] )
				? $_POST[ 'pp_related_' . $cat_slug ]
				: explode( ',', $_POST[ 'pp_related_' . $cat_slug ] );
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
		}
		$new_options[ $cat_slug ] = $ids;
	}

	update_option( 'pp_popup_related_products', $new_options, false );

	$crosssell_rules = [];
	foreach ( $categories as $cat_slug => $cat_label ) {
		$crosssell_rules[ $cat_slug ] = [];
		if ( ! empty( $_POST[ 'pp_crosssell_' . $cat_slug ] ) && is_array( $_POST[ 'pp_crosssell_' . $cat_slug ] ) ) {
			$crosssell_rules[ $cat_slug ] = array_values( array_filter(
				array_map( 'sanitize_title', $_POST[ 'pp_crosssell_' . $cat_slug ] )
			) );
		}
	}
	update_option( 'pp_popup_crosssell_rules', $crosssell_rules, false );

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ajustes del popup guardados.', 'hello-elementor-child' ) . '</p></div>';
	} );
}

function pp_get_managed_categories() {
	return [
		'padelschlaeger' => __( 'Palas de pádel', 'hello-elementor-child' ),
		'padel-set'      => __( 'Padel Sets', 'hello-elementor-child' ),
		'padelballe'     => __( 'Pelotas de pádel', 'hello-elementor-child' ),
		'padelschuhe'    => __( 'Zapatillas de pádel', 'hello-elementor-child' ),
		'padeltaschen'   => __( 'Bolsas de pádel', 'hello-elementor-child' ),
		'padel-zubehoer' => __( 'Accesorios de pádel', 'hello-elementor-child' ),
	];
}

add_action( 'admin_enqueue_scripts', 'pp_admin_enqueue_scripts' );
function pp_admin_enqueue_scripts( $hook ) {
	if ( 'woocommerce_page_pp-cart-popup-settings' !== $hook ) return;
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_script( 'selectWoo' );
	wp_enqueue_style( 'select2' );
}

function pp_render_popup_admin_page() {
	$categories   = pp_get_managed_categories();
	$saved        = get_option( 'pp_popup_related_products', [] );
	$search_nonce = wp_create_nonce( 'search-products' );
	?>
	<div class="wrap pp-popup-admin">
		<h1 style="margin-bottom:6px;"><?php esc_html_e( 'Gestión del Popup de Carrito', 'hello-elementor-child' ); ?></h1>
		<p class="description" style="margin-bottom:20px;">
			<?php esc_html_e( 'Selecciona los productos que aparecerán como "También puede interesarte" en el popup cuando se añade al carrito un producto de cada categoría. Máximo 4 por categoría.', 'hello-elementor-child' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'pp_popup_settings', 'pp_popup_nonce' ); ?>

			<table class="wp-list-table widefat fixed striped" style="max-width:960px;">
				<thead>
					<tr>
						<th style="width:220px;"><?php esc_html_e( 'Categoría del producto', 'hello-elementor-child' ); ?></th>
						<th><?php esc_html_e( 'Productos a mostrar en el popup (máx. 4)', 'hello-elementor-child' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $categories as $slug => $label ) :
					$selected_ids = $saved[ $slug ] ?? [];
					$field_name   = 'pp_related_' . $slug;
				?>
				<tr>
					<td style="vertical-align:top;padding-top:16px;">
						<strong><?php echo esc_html( $label ); ?></strong><br>
						<code style="font-size:11px;color:#999;"><?php echo esc_html( $slug ); ?></code>
					</td>
					<td style="padding:12px 10px;">
						<select
							id="pp_select_<?php echo esc_attr( $slug ); ?>"
							class="pp-product-search"
							multiple
							name="<?php echo esc_attr( $field_name ); ?>[]"
							style="width:100%;max-width:640px;"
							data-nonce="<?php echo esc_attr( $search_nonce ); ?>">
							<?php foreach ( $selected_ids as $pid ) :
								$p = wc_get_product( $pid );
								if ( ! $p ) continue;
							?>
							<option value="<?php echo esc_attr( $pid ); ?>" selected="selected">
								<?php echo esc_html( $p->get_name() . ' (#' . $pid . ')' ); ?>
							</option>
							<?php endforeach; ?>
						</select>
						<p class="description" style="margin-top:5px;font-size:12px;">
							<?php printf(
								esc_html__( 'Se mostrarán cuando se añada al carrito un producto de "%s"', 'hello-elementor-child' ),
								esc_html( $label )
							); ?>
						</p>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2 style="margin-top:40px;padding-top:20px;border-top:1px solid #ddd;">
				<?php esc_html_e( 'Reglas de Cross-Sell por Categoría', 'hello-elementor-child' ); ?>
			</h2>
			<p class="description" style="margin-bottom:16px;">
				<?php esc_html_e( 'Cuando se añada un producto de la categoría indicada en la fila, el sistema mostrará prioritariamente los más vendidos de las categorías marcadas (complementos). Si no hay reglas configuradas para esa categoría, se usarán los productos relacionados manuales de la tabla superior.', 'hello-elementor-child' ); ?>
				<br><strong><?php esc_html_e( 'Ejemplo: Pala añadida → mostrar pelotas y bolsas como cross-sell.', 'hello-elementor-child' ); ?></strong>
			</p>

			<table class="wp-list-table widefat fixed striped" style="max-width:960px;">
				<thead>
					<tr>
						<th style="width:180px;"><?php esc_html_e( 'Si añade un producto de…', 'hello-elementor-child' ); ?></th>
						<th><?php esc_html_e( 'Mostrar cross-sells de estas categorías', 'hello-elementor-child' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$crosssell_saved = get_option( 'pp_popup_crosssell_rules', [] );
				foreach ( $categories as $slug => $label ) :
					$selected_cats = $crosssell_saved[ $slug ] ?? [];
				?>
				<tr>
					<td style="vertical-align:middle;">
						<strong><?php echo esc_html( $label ); ?></strong>
					</td>
					<td style="padding:10px;">
						<div style="display:flex;flex-wrap:wrap;gap:8px 20px;">
						<?php foreach ( $categories as $target_slug => $target_label ) :
							if ( $target_slug === $slug ) continue;
							$checked = in_array( $target_slug, $selected_cats, true ) ? 'checked' : '';
						?>
							<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;">
								<input type="checkbox"
								       name="pp_crosssell_<?php echo esc_attr( $slug ); ?>[]"
								       value="<?php echo esc_attr( $target_slug ); ?>"
								       <?php echo $checked; ?>
								       style="width:16px;height:16px;accent-color:#fe6100;" />
								<?php echo esc_html( $target_label ); ?>
							</label>
						<?php endforeach; ?>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top:20px;">
				<input type="submit" name="pp_popup_save" class="button button-primary button-large"
				       value="<?php esc_attr_e( 'Guardar ajustes', 'hello-elementor-child' ); ?>" />
			</p>
		</form>
	</div>

	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		var ajaxUrl  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
		var maxItems = 4;

		$( '.pp-product-search' ).each( function() {
			var $select = $( this );
			var nonce   = $select.data( 'nonce' );

			$select.selectWoo( {
				ajax: {
					url:      ajaxUrl,
					dataType: 'json',
					delay:    300,
					data: function( params ) {
						return {
							term:     params.term,
							action:   'woocommerce_json_search_products_and_variations',
							security: nonce,
							exclude:  '',
							limit:    20,
						};
					},
					processResults: function( data ) {
						var results = [];
						if ( data && typeof data === 'object' ) {
							$.each( data, function( id, text ) {
								results.push( { id: id, text: text } );
							} );
						}
						return { results: results };
					},
					cache: true,
				},
				minimumInputLength: 2,
				placeholder:        '<?php echo esc_js( __( 'Escribe el nombre del producto...', 'hello-elementor-child' ) ); ?>',
				allowClear:         true,
				multiple:           true,
				maximumSelectionLength: maxItems,
				language: {
					inputTooShort:   function() { return 'Escribe al menos 2 caracteres'; },
					maximumSelected: function() { return 'Máximo ' + maxItems + ' productos por categoría'; },
					noResults:       function() { return 'No se encontraron productos'; },
					searching:       function() { return 'Buscando...'; },
				},
			} );
		} );
	} );
	</script>
	<?php
}

// ============================================================================
// 4. SHORTCODE [fk_cart_menu]
// ============================================================================

add_shortcode( 'fk_cart_menu', 'pp_cart_menu_shortcode' );
function pp_cart_menu_shortcode( $atts ) {
	if ( ! function_exists( 'WC' ) ) return '';

	$count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$checkout = wc_get_checkout_url();

	ob_start();
	?>
	<a href="<?php echo esc_url( $checkout ); ?>"
	   class="pp-cart-menu"
	   aria-label="<?php esc_attr_e( 'Carrito de compra', 'hello-elementor-child' ); ?>">
		<span class="pp-cart-menu__icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
			     stroke="currentColor" stroke-width="2" stroke-linecap="round"
			     stroke-linejoin="round" width="26" height="26" aria-hidden="true">
				<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
		</span>
		<?php if ( $count > 0 ) : ?>
			<span class="pp-cart-menu__count"
			      aria-label="<?php echo esc_attr( $count ); ?> <?php esc_attr_e( 'productos', 'hello-elementor-child' ); ?>">
				<?php echo esc_html( $count ); ?>
			</span>
		<?php endif; ?>
	</a>
	<?php
	return ob_get_clean();
}

// Actualizar contador del carrito via fragments WooCommerce
add_filter( 'woocommerce_add_to_cart_fragments', 'pp_cart_menu_fragment' );
function pp_cart_menu_fragment( $fragments ) {
	ob_start();
	$count    = WC()->cart->get_cart_contents_count();
	$checkout = wc_get_checkout_url();
	?>
	<a href="<?php echo esc_url( $checkout ); ?>" class="pp-cart-menu">
		<span class="pp-cart-menu__icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
			     stroke="currentColor" stroke-width="2" stroke-linecap="round"
			     stroke-linejoin="round" width="26" height="26" aria-hidden="true">
				<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
		</span>
		<?php if ( $count > 0 ) : ?>
			<span class="pp-cart-menu__count"><?php echo esc_html( $count ); ?></span>
		<?php endif; ?>
	</a>
	<?php
	$fragments['a.pp-cart-menu'] = ob_get_clean();
	return $fragments;
}

// ============================================================================
// 5. CHECKOUT VACÍO — Permitir acceso al checkout con carrito vacío
// ============================================================================

add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false' );

// ============================================================================
// 6. PREVENIR REDIRECCIÓN AL CARRITO tras añadir producto (usamos popup)
// ============================================================================

add_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );

// ============================================================================
// 7. FIX — Permitir acceso directo al checkout desde el icono del menú
// ============================================================================

add_action( 'template_redirect', 'pp_force_cart_check_before_checkout', 5 );
function pp_force_cart_check_before_checkout() {
	if ( ! is_checkout() || is_order_received_page() ) return;
	if ( ! WC()->cart || WC()->cart->is_empty() ) return;

	WC()->cart->calculate_totals();

	$notices = wc_get_notices( 'error' );
	if ( ! empty( $notices ) ) {
		$cart_error_strings = [
			'Probleme mit den Positionen',
			'problems with items',
			'items in your cart',
			'Warenkorb',
		];
		$filtered = [];
		foreach ( $notices as $notice ) {
			$notice_text      = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : $notice;
			$is_cart_redirect = false;
			foreach ( $cart_error_strings as $str ) {
				if ( stripos( $notice_text, $str ) !== false ) {
					$is_cart_redirect = true;
					break;
				}
			}
			if ( ! $is_cart_redirect ) {
				$filtered[] = $notice;
			}
		}
		WC()->session->set( 'wc_notices', array_merge(
			wc_get_notices(),
			[ 'error' => $filtered ]
		) );
	}
}

// ============================================================================
// 8. FRAGMENTS — Forzar actualización del shortcode [fk_cart_menu]
// ============================================================================

add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {
	ob_start();
	$count    = WC()->cart->get_cart_contents_count();
	$checkout = wc_get_checkout_url();
	?>
	<a href="<?php echo esc_url( $checkout ); ?>" class="pp-cart-menu">
		<span class="pp-cart-menu__icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
			     stroke="currentColor" stroke-width="2" stroke-linecap="round"
			     stroke-linejoin="round" width="26" height="26" aria-hidden="true">
				<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
		</span>
		<?php if ( $count > 0 ) : ?>
			<span class="pp-cart-menu__count"><?php echo esc_html( $count ); ?></span>
		<?php endif; ?>
	</a>
	<?php
	$fragments['a.pp-cart-menu'] = ob_get_clean();
	return $fragments;
}, 20 );