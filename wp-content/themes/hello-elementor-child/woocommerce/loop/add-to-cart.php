<?php
/**
 * Loop Add to Cart
 *
 * @version 9.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

// Envío gratis con icono
$custom_delivery = get_post_meta($product->get_id(), '_custom_delivery_delay', true);
$isotype = 'https://padelprofideutschland.de/wp-content/uploads/2024/12/Padel-Profi-Favicon.png';

$now_ts = current_time('timestamp');
$dayN  = date('N', $now_ts);
$hourG = date('G', $now_ts);

$format_date_de_den = function($timestamp) {
    if (class_exists('IntlDateFormatter')) {
        $tz = get_option('timezone_string');
        if (!$tz) { $tz = 'Europe/Berlin'; }
        $fmt = new IntlDateFormatter(
            'de_DE',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $tz,
            IntlDateFormatter::GREGORIAN,
            "EEEE, 'den' d. MMMM"
        );
        return $fmt->format($timestamp);
    }
    return date('l, \d\e\n d. F', $timestamp);
};

echo '<div class="free-shipping-catalog" style="display: flex; align-items: flex-start; justify-content: center; gap: 5px; margin-bottom: 0; max-width: 100%;">';
echo '<img src="' . esc_url($isotype) . '" alt="Free Shipping" style="min-width: 20px; max-width: 20px; flex-shrink: 0; margin-top: 2px;" />';
echo '<p class="textInLoop" style="margin: 0; font-size: 11px; line-height: 1.4; text-align: center;">';
echo '<span style="color:#00bf63; font-weight: 600; font-size: 11px;">Gratisversand.</span> ';

if ( has_term('envios-urgentes', 'product_cat', $product->get_id()) && $dayN >= 1 && $dayN <= 4 && $hourG < 16 ) {
    $tomorrow_ts   = strtotime('+1 weekday', $now_ts);
    $fecha_entrega = $format_date_de_den($tomorrow_ts);
    echo '<span style="color: #000; font-size: 11px;">Lieferung am ' . esc_html($fecha_entrega) . '</span>';
} elseif ( $custom_delivery === 'yes' ) {
    echo '<span style="color: #000; font-size: 11px;">Lieferung in 4–5 Tagen</span>';
} else {
    echo '<span style="color: #000; font-size: 11px;">Lieferung in 48–72 Std.</span>';
}

echo '</p>';
echo '</div>';

if ( ! has_term('praemien', 'product_cat', $product->get_id()) ) {
    $id    = absint( $product->get_id() );
    $label = esc_html__( 'In den Warenkorb', 'woocommerce' );
    ?>
    <div class="mi-btn-add-to-cart-container" style="text-align: center; margin-top: 8px; min-height: 42px; display: flex; align-items: center; justify-content: center;">
        <button type="button"
                data-quantity="1"
                data-product_id="<?php echo $id; ?>"
                data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                data-product_url="<?php echo esc_url( get_permalink( $id ) ); ?>"
                class="button mi-btn-add-to-cart-carousel"
                style="display: inline-block; background: #fe6100; color: white; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; transition: background 0.3s ease; border: none; white-space: nowrap; cursor: pointer; font-family: inherit;">
           <?php echo $label; ?>
        </button>
    </div>
    <?php
}
?>