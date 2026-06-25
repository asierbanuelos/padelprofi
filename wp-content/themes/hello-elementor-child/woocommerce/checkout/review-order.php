<?php
/**
 * Review Order — Estilo MediaMarkt
 * Override de woocommerce/checkout/review-order.php
 * Se usa dentro del paso 4 (Resumen).
 *
 * @package hello-elementor-child
 * @version MM-1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="shop_table woocommerce-checkout-review-order-table">

	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Producto', 'woocommerce' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

		<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
			$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			if ( $product && $product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) :
		?>
		<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
			<td class="product-name">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key ) ); ?>
				&nbsp;<strong class="product-quantity">×&nbsp;<?php echo esc_html( apply_filters( 'woocommerce_checkout_cart_item_quantity', $cart_item['quantity'], $cart_item, $cart_item_key ) ); ?></strong>
				<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
			</td>
			<td class="product-total">
				<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
			</td>
		</tr>
		<?php endif; endforeach; ?>

		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	</tbody>

	<tfoot>
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php echo WC()->cart->get_cart_subtotal(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
		<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
			<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
			<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
		</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
		<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
		<tr class="woocommerce-shipping-totals shipping">
			<th><?php esc_html_e( 'Envío', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_shipping_html(); ?></td>
		</tr>
		<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
		<tr class="fee">
			<th><?php echo esc_html( $fee->name ); ?></th>
			<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
		</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) :
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() )
				? sprintf( ' <small>' . esc_html__( '(estimated for %s)', 'woocommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] )
				: '';

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) :
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) :
				?>
				<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th><?php echo esc_html( $tax->label ) . wp_kses_post( $estimated_text ); ?></th>
					<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
				</tr>
			<?php endforeach; else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ) . wp_kses_post( $estimated_text ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif;
		endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php echo WC()->cart->get_total(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>

</table>
