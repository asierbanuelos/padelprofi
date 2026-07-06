<?php
/**
 * Cart Page Template - PadelProfi
 * Incluido por pp_render_cart_page() via ob_start/include.
 * Variables disponibles: $cart (WC_Cart)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$cart_items  = $cart->get_cart();
$total_count = $cart->get_cart_contents_count();

// ── Cross-sells: recopilar IDs de todos los productos del carrito ──────────
$all_cart_ids = [];
$cart_products = []; // guardar los objetos WC_Product para consultas posteriores
foreach ( $cart_items as $ci ) {
	if ( ! empty( $ci['data'] ) ) {
		$pid = absint( $ci['data']->get_id() );
		$all_cart_ids[]      = $pid;
		$cart_products[$pid] = $ci['data'];
	}
}

$all_cs_ids = [];

foreach ( $all_cart_ids as $cpid ) {
	$cp_obj = isset( $cart_products[ $cpid ] ) ? $cart_products[ $cpid ] : null;

	// Capa 1: reglas personalizadas por categoría (admin popup)
	if ( function_exists( 'pp_get_crosssell_ids' ) ) {
		$ids = (array) pp_get_crosssell_ids( $cpid, [] );
		$all_cs_ids = array_merge( $all_cs_ids, $ids );
	}

	// Capa 2: cross-sells nativos de WooCommerce (configurados en el producto)
	if ( $cp_obj ) {
		$native = (array) $cp_obj->get_cross_sell_ids();
		$all_cs_ids = array_merge( $all_cs_ids, $native );
	}

	// Capa 3: productos relacionados personalizados (admin popup)
	if ( function_exists( 'pp_get_related_product_ids_for_product' ) ) {
		$ids = (array) pp_get_related_product_ids_for_product( $cpid, [] );
		$all_cs_ids = array_merge( $all_cs_ids, $ids );
	}

	// Capa 4: productos relacionados nativos de WC (misma categoría/tags)
	$related = (array) wc_get_related_products( $cpid, 12 );
	$all_cs_ids = array_merge( $all_cs_ids, $related );
}

// Deduplicar y excluir los que ya están en el carrito
$all_cs_ids = array_values( array_unique( array_diff( $all_cs_ids, $all_cart_ids ) ) );

// Construir array de cross-sell products (máx. 8)
$generic_cs = [];
$checked    = 0;
foreach ( $all_cs_ids as $cid ) {
	if ( count( $generic_cs ) >= 8 ) break;
	$checked++;
	if ( $checked > 40 ) break; // evitar consultas excesivas
	$cid = absint( $cid );
	$cp  = wc_get_product( $cid );
	if ( ! $cp || ! $cp->is_purchasable() || ! $cp->is_in_stock() ) continue;
	$cp_img_id = $cp->get_image_id();
	$cp_img    = $cp_img_id ? wp_get_attachment_image_url( $cp_img_id, 'thumbnail' ) : '';
	if ( ! $cp_img ) $cp_img = wc_placeholder_img_src( 'thumbnail' );
	$generic_cs[] = [ 'cid' => $cid, 'cp' => $cp, 'cp_img' => $cp_img ];
}
?>
<div class="pp-cart-page">

	<!-- ======================================================
	     Columna principal
	     ====================================================== -->
	<div class="pp-cart-main">

		<div class="pp-cart-header">
			<h1 class="pp-cart-title"><?php esc_html_e( 'Warenkorb', 'hello-elementor-child' ); ?></h1>
			<span id="pp-cart-count" class="pp-cart-count"><?php echo esc_html( $total_count . ' ' . _n( 'Artikel', 'Artikel', $total_count, 'hello-elementor-child' ) ); ?></span>
		</div>

		<?php foreach ( $cart_items as $cart_item_key => $cart_item ) :

			if ( empty( $cart_item['data'] ) ) continue;

			/** @var WC_Product $product */
			$product    = $cart_item['data'];
			$product_id = absint( $product->get_id() );

			if ( ! $product->exists() || 0 === (int) $cart_item['quantity'] ) continue;

			$qty     = (int) $cart_item['quantity'];
			$img_id  = $product->get_image_id();
			$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';
			if ( ! $img_url ) $img_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );

			// Descripción corta o atributos de variación
			$item_meta = '';
			if ( $product->is_type( 'variation' ) ) {
				$var_attrs = $product->get_variation_attributes();
				$parts     = [];
				foreach ( $var_attrs as $attr_key => $attr_val ) {
					if ( ! $attr_val ) continue;
					$label   = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ), $product );
					$parts[] = '<span class="pp-meta-label">' . esc_html( $label ) . ':</span> '
						. '<span class="pp-meta-val">' . esc_html( $attr_val ) . '</span>';
				}
				$item_meta = implode( '&ensp;&middot;&ensp;', $parts );
			} else {
				$desc = $product->get_short_description();
				if ( $desc ) {
					$item_meta = '<span class="pp-meta-desc">' . wp_kses_post( wp_trim_words( wp_strip_all_tags( $desc ), 14, '...' ) ) . '</span>';
				}
			}
		?>
		<div class="pp-cart-item" id="pp-cart-item-<?php echo esc_attr( $cart_item_key ); ?>">
			<div class="pp-cart-item__main">

				<a class="pp-cart-item__imglink" href="<?php echo esc_url( $product->get_permalink() ); ?>">
					<img class="pp-cart-item__img"
						src="<?php echo esc_url( $img_url ); ?>"
						alt="<?php echo esc_attr( $product->get_name() ); ?>"
						width="100" height="100">
				</a>

				<div class="pp-cart-item__info">
					<a class="pp-cart-item__name" href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php echo esc_html( $product->get_name() ); ?>
					</a>

					<?php if ( $item_meta ) : ?>
					<p class="pp-cart-item__meta"><?php echo $item_meta; ?></p>
					<?php endif; ?>

					<div class="pp-cart-item__price-row">
						<?php if ( $product->is_on_sale() ) : ?>
						<span class="pp-cart-item__price pp-sale">
							<del><?php echo wp_kses_post( wc_price( (float) $product->get_regular_price() ) ); ?></del>
							<?php echo wp_kses_post( $product->get_price_html() ); ?>
						</span>
						<?php else : ?>
						<span class="pp-cart-item__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						<?php endif; ?>
					</div>

					<div class="pp-cqty">
						<button class="pp-cqty-btn pp-cqty-minus"
							data-key="<?php echo esc_attr( $cart_item_key ); ?>"
							<?php if ( $qty <= 1 ) echo 'disabled="disabled"'; ?>>&#8722;</button>
						<span class="pp-cqty-val" id="pp-qty-<?php echo esc_attr( $cart_item_key ); ?>"><?php echo esc_html( $qty ); ?></span>
						<button class="pp-cqty-btn pp-cqty-plus"
							data-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
					</div>
				</div>

				<div class="pp-cart-item__right">
					<button class="pp-cart-item__remove"
						data-key="<?php echo esc_attr( $cart_item_key ); ?>"
						title="<?php esc_attr_e( 'Entfernen', 'hello-elementor-child' ); ?>">
						<svg width="10" height="10" viewBox="0 0 14 14" fill="none">
							<path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
						</svg>
					</button>
					<span class="pp-cart-item__line-price" id="pp-line-<?php echo esc_attr( $cart_item_key ); ?>">
						<?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $qty ) ); ?>
					</span>
				</div>

			</div>
		</div><!-- .pp-cart-item -->
		<?php endforeach; ?>

		<?php if ( ! empty( $generic_cs ) ) : ?>
		<!-- ── Cross-sells genéricos ── -->
		<div class="pp-cart-cs">
			<div class="pp-cart-cs__header">
				<h3 class="pp-cart-cs__title"><?php esc_html_e( 'Das konnte dich auch interessieren', 'hello-elementor-child' ); ?></h3>
				<div class="pp-cart-cs__arrows">
					<button class="pp-cs-arrow pp-cs-arrow--prev" aria-label="Zuruck">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button class="pp-cs-arrow pp-cs-arrow--next" aria-label="Weiter">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
			</div>
			<div class="pp-cart-cs__list">
				<?php foreach ( $generic_cs as $cs ) :
					$cp       = $cs['cp'];
					$cid      = $cs['cid'];
					$cp_img   = $cs['cp_img'];
					$on_sale  = $cp->is_on_sale();
					$reg_price = (float) $cp->get_regular_price();
					$sale_price = (float) $cp->get_price();
					$discount = ( $on_sale && $reg_price > 0 )
						? round( ( $reg_price - $sale_price ) / $reg_price * 100 )
						: 0;
					$rating      = (float) $cp->get_average_rating();
					$review_count = (int) $cp->get_review_count();
				?>
				<div class="pp-cs-card">

					<div class="pp-cs-card__img-wrap">
						<?php if ( $discount > 0 ) : ?>
						<span class="pp-cs-card__badge">-<?php echo $discount; ?>%</span>
						<?php endif; ?>
						<img class="pp-cs-card__img"
							src="<?php echo esc_url( $cp_img ); ?>"
							alt="<?php echo esc_attr( $cp->get_name() ); ?>"
							width="120" height="120" loading="lazy">
					</div>

					<div class="pp-cs-card__body">
						<?php if ( $rating > 0 ) : ?>
						<div class="pp-cs-card__rating">
							<span class="pp-cs-stars" style="--r:<?php echo esc_attr( $rating / 5 * 100 ); ?>%"></span>
							<span class="pp-cs-card__rcount"><?php echo esc_html( $review_count ); ?></span>
						</div>
						<?php endif; ?>

						<span class="pp-cs-card__name"><?php echo esc_html( $cp->get_name() ); ?></span>

						<div class="pp-cs-card__pricing">
							<?php if ( $on_sale && $reg_price > 0 ) : ?>
							<del class="pp-cs-card__reg"><?php echo wp_kses_post( wc_price( $reg_price ) ); ?></del>
							<strong class="pp-cs-card__price pp-cs-card__price--sale"><?php echo wp_kses_post( wc_price( $sale_price ) ); ?></strong>
							<?php else : ?>
							<strong class="pp-cs-card__price"><?php echo wp_kses_post( $cp->get_price_html() ); ?></strong>
							<?php endif; ?>
						</div>
					</div>

					<button class="pp-cs-card__btn" data-product-id="<?php echo esc_attr( $cid ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2"/><path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
						<?php esc_html_e( 'In den Warenkorb', 'hello-elementor-child' ); ?>
					</button>

				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

	</div><!-- .pp-cart-main -->

	<!-- ======================================================
	     Columna resumen
	     ====================================================== -->
	<div class="pp-cart-sidebar">
		<div class="pp-cart-summary">
			<h3 class="pp-summary-title"><?php esc_html_e( 'Bestellubersicht', 'hello-elementor-child' ); ?></h3>

			<div id="pp-summary-rows">
				<?php echo pp_cart_summary_html(); ?>
			</div>

			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="pp-checkout-btn">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
					<path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<?php esc_html_e( 'Zur Kasse gehen', 'hello-elementor-child' ); ?>
			</a>

			<p class="pp-summary-secure">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
				</svg>
				<?php esc_html_e( 'Sicher & verschlusselt bezahlen', 'hello-elementor-child' ); ?>
			</p>

			<div class="pp-coupon-wrap">
				<input id="pp-coupon-input" type="text"
					placeholder="<?php esc_attr_e( 'Gutscheincode eingeben', 'hello-elementor-child' ); ?>">
				<button id="pp-coupon-apply"><?php esc_html_e( 'Einlosen', 'hello-elementor-child' ); ?></button>
			</div>
			<p id="pp-coupon-msg" aria-live="polite"></p>
		</div>
	</div><!-- .pp-cart-sidebar -->

</div><!-- .pp-cart-page -->

<!-- ======================================================
     Footer móvil fijo (visible solo en < 680px via CSS)
     ====================================================== -->
<div id="pp-mobile-footer" class="pp-mobile-footer">

	<button type="button" id="pp-mobile-toggle" class="pp-mobile-footer__toggle">
		<span class="pp-mobile-footer__toggle-label"><?php esc_html_e( 'Bestellubersicht', 'hello-elementor-child' ); ?></span>
		<span class="pp-mobile-footer__toggle-price" id="pp-mobile-total"><?php echo wp_kses_post( $cart->get_total() ); ?></span>
		<svg class="pp-footer-toggle-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
	</button>

	<div id="pp-mobile-drawer" class="pp-mobile-drawer">
		<div class="pp-mobile-drawer__inner" id="pp-mobile-summary-rows">
			<?php echo pp_cart_summary_html(); ?>
		</div>
	</div>

	<div class="pp-mobile-footer__bottom">
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="pp-mobile-footer__btn">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Zur Kasse gehen', 'hello-elementor-child' ); ?>
		</a>
	</div>

</div><!-- .pp-mobile-footer -->
