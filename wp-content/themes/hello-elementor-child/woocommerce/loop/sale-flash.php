<?php
/**
 * Product loop sale flash
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/sale-flash.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $product;


	// Mostrar etiqueta de descuento si el producto está en oferta
		$regular_price = floatval($product->get_regular_price());
		$sale_price = floatval($product->get_sale_price());
	
		if($regular_price!=0 && $sale_price!=0 && $sale_price!=$regular_price ){
			if ($regular_price > 0) { // Evita la división por cero
				$discount_percentage = 100 - ( ($sale_price / $regular_price) * 100 );
				echo '<div class="discount-label">-' . round($discount_percentage) . '%</div>';
			}
		}
	

?>
