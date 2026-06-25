<?php
/**
 * Encolar estilos y scripts
 */

// Encolar estilos y scripts en el frontend
add_action('wp_enqueue_scripts', 'wfpp_enqueue_assets');
function wfpp_enqueue_assets() {
    
    // CSS
    wp_enqueue_style(
        'wfpp-styles',
        WFPP_PLUGIN_URL . 'assets/css/styles.css',
        array(),
        WFPP_VERSION
    );
    
    // JavaScript (opcional)
    wp_enqueue_script(
        'wfpp-scripts',
        WFPP_PLUGIN_URL . 'assets/js/scripts.js',
        array('jquery'),
        WFPP_VERSION,
        true
    );
}
