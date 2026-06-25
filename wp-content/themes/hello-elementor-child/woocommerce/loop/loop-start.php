<?php
/**
 * Product Loop Start
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/loop-start.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.3.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if(esc_attr( wc_get_loop_prop('columns' ) )==6){?>
<?php 
if ( ! wp_doing_ajax() && shortcode_exists('render_subcategories_slider') ) {
    echo do_shortcode('[render_subcategories_slider]');
}
?>
<div class="woocommerce-ordering">
	<a style="border:none; margin-left:-20px; cursor:pointer; display:inline-flex; align-items:center; text-decoration:none; font-size:16px; color:black;" href="https://padelprofideutschland.de/padel-produkte-vergleichen">
  		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" style="width:17px; height:17px; margin-right:4px;">
   			 <path d="M7.59991 23.5313L14.9999 23.5313C16.0999 23.5313 16.9999 22.6172 16.9999 21.5L16.9999 7.28125C16.9999 6.16406 16.0999 5.25 14.9999 5.25L4.99991 5.25C3.89991 5.25 2.99991 6.16406 2.99991 7.28125L2.99991 18.8594L2.99991 21.5313C2.99991 22.6358 3.89534 23.5313 4.99991 23.5313L7.59991 23.5313ZM4.99991 7.28125L14.9999 7.28125L14.9999 21.5L8.99991 21.5L4.99991 21.5L4.99991 17.4375L4.99991 7.28125ZM20.9999 19.4687L18.9999 19.4687L18.9999 3.21875L6.99991 3.21875L6.99991 1.1875L18.9999 1.1875C20.0999 1.1875 20.9999 2.10156 20.9999 3.21875L20.9999 19.4687Z"></path>
 		 </svg>
  Vergleichen
		<div class="tooltip-container">
	 	 <span class="tooltip-text" id="tooltip_bottom">Produkte vergleichen</span>
		</div>
	</a>
	
	
<br>
    <a href="#" class="orderby" data-order="menu_order">Neuheiten</a>
    <a href="#" class="orderby" data-order="popularity">Bestseller</a>
    <a href="#" class="orderby" data-order="rating">Bewertung</a>
    <a href="#" class="orderby" data-order="date">Datum</a>
    <a href="#" class="orderby" data-order="price">Niedrigster Preis</a>
    <a href="#" class="orderby" data-order="price-desc">Höchster Preis</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var orderLinks = document.querySelectorAll('.orderby');
    
    orderLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            var order = this.getAttribute('data-order');
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('orderby');
            currentUrl.searchParams.set('orderby', order);
            window.location.href = currentUrl.toString();
        });
    });
});
</script>
<?php }?>
<ul class="products columns-<?php echo esc_attr( wc_get_loop_prop( 'columns' ) ); ?>">