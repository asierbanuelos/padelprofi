<?php
/**
 * Plugin Name: PadelProfi Carousel
 * Description: Ligero sistema de carruseles de productos. Sustituto de carousel-slider.
 * Version:     1.0.0
 * Text Domain: pp-carousel
 */

defined( 'ABSPATH' ) || exit;

define( 'PP_CAROUSEL_VER', '1.0.0' );
define( 'PP_CAROUSEL_URL', plugin_dir_url( __FILE__ ) );

// ── Activación ─────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'pp_carousel_activate' );
function pp_carousel_activate() {
	pp_carousel_register_cpt();
	flush_rewrite_rules();
	// Marcar que hay que crear los carruseles iniciales (se hace en admin_init, no aquí)
	update_option( 'pp_carousel_needs_migration', '1' );
}

// ── Migración diferida: se ejecuta en el primer admin_init tras la activación ──
add_action( 'admin_init', 'pp_carousel_maybe_migrate' );
function pp_carousel_maybe_migrate() {
	if ( ! get_option( 'pp_carousel_needs_migration' ) ) return;
	delete_option( 'pp_carousel_needs_migration' );

	if ( ! function_exists( 'wc_get_product' ) ) return; // WooCommerce no está activo

	$migrate = [
		14249 => [
			'title'    => 'Carousel Startseite Top',
			'products' => [ 37496, 35459, 26054, 37658 ],
		],
		13463 => [
			'title'    => 'Carousel Empfehlungen',
			'products' => [ 26059, 26058, 26054, 26050, 26049, 26048, 26047, 26063, 26060 ],
		],
	];

	foreach ( $migrate as $legacy_id => $data ) {
		$existing = get_posts( [
			'post_type'      => 'pp_carousel',
			'meta_key'       => '_pp_carousel_legacy_id',
			'meta_value'     => $legacy_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		if ( ! empty( $existing ) ) continue;

		$post_id = wp_insert_post( [
			'post_type'   => 'pp_carousel',
			'post_title'  => $data['title'],
			'post_status' => 'publish',
		], true );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_pp_carousel_products',  $data['products'] );
			update_post_meta( $post_id, '_pp_carousel_legacy_id', $legacy_id );
		}
	}
}

// ── CPT ────────────────────────────────────────────────────────────────────
add_action( 'init', 'pp_carousel_register_cpt' );
function pp_carousel_register_cpt() {
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

// ── Columnas en el listado admin ───────────────────────────────────────────
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
			echo '<br><small style="color:#888">Neuer ID: <code>[carousel_slide id="' . esc_html( $post_id ) . '"]</code></small>';
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
	add_meta_box(
		'pp_carousel_products',
		'Produkte im Karussell',
		'pp_carousel_render_metabox',
		'pp_carousel',
		'normal',
		'high'
	);
} );

function pp_carousel_render_metabox( $post ) {
	wp_nonce_field( 'pp_carousel_save', 'pp_carousel_nonce' );
	$product_ids = (array) ( get_post_meta( $post->ID, '_pp_carousel_products', true ) ?: [] );
	$legacy_id   = get_post_meta( $post->ID, '_pp_carousel_legacy_id', true );

	$products = [];
	foreach ( $product_ids as $pid ) {
		$p = wc_get_product( intval( $pid ) );
		if ( $p ) {
			$products[] = [ 'id' => $p->get_id(), 'name' => $p->get_name() ];
		}
	}
	?>
	<style>
	#pp-selected-products .pp-chip { display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #c3c4c7;border-radius:20px;padding:5px 12px;font-size:12px;cursor:move;user-select:none; }
	#pp-selected-products .pp-chip:hover { border-color:#FE6100; }
	#pp-selected-products .pp-chip .pp-remove { cursor:pointer;color:#aaa;font-size:18px;line-height:1;font-weight:300; }
	#pp-selected-products .pp-chip .pp-remove:hover { color:#cc0000; }
	#pp-search-results .pp-result { padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;color:#333; }
	#pp-search-results .pp-result:hover { background:#f0f7ff; }
	#pp-search-results .pp-result:last-child { border-bottom:none; }
	</style>

	<div id="pp-carousel-admin" style="padding:10px 0;">
		<input type="hidden" name="pp_carousel_product_ids" id="pp_carousel_product_ids"
			   value="<?php echo esc_attr( implode( ',', array_column( $products, 'id' ) ) ); ?>" />

		<div style="margin-bottom:16px;">
			<label style="font-weight:600;display:block;margin-bottom:6px;">Produkt suchen und hinzufügen:</label>
			<input type="text" id="pp-product-search" autocomplete="off"
				   placeholder="Produktname oder ID eingeben…"
				   style="width:100%;max-width:480px;padding:8px 12px;border:1px solid #c3c4c7;border-radius:4px 4px 0 0;font-size:13px;outline:none;" />
			<div id="pp-search-results"
				 style="border:1px solid #c3c4c7;border-top:none;border-radius:0 0 4px 4px;max-width:480px;max-height:220px;overflow-y:auto;display:none;background:#fff;position:relative;z-index:100;box-shadow:0 4px 8px rgba(0,0,0,.1);">
			</div>
		</div>

		<label style="font-weight:600;display:block;margin-bottom:8px;">
			Produkte im Karussell <span style="color:#999;font-weight:400;">(ziehen zum Sortieren)</span>:
		</label>
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

		<div style="margin-top:16px;padding:10px 14px;background:#f0f7ff;border:1px solid #c5d9ed;border-radius:4px;font-size:12px;line-height:1.8;">
			<strong>Shortcodes:</strong><br>
			<?php if ( $legacy_id ) : ?>
			<code>[carousel_slide id="<?php echo esc_html( $legacy_id ); ?>"]</code>
			&nbsp;·&nbsp;
			<code>[custom_carousel_slider id="<?php echo esc_html( $legacy_id ); ?>"]</code>
			<br>
			<span style="color:#888;">Neuer ID: <code>[carousel_slide id="<?php echo esc_html( $post->ID ); ?>"]</code></span>
			<?php else : ?>
			<code>[carousel_slide id="<?php echo esc_html( $post->ID ); ?>"]</code>
			&nbsp;·&nbsp;
			<code>[custom_carousel_slider id="<?php echo esc_html( $post->ID ); ?>"]</code>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

// ── Guardar metabox ────────────────────────────────────────────────────────
add_action( 'save_post_pp_carousel', function( $post_id ) {
	if ( ! isset( $_POST['pp_carousel_nonce'] )
		|| ! wp_verify_nonce( $_POST['pp_carousel_nonce'], 'pp_carousel_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$raw = sanitize_text_field( $_POST['pp_carousel_product_ids'] ?? '' );
	$ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );
	update_post_meta( $post_id, '_pp_carousel_products', $ids );
} );

// ── AJAX: buscar productos ─────────────────────────────────────────────────
add_action( 'wp_ajax_pp_search_products', function() {
	check_ajax_referer( 'pp_carousel_admin', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );

	$term    = sanitize_text_field( $_GET['term'] ?? '' );
	$results = [];

	if ( is_numeric( $term ) ) {
		$p = wc_get_product( intval( $term ) );
		if ( $p ) $results[] = [ 'id' => $p->get_id(), 'name' => $p->get_name() ];
	} else {
		$query = new WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			's'              => $term,
		] );
		foreach ( $query->posts as $post ) {
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
	wp_enqueue_script(
		'pp-carousel-admin',
		PP_CAROUSEL_URL . 'assets/admin.js',
		[ 'jquery', 'jquery-ui-sortable' ],
		PP_CAROUSEL_VER,
		true
	);
	wp_localize_script( 'pp-carousel-admin', 'ppCarouselAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'pp_carousel_admin' ),
	] );
} );

// ── Shortcode renderer ─────────────────────────────────────────────────────
function pp_carousel_render( $atts ) {
	$atts   = shortcode_atts( [ 'id' => 0 ], $atts );
	$req_id = intval( $atts['id'] );
	if ( ! $req_id ) return '';

	// Buscar post pp_carousel: por ID directo o por legacy mapping
	$post = get_post( $req_id );
	if ( $post && 'pp_carousel' === $post->post_type && 'publish' === $post->post_status ) {
		$carousel_id = $post->ID;
	} else {
		$found = get_posts( [
			'post_type'      => 'pp_carousel',
			'post_status'    => 'publish',
			'meta_key'       => '_pp_carousel_legacy_id',
			'meta_value'     => $req_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		if ( empty( $found ) ) return '';
		$carousel_id = $found[0];
	}

	$product_ids = (array) ( get_post_meta( $carousel_id, '_pp_carousel_products', true ) ?: [] );
	if ( empty( $product_ids ) ) return '';

	wp_enqueue_style( 'pp-carousel', PP_CAROUSEL_URL . 'assets/carousel.css', [ 'swiper-css' ], PP_CAROUSEL_VER );

	static $instance = 0;
	$instance++;
	$uid = 'pp-swiper-' . $carousel_id . '-' . $instance;

	// Registrar init de footer en la primera llamada
	if ( ! isset( $GLOBALS['_pp_carousel_inits'] ) ) {
		$GLOBALS['_pp_carousel_inits'] = [];
		add_action( 'wp_footer', 'pp_carousel_footer_init', 50 );
	}
	$GLOBALS['_pp_carousel_inits'][] = $uid;

	ob_start();
	?>
	<div class="pp-carousel-wrapper">
		<div class="swiper pp-swiper" id="<?php echo esc_attr( $uid ); ?>">
			<div class="swiper-wrapper">
				<?php foreach ( $product_ids as $pid ) :
					$wcp = wc_get_product( intval( $pid ) );
					if ( ! $wcp || ! $wcp->is_visible() ) continue;

					$img_url  = wp_get_attachment_image_url( $wcp->get_image_id(), 'woocommerce_thumbnail' )
					            ?: wc_placeholder_img_src();
					$price    = (float) $wcp->get_price();
					$regular  = (float) $wcp->get_regular_price();
					$on_sale  = $wcp->is_on_sale() && $regular > 0 && $price < $regular;
					$discount = $on_sale ? round( ( 1 - $price / $regular ) * 100 ) : 0;

					// Add-to-cart link
					$is_simple   = $wcp->is_purchasable() && $wcp->is_in_stock() && 'simple' === $wcp->get_type();
					$btn_url     = esc_url( $wcp->add_to_cart_url() );
					$btn_class   = 'button add_to_cart_button product_type_' . esc_attr( $wcp->get_type() )
					               . ( $is_simple ? ' ajax_add_to_cart' : '' );
					$btn_extra   = $is_simple
					               ? sprintf(
					                   ' data-product_id="%d" data-product_sku="%s" data-quantity="1" rel="nofollow"',
					                   $wcp->get_id(),
					                   esc_attr( $wcp->get_sku() )
					               )
					               : '';
					$btn_text    = esc_html( $wcp->add_to_cart_text() );
				?>
				<div class="swiper-slide">
					<div class="product-card2">
						<?php if ( $on_sale && $discount > 0 ) : ?>
						<div class="discount-label">-<?php echo $discount; ?>%</div>
						<?php endif; ?>

						<a href="<?php echo esc_url( $wcp->get_permalink() ); ?>" class="woocommerce-LoopProduct-link pp-card-link">
							<img src="<?php echo esc_url( $img_url ); ?>"
								 alt="<?php echo esc_attr( $wcp->get_name() ); ?>"
								 loading="lazy" />
						</a>

						<h3 class="woocommerce-loop-product__title">
							<a href="<?php echo esc_url( $wcp->get_permalink() ); ?>"><?php echo esc_html( $wcp->get_name() ); ?></a>
						</h3>

						<div class="price-container">
							<?php if ( $on_sale ) : ?>
							<span class="old-price"><?php echo wc_price( $regular ); ?></span>
							<span class="new-price"><?php echo wc_price( $price ); ?></span>
							<?php else : ?>
							<span class="new-price"><?php echo $wcp->get_price_html(); ?></span>
							<?php endif; ?>
						</div>

						<a href="<?php echo $btn_url; ?>" class="<?php echo $btn_class; ?>"<?php echo $btn_extra; ?>><?php echo $btn_text; ?></a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="pp-swiper-prev swiper-button-prev"></div>
			<div class="pp-swiper-next swiper-button-next"></div>
			<div class="pp-swiper-pagination swiper-pagination"></div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'carousel_slide',          'pp_carousel_render' );
add_shortcode( 'custom_carousel_slider',  'pp_carousel_render' );
add_shortcode( 'pp_carousel',             'pp_carousel_render' );

// ── Init Swiper en footer ──────────────────────────────────────────────────
function pp_carousel_footer_init() {
	$ids = $GLOBALS['_pp_carousel_inits'] ?? [];
	if ( empty( $ids ) ) return;
	?>
	<script>
	(function () {
		var breakpoints = {
			480:  { slidesPerView: 2, spaceBetween: 12 },
			768:  { slidesPerView: 3, spaceBetween: 16 },
			1024: { slidesPerView: 4, spaceBetween: 20 }
		};
		<?php foreach ( $ids as $uid ) : ?>
		new Swiper('#<?php echo esc_js( $uid ); ?>', {
			slidesPerView: 1.3,
			spaceBetween: 12,
			grabCursor: true,
			breakpoints: breakpoints,
			pagination: {
				el: '#<?php echo esc_js( $uid ); ?> .pp-swiper-pagination',
				clickable: true
			},
			navigation: {
				nextEl: '#<?php echo esc_js( $uid ); ?> .pp-swiper-next',
				prevEl: '#<?php echo esc_js( $uid ); ?> .pp-swiper-prev'
			}
		});
		<?php endforeach; ?>
	}());
	</script>
	<?php
}
