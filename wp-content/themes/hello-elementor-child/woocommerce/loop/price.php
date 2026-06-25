<?php
/**
 * Loop Price (Modificado para productos variables y categoría 'praemien')
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

// Precio centrado
$price_html = '<div class="price-container" style="text-align: center;">';

// ✅ Si es producto de categoría 'praemien', mostrar puntos en vez del precio
if ( has_term('praemien', 'product_cat', $product->get_id()) ) {
    $precio_puntos = get_post_meta($product->get_id(), '_precio_puntos', true);

    if ($precio_puntos) {
        $price_html .= '<span class="precio-en-puntos">🎁 ' . esc_html($precio_puntos) . ' Punkte</span>';
    } else {
        $price_html .= '<span class="precio-en-puntos">🎁 Preis in Punkten nicht verfügbar</span>';
    }

} else {
    // 🔁 Productos normales
    if ( $product->is_type('variable') ) {
        $min_regular_price = $product->get_variation_regular_price('min', true);
        $min_sale_price    = $product->get_variation_sale_price('min', true);

        if ( $min_sale_price && $min_sale_price < $min_regular_price ) {
            $price_html .= '<span class="new-price">' . wc_price( $min_sale_price ) . '</span> ';
            $price_html .= '<span class="old-price">' . wc_price( $min_regular_price ) . '</span>';
        } else {
            $price_html .= '<span class="regular-price">' . wc_price( $min_regular_price ) . '</span>';
        }

    } else {
        // Producto simple
        if ( $product->is_on_sale() ) {
            $price_html .= '<span class="new-price">' . wc_price( $product->get_sale_price() ) . '</span> ';
            $price_html .= '<span class="old-price">' . wc_price( $product->get_regular_price() ) . '</span>';
        } else {
            $price_html .= '<span class="regular-price">' . wc_price( $product->get_price() ) . '</span>';
        }
    }
}

$price_html .= '</div>';

echo $price_html;
?>