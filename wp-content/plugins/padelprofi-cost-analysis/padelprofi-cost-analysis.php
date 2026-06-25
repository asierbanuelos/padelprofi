<?php
/**
 * Plugin Name: PadelProfi Cost Analysis
 * Plugin URI: https://padelprofideutschland.de
 * Description: Sistema completo de análisis de costes y beneficios para WooCommerce con exportación Excel
 * Version: 3.6
 * Author: PadelProfi
 * Author URI: https://padelprofideutschland.de
 * Text Domain: padelprofi
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar que WooCommerce está activo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * FUNCIONES AUXILIARES
 */

function padelprofi_get_shipping_cost($order) {
    return 6.70;
}

function padelprofi_get_payment_fees($order) {
    $payment_fees = 0;
    $payment_fee_details = [];
    
    foreach ($order->get_fees() as $fee) {
        $fee_total = floatval($fee->get_total());
        if ($fee_total < 0) {
            $payment_fees += abs($fee_total);
            $payment_fee_details[] = [
                'name' => $fee->get_name(),
                'amount' => abs($fee_total)
            ];
        }
    }
    
    $paypal_fee = floatval($order->get_meta('_paypal_fee'));
    if ($paypal_fee > 0) {
        $payment_fees += $paypal_fee;
        $payment_fee_details[] = ['name' => 'Tarifa de PayPal', 'amount' => $paypal_fee];
    }
    
    $transaction_fee = floatval($order->get_meta('_transaction_fee'));
    if ($transaction_fee > 0) {
        $payment_fees += $transaction_fee;
        $payment_fee_details[] = ['name' => 'Comisión de transacción', 'amount' => $transaction_fee];
    }
    
    $stripe_fee = floatval($order->get_meta('_stripe_fee'));
    if ($stripe_fee > 0) {
        $payment_fees += $stripe_fee;
        $payment_fee_details[] = ['name' => 'Comisión de Stripe', 'amount' => $stripe_fee];
    }
    
    $fkwcs_stripe_fee = floatval($order->get_meta('_fkwcs_stripe_fee'));
    if ($fkwcs_stripe_fee > 0) {
        $payment_fees += $fkwcs_stripe_fee;
        $payment_fee_details[] = ['name' => 'Comisión de Stripe (FKWCS)', 'amount' => $fkwcs_stripe_fee];
    }
    
    return ['total' => $payment_fees, 'details' => $payment_fee_details];
}

function padelprofi_get_item_cost($item) {
    $saved_cost = $item->get_meta('_line_item_cost_price', true);
    
    if ($saved_cost !== '' && $saved_cost !== null) {
        return floatval($saved_cost);
    }
    
    $order = $item->get_order();
    if ($order) {
        $order_date = $order->get_date_created();
        $implementation_date = new DateTime('2025-12-23 00:00:00');
        
        if ($order_date < $implementation_date) {
            return 0;
        }
    }
    
    $product = $item->get_product();
    if ($product) {
        return floatval($product->get_meta('_cost_price_padelprofi', true));
    }
    
    return 0;
}

function padelprofi_get_order_calculations($order) {
    $total_cost = 0;
    $total_revenue = 0;
    
    foreach ($order->get_items() as $item) {
        $quantity = $item->get_quantity();
        $cost_price = padelprofi_get_item_cost($item);
        $line_total = $item->get_total();
        
        $total_cost += $cost_price * $quantity;
        $total_revenue += $line_total;
    }
    
    $total_tax = floatval($order->get_total_tax());
    $shipping_cost = padelprofi_get_shipping_cost($order);
    $shipping_charged = floatval($order->get_shipping_total());
    
    $payment_fees_data = padelprofi_get_payment_fees($order);
    $payment_fees = $payment_fees_data['total'];
    
    $gross_profit = $total_revenue - $total_cost;
    
    $order_total_with_tax = floatval($order->get_total());
    $net_profit = $order_total_with_tax - $total_tax - $total_cost - $shipping_cost + $shipping_charged - $payment_fees;
    
    $gross_margin_percent = $total_revenue > 0 ? ($gross_profit / $total_revenue) * 100 : 0;
    $net_margin_percent = $order_total_with_tax > 0 ? ($net_profit / $order_total_with_tax) * 100 : 0;
    
    return [
        'total_cost' => $total_cost,
        'total_revenue' => $total_revenue,
        'gross_profit' => $gross_profit,
        'gross_margin_percent' => $gross_margin_percent,
        'net_profit' => $net_profit,
        'net_margin_percent' => $net_margin_percent,
        'shipping_cost' => $shipping_cost,
        'shipping_charged' => $shipping_charged,
        'payment_fees' => $payment_fees,
        'payment_fees_details' => $payment_fees_data['details'],
        'total_tax' => $total_tax
    ];
}

/**
 * CLASE PRINCIPAL DEL PLUGIN
 */
class PadelProfi_Cost_Analysis {
    
    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'check_dependencies'));
        
        // Product cost field
        add_action('woocommerce_product_options_pricing', array($this, 'add_cost_field'));
        add_action('woocommerce_admin_process_product_object', array($this, 'save_cost_field'));
        
        // Save cost in order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_cost_in_order_item'), 10, 4);
        add_action('woocommerce_new_order_item', array($this, 'save_cost_in_new_item'), 10, 3);
        
        // Product export columns
        add_filter('woocommerce_product_export_column_names', array($this, 'add_product_export_columns'));
        add_filter('woocommerce_product_export_product_default_columns', array($this, 'add_product_export_columns'));
        add_filter('woocommerce_product_export_product_column_cost_price_padelprofi', array($this, 'export_product_cost'), 10, 2);
        
        // Order list columns
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_columns'), 20);
        add_action('manage_shop_order_posts_custom_column', array($this, 'populate_order_columns'), 10, 2);
        
        // Order totals section
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'add_order_totals_section'));
        
        // Meta box with detailed analysis
        add_action('add_meta_boxes', array($this, 'add_cost_analysis_meta_box'));
        
        // Save profit meta data
        add_action('woocommerce_checkout_order_processed', array($this, 'save_order_profit_meta'), 20);
        add_action('woocommerce_order_status_changed', array($this, 'save_order_profit_meta'), 20);
        add_action('woocommerce_saved_order_items', array($this, 'save_order_profit_meta'), 20);
        
        // Sistema de exportación
        add_action('admin_menu', array($this, 'add_export_menu'));
        add_action('wp_ajax_padelprofi_export_csv', array($this, 'ajax_export_csv'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('padelprofi', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p><strong>PadelProfi Cost Analysis</strong> requiere WooCommerce para funcionar.</p></div>';
            });
        }
    }
    
    public function add_cost_field() {
        woocommerce_wp_text_input([
            'id' => '_cost_price_padelprofi',
            'label' => 'Precio de Coste (€)',
            'description' => 'Precio de coste del producto para cálculos de beneficio',
            'type' => 'number',
            'custom_attributes' => ['step' => '0.01', 'min' => '0'],
            'desc_tip' => true
        ]);
    }
    
    public function save_cost_field($product) {
        $cost_price = isset($_POST['_cost_price_padelprofi']) ? sanitize_text_field($_POST['_cost_price_padelprofi']) : '';
        $product->update_meta_data('_cost_price_padelprofi', $cost_price);
    }
    
    public function save_cost_in_order_item($item, $cart_item_key, $values, $order) {
        if (!empty($values['data'])) {
            $product = $values['data'];
            $cost_price = $product->get_meta('_cost_price_padelprofi', true);
            if ($cost_price !== '') {
                $item->update_meta_data('_line_item_cost_price', $cost_price);
            }
        }
    }
    
    public function save_cost_in_new_item($item_id, $item, $order_id) {
        if ($item->get_type() !== 'line_item') {
            return;
        }
        
        $product = $item->get_product();
        if ($product) {
            $cost_price = $product->get_meta('_cost_price_padelprofi', true);
            if ($cost_price !== '') {
                $item->update_meta_data('_line_item_cost_price', $cost_price);
                $item->save_meta_data();
            }
        }
    }
    
    public function add_product_export_columns($columns) {
        $columns['cost_price_padelprofi'] = 'Precio de Coste (€)';
        return $columns;
    }
    
    public function export_product_cost($value, $product) {
        return $product->get_meta('_cost_price_padelprofi', true);
    }
    
    public function add_order_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_total') {
                $new_columns['order_cost'] = 'Coste';
                $new_columns['order_profit'] = 'Beneficio';
                $new_columns['order_margin'] = 'Margen %';
            }
        }
        return $new_columns;
    }
    
    public function populate_order_columns($column, $post_id) {
        if (!in_array($column, ['order_cost', 'order_profit', 'order_margin'])) {
            return;
        }
        
        $order = wc_get_order($post_id);
        if (!$order) {
            return;
        }
        
        $calc = padelprofi_get_order_calculations($order);
        
        switch ($column) {
            case 'order_cost':
                echo wc_price($calc['total_cost']);
                break;
            case 'order_profit':
                $color = $calc['net_profit'] >= 0 ? 'green' : 'red';
                echo '<span style="color: ' . $color . '; font-weight: bold;">' . wc_price($calc['net_profit']) . '</span>';
                break;
            case 'order_margin':
                $color = $calc['net_margin_percent'] >= 0 ? 'green' : 'red';
                echo '<span style="color: ' . $color . ';">' . number_format($calc['net_margin_percent'], 2) . '%</span>';
                break;
        }
    }
    
    public function add_order_totals_section($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $calc = padelprofi_get_order_calculations($order);
        ?>
        <tr>
            <td class="label">Coste Total:</td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price($calc['total_cost']); ?></td>
        </tr>
        <tr>
            <td class="label">Beneficio Bruto:</td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price($calc['gross_profit']); ?> 
                <span style="color: <?php echo $calc['gross_margin_percent'] >= 0 ? 'green' : 'red'; ?>;">
                    (<?php echo number_format($calc['gross_margin_percent'], 2); ?>%)
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Beneficio Neto:</td>
            <td width="1%"></td>
            <td class="total">
                <strong style="color: <?php echo $calc['net_profit'] >= 0 ? 'green' : 'red'; ?>;">
                    <?php echo wc_price($calc['net_profit']); ?>
                </strong>
                <span style="color: <?php echo $calc['net_margin_percent'] >= 0 ? 'green' : 'red'; ?>;">
                    (<?php echo number_format($calc['net_margin_percent'], 2); ?>%)
                </span>
            </td>
        </tr>
        <?php
    }
    
    public function add_cost_analysis_meta_box() {
        add_meta_box(
            'padelprofi_order_cost_details',
            __('Kostenanalyse pro Produkt', 'padelprofi'),
            array($this, 'render_cost_analysis_meta_box'),
            'shop_order',
            'normal',
            'default'
        );
        
        add_meta_box(
            'padelprofi_order_cost_details',
            __('Kostenanalyse pro Produkt', 'padelprofi'),
            array($this, 'render_cost_analysis_meta_box'),
            'woocommerce_page_wc-orders',
            'normal',
            'default'
        );
    }
    
    public function render_cost_analysis_meta_box($post_or_order) {
        $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order->ID);
        if (!$order) return;
        
        ?>
        <style>
            .cost-analysis-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .cost-analysis-table th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; }
            .cost-analysis-table td { padding: 10px; border-bottom: 1px solid #dee2e6; }
            .cost-analysis-table tr:hover { background: #f8f9fa; }
            .cost-analysis-table .text-right { text-align: right; }
            .cost-analysis-table .text-center { text-align: center; }
            .profit-positive { color: #2e7d32; font-weight: bold; }
            .profit-negative { color: #c62828; font-weight: bold; }
        </style>
        
        <table class="cost-analysis-table">
            <thead>
                <tr>
                    <th><?php echo __('Produkt', 'padelprofi'); ?></th>
                    <th class="text-center"><?php echo __('Menge', 'padelprofi'); ?></th>
                    <th class="text-right"><?php echo __('Stückkosten', 'padelprofi'); ?></th>
                    <th class="text-right"><?php echo __('Gesamtkosten', 'padelprofi'); ?></th>
                    <th class="text-right"><?php echo __('Umsatz', 'padelprofi'); ?></th>
                    <th class="text-right"><?php echo __('Gewinn', 'padelprofi'); ?></th>
                    <th class="text-right"><?php echo __('Marge %', 'padelprofi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_cost = 0;
                $total_revenue = 0;
                
                foreach ($order->get_items() as $item) {
                    $quantity = $item->get_quantity();
                    $cost_price = padelprofi_get_item_cost($item);
                    $line_total = $item->get_total();
                    $line_cost = $cost_price * $quantity;
                    $line_profit = $line_total - $line_cost;
                    $line_margin = $line_total > 0 ? ($line_profit / $line_total) * 100 : 0;
                    
                    $total_cost += $line_cost;
                    $total_revenue += $line_total;
                    
                    $profit_class = $line_profit >= 0 ? 'profit-positive' : 'profit-negative';
                    
                    $saved_cost = $item->get_meta('_line_item_cost_price', true);
                    if ($saved_cost !== '' && $saved_cost !== null) {
                        $cost_source = '(gespeichert)';
                    } else {
                        $order_date = $order->get_date_created();
                        $implementation_date = new DateTime('2025-12-23 00:00:00');
                        if ($order_date < $implementation_date) {
                            $cost_source = '(alte Bestellung)';
                        } else {
                            $cost_source = '(aktueller Produktpreis)';
                        }
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($item->get_name()); ?></strong>
                            <?php if ($cost_price == 0): ?>
                                <br><small style="color: #f57c00;">⚠️ <?php echo __('Keine Kosten erfasst', 'padelprofi'); ?></small>
                            <?php else: ?>
                                <br><small style="color: #666;"><?php echo $cost_source; ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo $quantity; ?></td>
                        <td class="text-right"><?php echo wc_price($cost_price, ['currency' => $order->get_currency()]); ?></td>
                        <td class="text-right"><?php echo wc_price($line_cost, ['currency' => $order->get_currency()]); ?></td>
                        <td class="text-right"><?php echo wc_price($line_total, ['currency' => $order->get_currency()]); ?></td>
                        <td class="text-right <?php echo $profit_class; ?>">
                            <?php echo wc_price($line_profit, ['currency' => $order->get_currency()]); ?>
                        </td>
                        <td class="text-right <?php echo $profit_class; ?>">
                            <?php echo number_format($line_margin, 2); ?>%
                        </td>
                    </tr>
                    <?php
                }
                
                $total_tax = floatval($order->get_total_tax());
                $shipping_cost = padelprofi_get_shipping_cost($order);
                $shipping_charged = floatval($order->get_shipping_total());
                
                $payment_fees_data = padelprofi_get_payment_fees($order);
                $payment_fees = $payment_fees_data['total'];
                $payment_fee_details = $payment_fees_data['details'];
                
                $shipping_methods = $order->get_shipping_methods();
                $shipping_method_name = '';
                if (!empty($shipping_methods)) {
                    $shipping_method = reset($shipping_methods);
                    $shipping_method_name = $shipping_method->get_method_title();
                }
                
                $gross_profit = $total_revenue - $total_cost;
                $order_total_with_tax = floatval($order->get_total());
                $net_profit = $order_total_with_tax - $total_tax - $total_cost - $shipping_cost + $shipping_charged - $payment_fees;
                
                $gross_margin = $total_revenue > 0 ? ($gross_profit / $total_revenue) * 100 : 0;
                $net_margin = $order_total_with_tax > 0 ? ($net_profit / $order_total_with_tax) * 100 : 0;
                
                $gross_class = $gross_profit >= 0 ? 'profit-positive' : 'profit-negative';
                $net_class = $net_profit >= 0 ? 'profit-positive' : 'profit-negative';
                ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #333; background: #f9f9f9;">
                    <td colspan="7" style="padding: 15px;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div style="border: 2px solid #e0e0e0; padding: 15px; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #333;">📊 Bruttogewinn</h4>
                                <table style="width: 100%; font-size: 14px;">
                                    <tr>
                                        <td>Umsatz (ohne MwSt):</td>
                                        <td style="text-align: right;"><strong><?php echo wc_price($total_revenue, ['currency' => $order->get_currency()]); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Produktkosten:</td>
                                        <td style="text-align: right; color: #c62828;"><strong>-<?php echo wc_price($total_cost, ['currency' => $order->get_currency()]); ?></strong></td>
                                    </tr>
                                    <tr style="border-top: 2px solid #333;">
                                        <td><strong>Bruttogewinn:</strong></td>
                                        <td style="text-align: right;" class="<?php echo $gross_class; ?>">
                                            <strong style="font-size: 18px;"><?php echo wc_price($gross_profit, ['currency' => $order->get_currency()]); ?></strong>
                                            <br><span style="font-size: 14px;">(<?php echo number_format($gross_margin, 2); ?>%)</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="border: 2px solid #2e7d32; padding: 15px; border-radius: 8px; background: #f1f8f4;">
                                <h4 style="margin: 0 0 10px 0; color: #2e7d32;">💰 Nettogewinn (Final)</h4>
                                <table style="width: 100%; font-size: 14px;">
                                    <tr>
                                        <td>Gesamtbetrag (mit MwSt):</td>
                                        <td style="text-align: right;"><?php echo wc_price($order_total_with_tax, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <tr>
                                        <td>- MwSt (19%):</td>
                                        <td style="text-align: right; color: #c62828;">-<?php echo wc_price($total_tax, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <tr>
                                        <td>- Produktkosten:</td>
                                        <td style="text-align: right; color: #c62828;">-<?php echo wc_price($total_cost, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            - Versandkosten:
                                            <?php if ($shipping_method_name): ?>
                                                <br><small style="color: #666;">(<?php echo esc_html($shipping_method_name); ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; color: #c62828;">-<?php echo wc_price($shipping_cost, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <tr>
                                        <td>+ Versand berechnet:</td>
                                        <td style="text-align: right; color: #2e7d32;">+<?php echo wc_price($shipping_charged, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <?php if ($payment_fees > 0): ?>
                                    <tr>
                                        <td>
                                            - Zahlungsgebühren:
                                            <?php if (!empty($payment_fee_details)): ?>
                                                <?php foreach ($payment_fee_details as $detail): ?>
                                                    <br><small style="color: #666;">(<?php echo esc_html($detail['name']); ?>: <?php echo wc_price($detail['amount'], ['currency' => $order->get_currency()]); ?>)</small>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; color: #c62828;">-<?php echo wc_price($payment_fees, ['currency' => $order->get_currency()]); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr style="border-top: 2px solid #2e7d32;">
                                        <td><strong style="color: #2e7d32;">NETTOGEWINN:</strong></td>
                                        <td style="text-align: right;" class="<?php echo $net_class; ?>">
                                            <strong style="font-size: 20px;"><?php echo wc_price($net_profit, ['currency' => $order->get_currency()]); ?></strong>
                                            <br><span style="font-size: 14px;">(<?php echo number_format($net_margin, 2); ?>%)</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php
    }
    
    public function save_order_profit_meta($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $calc = padelprofi_get_order_calculations($order);
        
        $order->update_meta_data('_padelprofi_total_cost', $calc['total_cost']);
        $order->update_meta_data('_padelprofi_gross_profit', $calc['gross_profit']);
        $order->update_meta_data('_padelprofi_gross_margin_percent', $calc['gross_margin_percent']);
        $order->update_meta_data('_padelprofi_net_profit', $calc['net_profit']);
        $order->update_meta_data('_padelprofi_net_margin_percent', $calc['net_margin_percent']);
        $order->update_meta_data('_padelprofi_payment_fees', $calc['payment_fees']);
        
        $order->save();
    }
    
    // ==================== SISTEMA DE EXPORTACIÓN ====================
    
    public function add_export_menu() {
        add_submenu_page(
            'woocommerce',
            'Exportar Pedidos',
            'Exportar Pedidos',
            'manage_woocommerce',
            'padelprofi-export',
            array($this, 'render_export_page')
        );
    }
    
    public function render_export_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('No tienes permisos suficientes');
        }
        ?>
        <div class="wrap">
            <h1>Exportar Pedidos - PadelProfi</h1>
            
            <div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px; background: white;">
                <h2>Exportar a CSV</h2>
                <p>Genera un archivo CSV con todos los datos de pedidos y productos.</p>
                
                <form method="get" id="padelprofi-export-form">
                    <input type="hidden" name="action" value="padelprofi_export_csv" />
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('padelprofi_export_csv'); ?>" />
                    
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="date_from">Fecha Desde</label>
                            </th>
                            <td>
                                <input type="date" id="date_from" name="date_from" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="date_to">Fecha Hasta</label>
                            </th>
                            <td>
                                <input type="date" id="date_to" name="date_to" class="regular-text" />
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="export-csv-btn" class="button button-primary button-large">
                            Descargar CSV
                        </button>
                        <span id="export-status" style="margin-left: 15px; display: none;"></span>
                    </p>
                </form>
            </div>
            
            
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#export-csv-btn').on('click', function(e) {
                e.preventDefault();
                
                var btn = $(this);
                var status = $('#export-status');
                var dateFrom = $('#date_from').val();
                var dateTo = $('#date_to').val();
                
                var url = ajaxurl + '?action=padelprofi_export_csv';
                url += '&nonce=' + encodeURIComponent($('input[name="nonce"]').val());
                
                if (dateFrom) {
                    url += '&date_from=' + encodeURIComponent(dateFrom);
                }
                if (dateTo) {
                    url += '&date_to=' + encodeURIComponent(dateTo);
                }
                
                btn.prop('disabled', true);
                status.show().html('<span style="color: #0073aa;">⏳ Generando archivo...</span>');
                
                window.location.href = url;
                
                setTimeout(function() {
                    btn.prop('disabled', false);
                    status.html('<span style="color: #46b450;">✓ Descarga iniciada</span>');
                    setTimeout(function() {
                        status.fadeOut();
                    }, 3000);
                }, 5000);
            });
        });
        </script>
        <?php
    }
    
    public function ajax_export_csv() {
        // Verificar nonce y permisos
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'padelprofi_export_csv')) {
            wp_die('Error de seguridad');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Sin permisos');
        }
        
        try {
            // Configuración de límites
            @ini_set('memory_limit', '2048M');
            @ini_set('max_execution_time', '0');
            @set_time_limit(0);
            ignore_user_abort(true);
            
            // Limpiar buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Preparar filtros de fecha
            $date_args = array(
                'type' => 'shop_order', // CRÍTICO: Solo pedidos, no reembolsos
            );
            
            if (!empty($_GET['date_from'])) {
                $date_args['date_created'] = '>=' . sanitize_text_field($_GET['date_from']);
            }
            if (!empty($_GET['date_to'])) {
                $date_to = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
                if (isset($date_args['date_created'])) {
                    $date_args['date_created'] .= '...' . $date_to;
                } else {
                    $date_args['date_created'] = '<=' . $date_to;
                }
            }
            
            // Contar pedidos
            $count_args = array_merge($date_args, array(
                'limit' => 1,
                'return' => 'ids',
                'paginate' => true
            ));
            
            $count_result = wc_get_orders($count_args);
            $total_orders = $count_result->total;
            
            if ($total_orders == 0) {
                wp_die('No hay pedidos para exportar');
            }
            
            // Calcular máximo de productos
            $max_products = $this->get_max_products_count($date_args);
            
            // Headers HTTP
            $filename = 'pedidos_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Abrir output
            $output = fopen('php://output', 'w');
            
            // BOM UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers CSV
            $headers = $this->build_csv_headers($max_products);
            fputcsv($output, $headers, ';');
            
            // Procesar por lotes
            $batch_size = 50;
            $page = 1;
            $processed = 0;
            
            while ($processed < $total_orders) {
                $batch_args = array_merge($date_args, array(
                    'limit' => $batch_size,
                    'page' => $page,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'return' => 'ids'
                ));
                
                $order_ids = wc_get_orders($batch_args);
                
                if (empty($order_ids)) {
                    break;
                }
                
                foreach ($order_ids as $order_id) {
                    try {
                        $row = $this->build_csv_row($order_id, $max_products);
                        if ($row) {
                            fputcsv($output, $row, ';');
                            $processed++;
                        }
                    } catch (Exception $e) {
                        defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log('Error en pedido ' . $order_id . ': ' . $e->getMessage());
                        continue;
                    }
                    
                    unset($row);
                }
                
                unset($order_ids);
                
                flush();
                wp_cache_flush();
                
                $page++;
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log('PadelProfi Export Error: ' . $e->getMessage());
            wp_die('Error en exportación: ' . $e->getMessage());
        }
    }
    
    private function get_max_products_count($date_args) {
        $sample_args = array_merge($date_args, array(
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids'
        ));
        
        try {
            $sample_ids = wc_get_orders($sample_args);
        } catch (Exception $e) {
            return 10;
        }
        
        $max = 1;
        
        foreach ($sample_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order && $order->get_type() === 'shop_order') {
                $count = count($order->get_items());
                if ($count > $max) {
                    $max = $count;
                }
                unset($order);
            }
        }
        
        unset($sample_ids);
        return min($max, 15);
    }
    
    private function build_csv_headers($max_products) {
        $headers = array(
            'Número Pedido', 'ID', 'Fecha', 'Estado', 'Moneda',
            'Cliente', 'Email', 'Teléfono',
            'Ciudad', 'CP', 'País',
            'Pago', 'Envío',
            'Subtotal', 'Desc', 'Envío', 'IVA', 'Total',
            'Coste', 'Bruto', 'B%', 'Neto', 'N%'
        );
        
        for ($i = 1; $i <= $max_products; $i++) {
            $headers[] = "P{$i}";
            $headers[] = "P{$i}_SKU";
            $headers[] = "P{$i}_Cant";
            $headers[] = "P{$i}_€";
            $headers[] = "P{$i}_Total";
            $headers[] = "P{$i}_Coste";
            $headers[] = "P{$i}_Benef";
        }
        
        return $headers;
    }
    
    private function build_csv_row($order_id, $max_products) {
        $order = wc_get_order($order_id);
        
        // CRÍTICO: Verificar que es un pedido real, no un reembolso
        if (!$order || $order->get_type() !== 'shop_order') {
            return null;
        }
        
        $calc = padelprofi_get_order_calculations($order);
        
        // Método de envío
        $shipping_methods = $order->get_shipping_methods();
        $shipping_name = '';
        if (!empty($shipping_methods)) {
            $method = reset($shipping_methods);
            $shipping_name = $method->get_method_title();
        }
        
        // Datos básicos - USAR get_id() en lugar de get_order_number()
        $row = array(
            $order->get_id(), // Usar ID en lugar de get_order_number()
            $order->get_id(),
            $order->get_date_created() ? $order->get_date_created()->format('Y-m-d H:i') : '',
            wc_get_order_status_name($order->get_status()),
            $order->get_currency(),
            trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            $order->get_billing_email(),
            $order->get_billing_phone(),
            $order->get_billing_city(),
            $order->get_billing_postcode(),
            $order->get_billing_country(),
            $order->get_payment_method_title(),
            $shipping_name,
            number_format($order->get_subtotal(), 2, ',', ''),
            number_format($order->get_total_discount(), 2, ',', ''),
            number_format($order->get_shipping_total(), 2, ',', ''),
            number_format($order->get_total_tax(), 2, ',', ''),
            number_format($order->get_total(), 2, ',', ''),
            number_format($calc['total_cost'], 2, ',', ''),
            number_format($calc['gross_profit'], 2, ',', ''),
            number_format($calc['gross_margin_percent'], 2, ',', ''),
            number_format($calc['net_profit'], 2, ',', ''),
            number_format($calc['net_margin_percent'], 2, ',', '')
        );
        
        // Productos
        $items = $order->get_items();
        $p = 0;
        
        foreach ($items as $item) {
            if ($p >= $max_products) break;
            
            $product = $item->get_product();
            $qty = $item->get_quantity();
            $cost = padelprofi_get_item_cost($item);
            $total = $item->get_total();
            $benefit = $total - ($cost * $qty);
            
            $row[] = $item->get_name();
            $row[] = $product ? $product->get_sku() : '';
            $row[] = $qty;
            $row[] = number_format($qty > 0 ? $total/$qty : 0, 2, ',', '');
            $row[] = number_format($total, 2, ',', '');
            $row[] = number_format($cost, 2, ',', '');
            $row[] = number_format($benefit, 2, ',', '');
            
            $p++;
        }
        
        // Rellenar vacíos
        while ($p < $max_products) {
            $row[] = ''; $row[] = ''; $row[] = ''; 
            $row[] = ''; $row[] = ''; $row[] = ''; $row[] = '';
            $p++;
        }
        
        // Liberar memoria
        unset($order, $calc, $items, $shipping_methods, $product);
        
        return $row;
    }
}

// Inicializar el plugin
new PadelProfi_Cost_Analysis();