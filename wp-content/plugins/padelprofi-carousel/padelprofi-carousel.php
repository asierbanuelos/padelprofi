<?php
/**
 * Plugin Name: PadelProfi Carousel
 * Description: Ligero sistema de carruseles de productos. Sustituto de carousel-slider.
 * Version:     1.2.6
 * Text Domain: pp-carousel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'PP_CAROUSEL_VER' ) ) define( 'PP_CAROUSEL_VER', '1.2.6' );
if ( ! defined( 'PP_CAROUSEL_URL' ) ) define( 'PP_CAROUSEL_URL', plugin_dir_url( __FILE__ ) );

// ── Activación: SOLO flush rewrite rules + guardar datos legacy en option ──
register_activation_hook( __FILE__, function() {
	pp_carousel_register_cpt();
	flush_rewrite_rules();
	// Datos de los carruseles migrados — sin wp_insert_post, sin tocar hooks de terceros
	update_option( 'pp_carousel_legacy_map', [
		14249 => [ 'title' => 'Carousel Startseite Top',  'products' => [ 37496, 35459, 26054, 37658 ] ],
		13463 => [ 'title' => 'Carousel Empfehlungen',    'products' => [ 26059, 26058, 26054, 26050, 26049, 26048, 26047, 26063, 26060 ] ],
	], false );
} );

// ── CPT ────────────────────────────────────────────────────────────────────
add_action( 'init', 'pp_carousel_register_cpt' );
function pp_carousel_register_cpt() {
	if ( post_type_exists( 'pp_carousel' ) ) return;
	register_post_type( 'pp_carousel', [
		'labels' => [
			'name'          => 'Karussells',
			'singular_name' => 'Karussell',
			'add_new_item'  => 'Neues Karussell',
			'edit_item'     => 'Karussell bearbeiten',
			'all_items'     => 'Alle Karussells',
		],
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-images-alt2',
		'menu_position' => 58,
		'supports'      => [ 'title' ],
		'show_in_rest'  => false,
	] );
}

// ── Migración lazy: se crea solo cuando el admin visita la página Karussells ─
add_action( 'load-edit.php', function() {
	if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'pp_carousel' ) return;
	$map = get_option( 'pp_carousel_legacy_map', [] );
	if ( empty( $map ) ) return;
	if ( ! function_exists( 'wc_get_product' ) ) return;

	foreach ( $map as $legacy_id => $data ) {
		$found = get_posts( [
			'post_type'      => 'pp_carousel',
			'meta_key'       => '_pp_carousel_legacy_id',
			'meta_value'     => $legacy_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		if ( ! empty( $found ) ) continue;

		$pid = wp_insert_post( [
			'post_type'   => 'pp_carousel',
			'post_title'  => $data['title'],
			'post_status' => 'publish',
		], true );

		if ( $pid && ! is_wp_error( $pid ) ) {
			update_post_meta( $pid, '_pp_carousel_products',  $data['products'] );
			update_post_meta( $pid, '_pp_carousel_legacy_id', $legacy_id );
		}
	}
	delete_option( 'pp_carousel_legacy_map' );
} );

// ── Columnas admin ─────────────────────────────────────────────────────────
add_filter( 'manage_pp_carousel_posts_columns', function( $cols ) {
	$cols['pp_shortcode'] = 'Shortcode';
	$cols['pp_count']     = 'Produkte';
	return $cols;
} );
add_action( 'manage_pp_carousel_posts_custom_column', function( $col, $post_id ) {
	if ( 'pp_shortcode' === $col ) {
		$leg = get_post_meta( $post_id, '_pp_carousel_legacy_id', true );
		if ( $leg ) {
			echo '<code>[carousel_slide id="' . esc_html( $leg ) . '"]</code>';
		} else {
			echo '<code>[carousel_slide id="' . esc_html( $post_id ) . '"]</code>';
		}
	}
	if ( 'pp_count' === $col ) {
		$ids = (array) ( get_post_meta( $post_id, '_pp_carousel_products', true ) ?: [] );
		echo count( $ids ) . ' Produkte';
	}
}, 10, 2 );

// ── Metabox ────────────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function() {
	add_meta_box( 'pp_carousel_products', 'Produkte im Karussell', 'pp_carousel_render_metabox', 'pp_carousel', 'normal', 'high' );
} );

function pp_carousel_render_metabox( $post ) {
	wp_nonce_field( 'pp_carousel_save', 'pp_carousel_nonce' );
	$product_ids = (array) ( get_post_meta( $post->ID, '_pp_carousel_products', true ) ?: [] );
	$legacy_id   = get_post_meta( $post->ID, '_pp_carousel_legacy_id', true );

	$products = [];
	if ( function_exists( 'wc_get_product' ) ) {
		foreach ( $product_ids as $pid ) {
			$p = wc_get_product( intval( $pid ) );
			if ( $p ) $products[] = [ 'id' => $p->get_id(), 'name' => $p->get_name() ];
		}
	}
	?>
	<style>
	#pp-selected-products .pp-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #c3c4c7;border-radius:20px;padding:5px 12px;font-size:12px;cursor:move;user-select:none;}
	#pp-selected-products .pp-chip:hover{border-color:#FE6100;}
	#pp-selected-products .pp-chip .pp-remove{cursor:pointer;color:#aaa;font-size:18px;line-height:1;font-weight:300;}
	#pp-selected-products .pp-chip .pp-remove:hover{color:#c00;}
	#pp-search-results .pp-result{padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;color:#333;}
	#pp-search-results .pp-result:hover{background:#f0f7ff;}
	</style>
	<div style="padding:10px 0;">
		<input type="hidden" name="pp_carousel_product_ids" id="pp_carousel_product_ids"
			   value="<?php echo esc_attr( implode( ',', array_column( $products, 'id' ) ) ); ?>" />
		<div style="margin-bottom:16px;">
			<label style="font-weight:600;display:block;margin-bottom:6px;">Produkt suchen:</label>
			<input type="text" id="pp-product-search" autocomplete="off" placeholder="Name oder ID…"
				   style="width:100%;max-width:480px;padding:8px 12px;border:1px solid #c3c4c7;border-radius:4px 4px 0 0;font-size:13px;" />
			<div id="pp-search-results"
				 style="border:1px solid #c3c4c7;border-top:none;border-radius:0 0 4px 4px;max-width:480px;max-height:220px;overflow-y:auto;display:none;background:#fff;z-index:100;box-shadow:0 4px 8px rgba(0,0,0,.1);position:relative;">
			</div>
		</div>
		<label style="font-weight:600;display:block;margin-bottom:8px;">Produkte <span style="color:#999;font-weight:400;">(Drag & Drop zum Sortieren)</span>:</label>
		<div id="pp-selected-products"
			 style="display:flex;flex-wrap:wrap;gap:8px;min-height:48px;padding:10px;background:#f9f9f9;border:1px dashed #c3c4c7;border-radius:4px;">
			<?php foreach ( $products as $p ) : ?>
			<div class="pp-chip" data-id="<?php echo esc_attr( $p['id'] ); ?>">
				<span class="dashicons dashicons-menu" style="font-size:14px;color:#bbb;"></span>
				<span><?php echo esc_html( $p['name'] ); ?> <span style="color:#aaa;">#<?php echo esc_html( $p['id'] ); ?></span></span>
				<span class="pp-remove" data-id="<?php echo esc_attr( $p['id'] ); ?>" title="Entfernen">×</span>
			</div>
			<?php endforeach; ?>
		</div>
		<div style="margin-top:14px;padding:10px 14px;background:#f0f7ff;border:1px solid #c5d9ed;border-radius:4px;font-size:12px;line-height:1.8;">
			<strong>Shortcodes:</strong>
			<?php if ( $legacy_id ) : ?>
			<code>[carousel_slide id="<?php echo esc_html( $legacy_id ); ?>"]</code> ·
			<code>[custom_carousel_slider id="<?php echo esc_html( $legacy_id ); ?>"]</code>
			<br><span style="color:#888;">Neuer Shortcode: <code>[carousel_slide id="<?php echo esc_html( $post->ID ); ?>"]</code></span>
			<?php else : ?>
			<code>[carousel_slide id="<?php echo esc_html( $post->ID ); ?>"]</code> ·
			<code>[custom_carousel_slider id="<?php echo esc_html( $post->ID ); ?>"]</code>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

// ── Guardar metabox ────────────────────────────────────────────────────────
add_action( 'save_post_pp_carousel', function( $post_id ) {
	if ( ! isset( $_POST['pp_carousel_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['pp_carousel_nonce'], 'pp_carousel_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$raw = isset( $_POST['pp_carousel_product_ids'] ) ? sanitize_text_field( $_POST['pp_carousel_product_ids'] ) : '';
	$ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );
	update_post_meta( $post_id, '_pp_carousel_products', $ids );
} );

// ── AJAX buscar productos ──────────────────────────────────────────────────
add_action( 'wp_ajax_pp_search_products', function() {
	check_ajax_referer( 'pp_carousel_admin', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
	if ( ! function_exists( 'wc_get_product' ) ) wp_send_json_error( 'WooCommerce not active' );

	$term    = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	$results = [];

	if ( is_numeric( $term ) ) {
		$p = wc_get_product( intval( $term ) );
		if ( $p ) $results[] = [ 'id' => $p->get_id(), 'name' => $p->get_name() ];
	} else {
		$q = new WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			's'              => $term,
		] );
		foreach ( $q->posts as $post ) {
			$p = wc_get_product( $post->ID );
			if ( $p ) $results[] = [ 'id' => $p->get_id(), 'name' => $p->get_name() ];
		}
	}
	wp_send_json_success( $results );
} );

// ── Scripts admin ──────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', function( $hook ) {
	global $post;
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
	if ( ! isset( $post ) || 'pp_carousel' !== $post->post_type ) return;

	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'pp-carousel-admin', PP_CAROUSEL_URL . 'assets/admin.js', [ 'jquery', 'jquery-ui-sortable' ], PP_CAROUSEL_VER, true );
	wp_localize_script( 'pp-carousel-admin', 'ppCarouselAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'pp_carousel_admin' ),
	] );
} );

// ── Shortcode ──────────────────────────────────────────────────────────────
add_shortcode( 'carousel_slide',         'pp_carousel_render' );
add_shortcode( 'custom_carousel_slider', 'pp_carousel_render' );
add_shortcode( 'pp_carousel',            'pp_carousel_render' );

function pp_carousel_render( $atts ) {
	if ( ! function_exists( 'wc_get_product' ) ) return '';

	$atts   = shortcode_atts( [ 'id' => 0 ], $atts );
	$req_id = intval( $atts['id'] );
	if ( ! $req_id ) return '';

	// 1. ¿Es un post pp_carousel directo?
	$product_ids = null;
	$carousel_post = get_post( $req_id );
	if ( $carousel_post && 'pp_carousel' === $carousel_post->post_type && 'publish' === $carousel_post->post_status ) {
		$product_ids = (array) ( get_post_meta( $req_id, '_pp_carousel_products', true ) ?: [] );
	}

	// 2. ¿Existe un pp_carousel con este legacy ID?
	if ( is_null( $product_ids ) ) {
		$found = get_posts( [
			'post_type'      => 'pp_carousel',
			'post_status'    => 'publish',
			'meta_key'       => '_pp_carousel_legacy_id',
			'meta_value'     => $req_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		if ( ! empty( $found ) ) {
			$product_ids = (array) ( get_post_meta( $found[0], '_pp_carousel_products', true ) ?: [] );
		}
	}

	// 3. Fallback al option map guardado en activación
	if ( is_null( $product_ids ) ) {
		$map = get_option( 'pp_carousel_legacy_map', [] );
		if ( isset( $map[ $req_id ] ) ) {
			$product_ids = $map[ $req_id ]['products'];
		}
	}

	if ( empty( $product_ids ) ) return '';

	wp_enqueue_style( 'pp-carousel', PP_CAROUSEL_URL . 'assets/carousel.css', [ 'swiper-css' ], PP_CAROUSEL_VER );

	static $instance = 0;
	$instance++;
	$uid = 'pp-swiper-' . $req_id . '-' . $instance;

	if ( ! isset( $GLOBALS['_pp_carousel_inits'] ) ) {
		$GLOBALS['_pp_carousel_inits'] = [];
		add_action( 'wp_footer', 'pp_carousel_footer_init', 50 );
	}
	$GLOBALS['_pp_carousel_inits'][] = $uid;

	// El global $product lo necesitan los hooks del tema (custom_add_shipping_text_loop, etc.)
	global $product;
	$_product_backup = isset( $product ) ? $product : null;

	ob_start();
	echo '<div class="pp-carousel-wrapper"><div class="swiper pp-swiper" id="' . esc_attr( $uid ) . '"><div class="swiper-wrapper">';

	foreach ( $product_ids as $pid ) {
		$wcp = wc_get_product( intval( $pid ) );
		if ( ! $wcp ) continue;

		// Establecer global $product para todos los hooks y filtros de WC del tema
		$product = $wcp;

		$price   = (float) $wcp->get_price();
		$regular = (float) $wcp->get_regular_price();
		$on_sale = $wcp->is_on_sale() && $regular > 0 && $price < $regular;
		$discount = $on_sale ? round( ( 1 - $price / $regular ) * 100 ) : 0;

		echo '<div class="swiper-slide"><div class="product-card2">';

		// Badge de descuento
		if ( $on_sale && $discount > 0 ) {
			echo '<div class="discount-label">-' . $discount . '%</div>';
		}

		// Imagen con hover — llamadas directas para evitar que WCBoost Compare (y cualquier otro
		// plugin) inyecte HTML en woocommerce_before_shop_loop_item_title a prioridad > 10
		echo '<a href="' . esc_url( $wcp->get_permalink() ) . '" class="woocommerce-LoopProduct-link pp-card-link">';
		if ( function_exists( 'mi_thumbnail_con_overlay' ) ) {
			mi_thumbnail_con_overlay(); // imagen principal + imagen hover de la galería
		} else {
			echo woocommerce_get_product_thumbnail();
		}
		echo '</a>';

		// Badge "Ausverkauft" si el producto está sin stock
		if ( function_exists( 'etiqueta_agotado_automatica' ) ) {
			etiqueta_agotado_automatica();
		}

		// Título como <p> en lugar de heading para no contaminar la jerarquía H1/H2/H3 de la página
		echo '<p class="woocommerce-loop-product__title"><a href="' . esc_url( $wcp->get_permalink() ) . '">' . esc_html( $wcp->get_name() ) . '</a></p>';

		// Estrellas — llamada directa (evita woocommerce_template_loop_price y WCBoost Compare
		// que también se engancharían en do_action('woocommerce_after_shop_loop_item_title'))
		if ( function_exists( 'woocommerce_template_loop_rating' ) ) {
			woocommerce_template_loop_rating();
		}

		// Precio: nuevo primero, tachado después — igual que el carrusel original
		echo '<div class="price-container">';
		if ( $on_sale ) {
			echo '<span class="new-price">' . wc_price( $price ) . '</span>';
			echo '<span class="old-price">' . wc_price( $regular ) . '</span>';
		} else {
			echo '<span class="new-price">' . $wcp->get_price_html() . '</span>';
		}
		echo '</div>';

		// Texto de envío + botón — llamada directa para evitar otros hooks de woocommerce_after_shop_loop_item
		if ( function_exists( 'custom_add_shipping_text_loop' ) ) {
			custom_add_shipping_text_loop();
		}

		echo '</div></div>';
	}

	$product = $_product_backup; // restaurar global tras el bucle

	echo '</div>';
	echo '<div class="pp-swiper-prev swiper-button-prev"></div>';
	echo '<div class="pp-swiper-next swiper-button-next"></div>';
	echo '<div class="pp-swiper-pagination swiper-pagination"></div>';
	echo '</div></div>';

	return ob_get_clean();
}

// ── Init Swiper en footer ──────────────────────────────────────────────────
function pp_carousel_footer_init() {
	$ids = isset( $GLOBALS['_pp_carousel_inits'] ) ? $GLOBALS['_pp_carousel_inits'] : [];
	if ( empty( $ids ) ) return;

	echo '<script>(function(){';
	echo 'var bp={480:{slidesPerView:2,spaceBetween:12},768:{slidesPerView:3,spaceBetween:16},1024:{slidesPerView:4,spaceBetween:20}};';
	foreach ( $ids as $uid ) {
		$esc = esc_js( $uid );
		echo 'new Swiper(\'#' . $esc . '\',{slidesPerView:1.3,spaceBetween:12,grabCursor:true,breakpoints:bp,';
		echo 'pagination:{el:\'#' . $esc . ' .pp-swiper-pagination\',clickable:true},';
		echo 'navigation:{nextEl:\'#' . $esc . ' .pp-swiper-next\',prevEl:\'#' . $esc . ' .pp-swiper-prev\'}});';
	}
	echo '}());</script>';
}
