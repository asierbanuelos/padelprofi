/**
 * Scripts para WooCommerce Featured Padel Products
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Tracking de clics (opcional)
        $('.wfpp-cta-button').on('click', function() {
            var productTitle = $(this).closest('.wfpp-product-card').find('.wfpp-product-title').text().trim();
            
            // Log para debug
            console.log('Producto clickeado:', productTitle);
            
            // Aquí puedes añadir Google Analytics o similar
            // gtag('event', 'product_click', { 'product_name': productTitle });
        });
        
    });
    
})(jQuery);
