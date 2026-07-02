<?php
/**
 * Custom Cart Page — MediaMarkt style for PadelProfi
 * Overrides woocommerce/templates/cart/cart.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$cart       = WC()->cart;
$cart_items = $cart->get_cart();
$nonce      = wp_create_nonce( 'pp_cart_page_nonce' );

do_action( 'woocommerce_before_cart' );
?>
<div class="pp-cart-page" id="pp-cart-page" data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<?php wc_print_notices(); ?>

	<?php if ( $cart->is_empty() ) : ?>
		<div class="pp-cart-empty">
			<div class="pp-cart-empty__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="64" height="64">
					<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
					<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
				</svg>
			</div>
			<h2><?php esc_html_e( 'Dein Warenkorb ist leer', 'hello-elementor-child' ); ?></h2>
			<p><?php esc_html_e( 'Entdecke unsere Produkte und füge sie deinem Warenkorb hinzu.', 'hello-elementor-child' ); ?></p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pp-cart-empty__cta">
				<?php esc_html_e( 'Weiter einkaufen', 'hello-elementor-child' ); ?>
			</a>
		</div>

	<?php else : ?>

	<div class="pp-cart-page__layout">

		<!-- ─── LEFT: Cart items ──────────────────────────────────────── -->
		<div class="pp-cart-page__items" id="pp-cart-items">

			<div class="pp-cart-page__header">
				<h1 class="pp-cart-page__title"><?php esc_html_e( 'Warenkorb', 'hello-elementor-child' ); ?></h1>
				<span class="pp-cart-page__count" id="pp-cart-count">
					<?php echo esc_html( $cart->get_cart_contents_count() . ' ' . _n( 'Artikel', 'Artikel', $cart->get_cart_contents_count(), 'hello-elementor-child' ) ); ?>
				</span>
			</div>

			<?php foreach ( $cart_items as $cart_item_key => $cart_item ) :
				$product    = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = absint( $cart_item['product_id'] );

				if ( ! $product || ! $product->exists() || 0 === $cart_item['quantity'] ) continue;

				$img_src = get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' )
					?: wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$qty = (int) $cart_item['quantity'];

				// ── Cross-sells para este producto ────────────────────────
				$exclude_ids = array_map( function( $ci ) { return absint( $ci['product_id'] ); }, array_values( $cart_items ) );

				$cs_ids = pp_get_crosssell_ids( $product_id, $exclude_ids );
				if ( empty( $cs_ids ) ) {
					$cs_ids = pp_get_related_product_ids_for_product( $product_id, $exclude_ids );
				}
				if ( empty( $cs_ids ) ) {
					$native = wc_get_related_products( $product_id, 8 );
					$cs_ids = array_values( array_diff( $native, $exclude_ids ) );
				}

				$cs_products = [];
				foreach ( array_slice( (array) $cs_ids, 0, 12 ) as $cid ) {
					if ( count( $cs_products ) >= 4 ) break;
					$cid = absint( $cid );
					$cp  = wc_get_product( $cid );
					if ( ! $cp || ! $cp->is_purchasable() || ! $cp->is_in_stock() ) continue;
					$cp_img = get_the_post_thumbnail_url( $cid, 'thumbnail' ) ?: wc_placeholder_img_src( 'thumbnail' );
					$cs_products[] = compact( 'cid', 'cp', 'cp_img' );
				}
			?>
			<div class="pp-cart-item" id="pp-cart-item-<?php echo esc_attr( $cart_item_key ); ?>" data-key="<?php echo esc_attr( $cart_item_key ); ?>">

				<div class="pp-cart-item__main">
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="pp-cart-item__img-wrap">
						<img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" width="110" height="110" />
					</a>

					<div class="pp-cart-item__info">
						<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="pp-cart-item__name">
							<?php echo esc_html( $product->get_name() ); ?>
						</a>
						<?php if ( $product->is_on_sale() ) : ?>
						<div class="pp-cart-item__price pp-cart-item__price--sale">
							<del><?php echo wc_price( (float) $product->get_regular_price() ); ?></del>
							<ins><?php echo wc_price( (float) $product->get_sale_price() ); ?></ins>
						</div>
						<?php else : ?>
						<div class="pp-cart-item__price">
							<?php echo $product->get_price_html(); ?>
						</div>
						<?php endif; ?>
					</div>

					<div class="pp-cart-item__controls">
						<div class="pp-cart-item__qty-wrap">
							<button class="pp-cqty-btn pp-cqty-minus" data-key="<?php echo esc_attr( $cart_item_key ); ?>" <?php disabled( $qty <= 1 ); ?>>−</button>
							<span class="pp-cqty-val" id="pp-qty-<?php echo esc_attr( $cart_item_key ); ?>"><?php echo esc_html( $qty ); ?></span>
							<button class="pp-cqty-btn pp-cqty-plus" data-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
						</div>
						<div class="pp-cart-item__line-price" id="pp-line-<?php echo esc_attr( $cart_item_key ); ?>">
							<?php echo wc_price( $cart_item['line_subtotal'] ); ?>
						</div>
						<button class="pp-cart-item__remove" data-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="Entfernen">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
								<polyline points="3 6 5 6 21 6"/>
								<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
							</svg>
						</button>
					</div>
				</div>

				<?php if ( ! empty( $cs_products ) ) : ?>
				<div class="pp-cart-item__cs">
					<div class="pp-cart-item__cs-title"><?php esc_html_e( 'Das könnte dich auch interessieren:', 'hello-elementor-child' ); ?></div>
					<div class="pp-cart-item__cs-list">
						<?php foreach ( $cs_products as $cs ) :
							/** @var WC_Product $cp */
							$cp = $cs['cp']; $cid = $cs['cid']; $cp_img = $cs['cp_img'];
						?>
						<div class="pp-cs-card">
							<a href="<?php echo esc_url( get_permalink( $cid ) ); ?>" class="pp-cs-card__img-wrap">
								<img src="<?php echo esc_url( $cp_img ); ?>" alt="<?php echo esc_attr( $cp->get_name() ); ?>" width="80" height="80" loading="lazy" />
							</a>
							<a href="<?php echo esc_url( get_permalink( $cid ) ); ?>" class="pp-cs-card__name"><?php echo esc_html( $cp->get_name() ); ?></a>
							<div class="pp-cs-card__price"><?php echo $cp->get_price_html(); ?></div>
							<?php if ( $cp->get_type() === 'simple' ) : ?>
							<button class="pp-cs-card__btn" data-product-id="<?php echo esc_attr( $cid ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
								<?php esc_html_e( 'In den Warenkorb', 'hello-elementor-child' ); ?>
							</button>
							<?php else : ?>
							<a href="<?php echo esc_url( get_permalink( $cid ) ); ?>" class="pp-cs-card__btn pp-cs-card__btn--link">
								<?php esc_html_e( 'Ansehen', 'hello-elementor-child' ); ?>
							</a>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

			</div>
			<?php endforeach; ?>

		</div><!-- /.pp-cart-page__items -->

		<!-- ─── RIGHT: Order summary ─────────────────────────────────── -->
		<div class="pp-cart-page__summary">
			<div class="pp-cart-summary" id="pp-cart-summary">
				<h2 class="pp-cart-summary__title"><?php esc_html_e( 'Bestellübersicht', 'hello-elementor-child' ); ?></h2>

				<div class="pp-cart-summary__rows" id="pp-summary-rows">
					<?php echo pp_cart_summary_html(); ?>
				</div>

				<!-- Coupon -->
				<div class="pp-cart-summary__coupon">
					<div class="pp-coupon-form">
						<input type="text" id="pp-coupon-input" class="pp-coupon-input"
							placeholder="<?php esc_attr_e( 'Gutscheincode', 'hello-elementor-child' ); ?>" />
						<button id="pp-coupon-apply" class="pp-coupon-btn">
							<?php esc_html_e( 'Einlösen', 'hello-elementor-child' ); ?>
						</button>
					</div>
					<div id="pp-coupon-msg" class="pp-coupon-msg"></div>
				</div>

				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="pp-cart-checkout-btn">
					<?php esc_html_e( 'Zur Kasse', 'hello-elementor-child' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
						<path d="M5 12h14M12 5l7 7-7 7"/>
					</svg>
				</a>

				<p class="pp-cart-summary__secure">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<?php esc_html_e( 'Sicherer Kauf – SSL-verschlüsselt', 'hello-elementor-child' ); ?>
				</p>
			</div>
		</div>

	</div><!-- /.pp-cart-page__layout -->

	<?php endif; ?>

</div><!-- /.pp-cart-page -->

<?php do_action( 'woocommerce_after_cart' ); ?>
