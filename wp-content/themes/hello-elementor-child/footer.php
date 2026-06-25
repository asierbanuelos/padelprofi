<?php
/**
 * The template for displaying the footer.
 *
 * Contains the body & html closing tags.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) {
	if ( hello_elementor_display_header_footer() ) {
		if ( did_action( 'elementor/loaded' ) && hello_header_footer_experiment_active() ) {
			get_template_part( 'template-parts/dynamic-footer' );
		} else {
			get_template_part( 'template-parts/footer' );
		}
	}
}
?>

<script>

document.addEventListener("DOMContentLoaded", function() {
    const addToCartButton = document.getElementById("add-to-cart");

    if (addToCartButton) {
        addToCartButton.addEventListener("click", function() {
            const productId = addToCartButton.getAttribute("data-product-id");

            if (!productId) {
                return;
            }

            // Realizar la solicitud AJAX para añadir al carrito
            jQuery.ajax({
                url: '/wp-admin/admin-ajax.php',  // URL de AJAX de WordPress
                type: 'POST',
                data: {
                    action: 'woocommerce_add_to_cart',  // Acción de WooCommerce para añadir al carrito
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    if (response && response.fragments) {
                        // Producto añadido correctamente al carrito, redirigir al carrito
                        window.location.href = "/warenkorb"; // Cambia "/cart" si tienes una URL diferente para el carrito
                    } else {
                    }
                },
                error: function() {
                }
            });
        });
    }
});

</script>



<?php wp_footer(); ?>
</body>

</html>
