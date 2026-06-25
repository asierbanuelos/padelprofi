<?php
/**
 * Plugin Name: WooCommerce Featured Padel Products
 * Plugin URI: https://example.com
 * Description: Selecciona 3 productos destacados de WooCommerce por categoría para mostrar en páginas
 * Version: 1.0.0
 * Author: Tu Nombre
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL2
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si WooCommerce está activo
add_action('admin_init', 'wfpp_check_woocommerce');
function wfpp_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wfpp_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function wfpp_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Featured Padel Products requiere que WooCommerce esté instalado y activado.', 'wfpp'); ?></p>
    </div>
    <?php
}

// Definir constantes
define('WFPP_VERSION', '1.0.0');
define('WFPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WFPP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios
require_once WFPP_PLUGIN_DIR . 'includes/category-fields.php';
require_once WFPP_PLUGIN_DIR . 'includes/product-fields.php';
require_once WFPP_PLUGIN_DIR . 'includes/page-meta-box.php';
require_once WFPP_PLUGIN_DIR . 'includes/shortcode.php';
require_once WFPP_PLUGIN_DIR . 'includes/enqueue.php';
require_once WFPP_PLUGIN_DIR . 'includes/attribute-icons.php';
