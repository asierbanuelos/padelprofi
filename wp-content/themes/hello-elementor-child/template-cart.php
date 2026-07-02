<?php
/**
 * Template Name: Carrito PadelProfi
 * Template Post Type: page
 *
 * Plantilla de página personalizada para el carrito.
 * Asigna esta plantilla a la página /warenkorb-2/ desde
 * WP Admin → Páginas → Editar → Atributos de página → Plantilla.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<main id="pp-cart-page-wrap" class="pp-cart-page-wrap">
	<?php echo pp_render_cart_page(); ?>
</main>

<?php
get_footer();
