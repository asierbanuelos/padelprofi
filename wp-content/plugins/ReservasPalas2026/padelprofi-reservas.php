<?php
/**
 * Plugin Name: PadelProfi – Vorbestellung mit Anzahlung
 * Description: Añade un botón "Vorbestellen mit Anzahlung" a los productos de WooCommerce. Cobra un anticipo según el precio y genera un cupón (uso único) por el mismo importe para descontarlo en la compra final. El cupón se activa a partir de la fecha indicada en ajustes.
 * Version: 1.1.7
 * Author: Tu Equipo
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class PadelProfi_Reservas {
  const OPT_KEY = 'ppd_reservas_settings';
  const HIDDEN_PRODUCT_SKU = 'PPD-VORBESTELLUNG';

  public function __construct(){

    /* Presentación en carrito/checkout/pedidos */
    add_filter('woocommerce_cart_item_name',      [$this, 'pretty_reservation_name_cart'], 10, 3);
    add_filter('woocommerce_cart_item_thumbnail', [$this, 'pretty_reservation_thumb'],     10, 3);
    add_filter('woocommerce_get_item_data',       [$this, 'pretty_reservation_meta'],      10, 2);
    add_filter('woocommerce_order_item_name',     [$this, 'pretty_reservation_name_order'],10, 3);

    /* Copiar metadatos del carrito al ítem del pedido */
    add_action('woocommerce_checkout_create_order_line_item', [$this, 'copy_reservation_meta_to_order_item'], 10, 4);

    /* Generar cupón tras pago / cambio de estado */
    add_action('woocommerce_payment_complete',        [$this, 'maybe_generate_coupon_on_payment']);
    add_action('woocommerce_order_status_processing', [$this, 'maybe_generate_coupon_on_payment']);
    add_action('woocommerce_order_status_completed',  [$this, 'maybe_generate_coupon_on_payment']);

    /* Ajustes (admin) */
    add_action('admin_init', [$this, 'register_settings']);
    add_action('admin_menu', [$this, 'add_settings_page']);

    /* Front: assets + botón propio */
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    add_action('woocommerce_after_add_to_cart_form', [$this, 'render_reserve_button'], 10);

    /* Ocultar botón nativo cuando aplique Vorbestellung */
    add_action('wp', [$this, 'maybe_hide_default_add_to_cart'], 20);

    /* Clase en <body> para fallback CSS */
    add_filter('body_class', [$this, 'add_reservable_body_class']);

    /* AJAX */
    add_action('wp_ajax_ppd_add_reservation',        [$this, 'ajax_add_reservation']);
    add_action('wp_ajax_nopriv_ppd_add_reservation', [$this, 'ajax_add_reservation']);

    /* Producto oculto de Vorbestellung */
    add_action('init', [$this, 'ensure_hidden_reservation_product']);

    /* Precio dinámico del ítem-Vorbestellung */
    add_filter('woocommerce_before_calculate_totals', [$this, 'set_dynamic_cart_price'], 20, 1);

    /* Validación del cupón (SKU + fecha activo desde) */
    add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_with_reserved_sku_and_date'], 10, 2);

    /* Email */
    add_action('ppd_reserva_email_send', [$this, 'send_reservation_email'], 10, 3);

    /* Shortcodes (mantenemos los mismos nombres por compatibilidad) */
    add_shortcode('ppd_reserve_badge',   [$this, 'shortcode_reserve_badge']);
    add_action('init', function(){ add_shortcode('ppd_reserve_button', [$this, 'shortcode_reserve_button']); });

    /* Convertir el sticky footer button del tema en botón de Vorbestellung */
    add_action('wp_footer', [$this,'hook_sticky_footer_button'], 99);
  }

  /* === SHORTCODES === */
  public function shortcode_reserve_badge($atts = []) {
    $atts = shortcode_atts(['product_id' => 0], $atts, 'ppd_reserve_badge');
    $product = null;

    if (intval($atts['product_id'])) {
      $product = wc_get_product(intval($atts['product_id']));
    } else {
      if (!is_product()) return '';
      global $product;
    }
    if (!$product) return '';

    $mensaje = 'Du zahlst jetzt die Anzahlung und erhältst einen Code in gleicher Höhe, den du beim Kauf dieses Schlägers (Vorbestellung) einlösen kannst..';
    ob_start(); ?>
    <span class="ppd-badge-shortcode"><?php echo esc_html($mensaje); ?></span>
    <?php return ob_get_clean();
  }

  public function shortcode_reserve_button($atts = []){
    ob_start();
    $this->render_reserve_button();
    return ob_get_clean();
  }

  /* === AJUSTES === */
  public static function defaults(){
    return [
      'threshold_high'       => 200,
      'threshold_low'        => 100,
      'deposit_high'         => 50,
      'deposit_mid'          => 40,
      'deposit_low'          => 30,
      'btn_text'             => 'Vorbestellen mit Anzahlung',
      'show_when_outofstock' => 'no',
      'allowed_categories'   => 'raquettes-de-padel-adidas-2026',
      /* NUEVO: fecha global de activación del cupón (YYYY-MM-DD). Si vacío = hoy */
      'valid_from_date'      => '',
    ];
  }

  public function register_settings(){
    register_setting('ppd_reservas_group', self::OPT_KEY, [
      'type' => 'array',
      'sanitize_callback' => function($v){
        $d = self::defaults();
        $v = is_array($v) ? array_merge($d, $v) : $d;
        foreach (['threshold_high','threshold_low','deposit_high','deposit_mid','deposit_low'] as $k){
          $v[$k] = floatval($v[$k]);
        }
        $v['btn_text'] = sanitize_text_field($v['btn_text']);
        $v['show_when_outofstock'] = ($v['show_when_outofstock'] === 'yes') ? 'yes' : 'no';

        $raw = isset($v['allowed_categories']) ? (string)$v['allowed_categories'] : '';
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $parts = array_map(function($s){ return sanitize_title($s); }, $parts);
        $v['allowed_categories'] = implode(',', array_unique($parts));

        $v['valid_from_date'] = sanitize_text_field( isset($v['valid_from_date']) ? $v['valid_from_date'] : '' );
        return $v;
      },
      'default' => self::defaults()
    ]);
  }

  public function add_settings_page(){
    add_options_page('Vorbestellung mit Anzahlung', 'Vorbestellung mit Anzahlung', 'manage_woocommerce', 'ppd-reservas', [$this, 'render_settings_page']);
  }

  public function render_settings_page(){
    $opt = get_option(self::OPT_KEY, self::defaults());
    ?>
    <div class="wrap">
      <h1>Vorbestellung mit Anzahlung – Ajustes</h1>
      <form method="post" action="options.php">
        <?php settings_fields('ppd_reservas_group'); ?>
        <table class="form-table" role="presentation">
          <tr><th>Umbral alto (€)</th><td><input name="<?php echo self::OPT_KEY; ?>[threshold_high]" type="number" step="0.01" value="<?php echo esc_attr($opt['threshold_high']); ?>" /></td></tr>
          <tr><th>Umbral bajo (€)</th><td><input name="<?php echo self::OPT_KEY; ?>[threshold_low]" type="number" step="0.01" value="<?php echo esc_attr($opt['threshold_low']); ?>" /></td></tr>
          <tr><th>Anzahlung > umbral alto (€)</th><td><input name="<?php echo self::OPT_KEY; ?>[deposit_high]" type="number" step="0.01" value="<?php echo esc_attr($opt['deposit_high']); ?>" /></td></tr>
          <tr><th>Anzahlung entre umbrales (€)</th><td><input name="<?php echo self::OPT_KEY; ?>[deposit_mid]" type="number" step="0.01" value="<?php echo esc_attr($opt['deposit_mid']); ?>" /></td></tr>
          <tr><th>Anzahlung < umbral bajo (€)</th><td><input name="<?php echo self::OPT_KEY; ?>[deposit_low]" type="number" step="0.01" value="<?php echo esc_attr($opt['deposit_low']); ?>" /></td></tr>
          <tr><th>Texto del botón</th><td><input name="<?php echo self::OPT_KEY; ?>[btn_text]" type="text" value="<?php echo esc_attr($opt['btn_text']); ?>" class="regular-text" /></td></tr>
          <tr><th>Mostrar si no hay stock</th><td><label><input name="<?php echo self::OPT_KEY; ?>[show_when_outofstock]" type="checkbox" value="yes" <?php checked($opt['show_when_outofstock'], 'yes'); ?> /> Sí</label></td></tr>
          <tr><th>Categorías con Vorbestellung (slugs)</th><td>
            <input name="<?php echo self::OPT_KEY; ?>[allowed_categories]" type="text" value="<?php echo esc_attr($opt['allowed_categories']); ?>" class="regular-text" />
            <p class="description">Slugs separados por coma.</p>
          </td></tr>
          <tr><th>Fecha global de activación del cupón</th><td>
            <input name="<?php echo self::OPT_KEY; ?>[valid_from_date]" type="date" value="<?php echo esc_attr($opt['valid_from_date']); ?>" />
          </td></tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  /* === LÓGICA COMÚN === */
  public function can_show_reserve_button($product){
    if (!$product || !is_a($product, 'WC_Product')) return false;

    $opt = get_option(self::OPT_KEY, self::defaults());
    if (!$product->is_purchasable()) return false;
    if (!$product->is_in_stock() && $opt['show_when_outofstock'] !== 'yes') return false;

    $allowed = $this->get_allowed_categories();
    if (!empty($allowed)) {
      $in_allowed = false;
      foreach ($allowed as $slug) {
        if (has_term($slug, 'product_cat', $product->get_id())) { $in_allowed = true; break; }
      }
      if (!$in_allowed) return false;
    }

    $price = floatval(wc_get_price_to_display($product));
    if ($price <= 0) return false;

    return true;
  }

  public static function calc_deposit($price){
    $o = get_option(self::OPT_KEY, self::defaults());
    $th = floatval($o['threshold_high']);
    $tl = floatval($o['threshold_low']);
    if ($price > $th) return floatval($o['deposit_high']);
    if ($price >= $tl) return floatval($o['deposit_mid']);
    return floatval($o['deposit_low']);
  }

  public function get_allowed_categories(){
    $o = get_option(self::OPT_KEY, self::defaults());
    $raw = isset($o['allowed_categories']) ? (string)$o['allowed_categories'] : '';
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    return array_unique(array_map('sanitize_title', $parts));
  }

  /* === ASSETS === */
  public function enqueue_assets(){
    $content = get_post() ? (get_post()->post_content ?? '') : '';
    if (is_product() || (is_singular() && (has_shortcode($content, 'ppd_reserve_button') || has_shortcode($content, 'ppd_reserve_badge')))) {
      wp_enqueue_script('ppd-reservas', plugin_dir_url(__FILE__) . 'ppd-reservas.js', ['jquery'], '1.1.7', true);
      wp_localize_script('ppd-reservas', 'PPD_RES', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ppd_res_nonce')
      ]);
      wp_enqueue_style('ppd-reservas-css', plugin_dir_url(__FILE__) . 'ppd-reservas.css', [], '1.1.7');
    }
  }

  /* === OCULTAR BOTÓN NATIVO CUANDO APLICA VORBESTELLUNG === */
  public function maybe_hide_default_add_to_cart(){
    if (!is_product()) return;
    global $product;
    if (!$this->can_show_reserve_button($product)) return;

    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_simple_add_to_cart',   'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_grouped_add_to_cart',  'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_variable_add_to_cart', 'woocommerce_template_single_add_to_cart', 30);
  }

  public function add_reservable_body_class($classes){
    if (is_product()) {
      global $product;
      if ($this->can_show_reserve_button($product)) {
        $classes[] = 'ppd-reservable';
      }
    }
    return $classes;
  }

/* === BOTÓN PROPIO EN LA FICHA === */
public function render_reserve_button(){
  global $product; 
  if (!$product) return;
  if (!$this->can_show_reserve_button($product)) return;

  $opt = get_option(self::OPT_KEY, self::defaults());
  $price   = floatval(wc_get_price_to_display($product));
  $deposit = self::calc_deposit($price);
  $sku     = $product->get_sku() ?: $product->get_id();

  echo '<div class="ppd-reserve-wrapper">';
  
  // Botón con estructura para diseño minimal elegante
  echo '<button type="button" class="button single_add_to_cart_button ppd-reserve-btn"
      data-sku="'.esc_attr($sku).'"
      data-pid="'.esc_attr($product->get_id()).'"
      data-deposit="'.esc_attr($deposit).'"
      aria-label="'.esc_attr($opt['btn_text'].' '.wp_strip_all_tags(wc_price($deposit))).'">';
  
  // Contenido izquierdo (icono + texto)
  echo '<div class="ppd-left-content">';
  echo '<span>'.esc_html($opt['btn_text']).'</span>';
  echo '</div>';
  
  // Precio en el lado derecho
  echo '<span class="ppd-price">'.wc_price($deposit).'</span>';
  
  echo '</button>';
  

  
  echo '</div>';
}

  /* === PRODUCTO OCULTO === */
  public function ensure_hidden_reservation_product(){
    if (!class_exists('WC_Product_Simple')) return;
    $product_id = wc_get_product_id_by_sku(self::HIDDEN_PRODUCT_SKU);
    if ($product_id) return;

    $product = new WC_Product_Simple();
    $product->set_name('Vorbestellung für Produkt (PPD)');
    $product->set_status('publish');
    $product->set_catalog_visibility('hidden');
    $product->set_sku(self::HIDDEN_PRODUCT_SKU);
    $product->set_price(1);
    $product->set_regular_price(1);
    $product->set_virtual(true);
    $product->set_sold_individually(true);
    $product->save();
  }

  /* === AJAX === */
  public function ajax_add_reservation(){
    check_ajax_referer('ppd_res_nonce', 'nonce');

    $pid = intval($_POST['pid'] ?? 0);
    $sku = sanitize_text_field($_POST['sku'] ?? '');
    $deposit = floatval($_POST['deposit'] ?? 0);

    if (!$pid || !$deposit) {
      wp_send_json_error(['message' => 'Datos incompletos.']);
      return;
    }

    $hidden_id = wc_get_product_id_by_sku(self::HIDDEN_PRODUCT_SKU);
    if (!$hidden_id) {
      wp_send_json_error(['message' => 'Vorbestellungsprodukt nicht verfügbar.']);
      return;
    }

    WC()->cart->empty_cart();

    $cart_item_data = [
      '_ppd_is_reservation' => true,
      '_ppd_reserved_pid'   => $pid,
      '_ppd_reserved_sku'   => $sku,
      '_ppd_deposit_amount' => $deposit,
      '_ppd_price_reference'=> floatval(get_post_meta($pid, '_price', true)),
      'unique_key'          => md5(uniqid(rand(), true)),
    ];

    $added = WC()->cart->add_to_cart($hidden_id, 1, 0, [], $cart_item_data);

    if ($added){
      wp_send_json_success(['redirect' => wc_get_checkout_url()]);
    } else {
      wp_send_json_error(['message' => 'Vorbestellung konnte nicht hinzugefügt werden.']);
    }
  }

  /* === PRECIO DINÁMICO === */
  public function set_dynamic_cart_price($cart){
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (empty($cart->get_cart())) return;

    foreach ($cart->get_cart() as $cart_item){
      if (!empty($cart_item['_ppd_is_reservation'])){
        $deposit = floatval($cart_item['_ppd_deposit_amount'] ?? 0);
        if ($deposit > 0){
          $cart_item['data']->set_price($deposit);
          $name = 'Vorbestellung ' . (!empty($cart_item['_ppd_reserved_sku']) ? '['.$cart_item['_ppd_reserved_sku'].']' : '') . ' (Anzahlung)';
          $cart_item['data']->set_name($name);
        }
      }
    }
  }

  /* === COPIAR METAS DEL CARRITO AL PEDIDO === */
  public function copy_reservation_meta_to_order_item( $item, $cart_item_key, $values, $order ) {
    if ( empty( $values['_ppd_is_reservation'] ) ) return;
    $item->add_meta_data('_ppd_is_reservation', true, true);
    if (!empty($values['_ppd_reserved_pid'])) {
      $item->add_meta_data('_ppd_reserved_pid', intval($values['_ppd_reserved_pid']), true);
    }
    if (!empty($values['_ppd_reserved_sku'])) {
      $item->add_meta_data('_ppd_reserved_sku', sanitize_text_field($values['_ppd_reserved_sku']), true);
    }
    if (isset($values['_ppd_deposit_amount'])) {
      $item->add_meta_data('_ppd_deposit_amount', floatval($values['_ppd_deposit_amount']), true);
    }
  }

  /* === CUPÓN + EMAIL === */
  public function maybe_generate_coupon_on_payment($order_id){
    $order = wc_get_order($order_id); 
    if (!$order) return;

    if ($order->get_meta('_ppd_coupon_generated') === 'yes') {
      return;
    }

    $is_reservation_order = false;
    $reserved_sku   = '';
    $reserved_pid   = 0;
    $deposit_amount = 0;

    foreach ($order->get_items() as $item){
      if ($item->get_meta('_ppd_is_reservation')){
        $is_reservation_order = true;
        $reserved_sku = $item->get_meta('_ppd_reserved_sku');
        $reserved_pid = intval($item->get_meta('_ppd_reserved_pid'));
        $deposit_amount += (float)$item->get_total();
      }
    }

    if (!$is_reservation_order || !$reserved_sku || $deposit_amount <= 0) return;

    /* Fecha “activo desde” obtenida de ajustes */
    $opt = get_option(self::OPT_KEY, self::defaults());
    $vf_raw = trim((string)$opt['valid_from_date']);
    try {
      $tz = wp_timezone();
      $vf = $vf_raw ? new DateTime($vf_raw, $tz) : new DateTime('today', $tz);
    } catch (Exception $e) {
      $vf = new DateTime('today', wp_timezone());
    }

    /* Crear cupón */
    $code = 'VORB-' . strtoupper(wp_generate_password(8, false, false));
    $coupon_post = [
      'post_title'   => $code,
      'post_content' => 'Cupón de Vorbestellung para SKU ' . $reserved_sku,
      'post_status'  => 'publish',
      'post_author'  => get_current_user_id(),
      'post_type'    => 'shop_coupon',
    ];
    $coupon_id = wp_insert_post($coupon_post);

    update_post_meta($coupon_id, 'discount_type', 'fixed_cart');
    update_post_meta($coupon_id, 'coupon_amount', wc_format_decimal($deposit_amount, 2));
    update_post_meta($coupon_id, 'individual_use', 'yes');
    update_post_meta($coupon_id, 'usage_limit', '1'); // uso ÚNICO total
    // SIN CADUCIDAD: no establecemos date_expires
    update_post_meta($coupon_id, 'free_shipping', 'no');
    update_post_meta($coupon_id, 'customer_email', $order->get_billing_email());
    update_post_meta($coupon_id, '_ppd_reserved_sku', $reserved_sku);

    // Guardar “activo desde” en el cupón
    update_post_meta($coupon_id, '_ppd_valid_from', $vf->format('Y-m-d'));

    $order->update_meta_data('_ppd_coupon_generated', 'yes');
    $order->update_meta_data('_ppd_coupon_code', $code);
    $order->save();

    // Email al cliente (indica “utilizable desde”)
    do_action('ppd_reserva_email_send', $order, $code, $vf);

    $order->add_order_note(
      'Generado cupón de Vorbestellung: ' . $code .
      ' por ' . wc_price($deposit_amount) .
      '. Utilizable desde ' . $vf->format('Y-m-d') .
      ' (sin caducidad).'
    );
  }

  public function send_reservation_email($order, $code, $valid_from){
    if ( ! $order instanceof WC_Order ) return;

    $to = $order->get_billing_email();
    if (!$to) return;

    $mailer  = WC()->mailer();
    $subject = 'Ihre Vorbestellung ist bestätigt – Rabattcode';

    ob_start(); ?>
    <div style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#222;">
      <h2 style="margin:0 0 10px;">Danke für deine Vorbestellung!</h2>
      <p style="margin:0 0 8px;">Dein Code zur Anrechnung der Anzahlung beim endgültigen Kauf lautet:</p>
      <p style="margin:0 0 12px;">
        <strong style="font-size:18px;letter-spacing:1px;"><?php echo esc_html($code); ?></strong>
      </p>
      <p style="margin:0 0 12px;">Du kannst ihn ab dem <strong><?php echo esc_html($valid_from->format('d/m/Y')); ?></strong> verwenden.</p>
      <p style="margin:0 0 6px;">So verwendest du den Code:</p>
      <ol style="margin:0; padding-left:18px;">
        <li>Füge den vorbestellten Schläger zum Warenkorb hinzu.</li>
        <li>Gib den Code an der Kasse ein.</li>
        <li>Der Anzahlungsbetrag wird von deinem Gesamtbetrag abgezogen.</li>
      </ol>
      <p style="margin:12px 0 0;opacity:.7;">Dieser Gutschein läuft nicht ab und ist nur einmal verwendbar.</p>
    </div>
    <?php
    $body    = ob_get_clean();
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $mailer->send($to, $subject, $body, $headers);
  }

  /* === VALIDACIÓN CUPÓN (SKU + FECHA “ACTIVO DESDE”) === */
  public function validate_coupon_with_reserved_sku_and_date($valid, $coupon){
    if (!$valid) return $valid;

    $coupon_id   = $coupon->get_id();
    $reserved_sku = get_post_meta($coupon_id, '_ppd_reserved_sku', true);
    if (!$reserved_sku) return $valid;

    // Debe estar en el carrito el SKU de la Vorbestellung
    $has_sku = false;
    foreach (WC()->cart->get_cart() as $cart_item){
      $product = $cart_item['data'];
      if ($product && $product->get_sku() === $reserved_sku){ $has_sku = true; break; }
    }
    if (!$has_sku){
      wc_add_notice(__('Dieser Gutschein ist nur für den vorbestellten Schläger gültig.', 'woocommerce'), 'error');
      return false;
    }

    // Comprobar “activo desde”
    $valid_from_meta = get_post_meta($coupon_id, '_ppd_valid_from', true);
    if ($valid_from_meta) {
      try {
        $tz  = wp_timezone();
        $now = new DateTime('now', $tz);
        $vf  = new DateTime($valid_from_meta, $tz);
        if ($now < $vf) {
          wc_add_notice(
            sprintf(__('Dieser Gutschein ist ab dem %s aktiv.', 'woocommerce'), esc_html($vf->format('d/m/Y'))),
            'error'
          );
          return false;
        }
      } catch (Exception $e) { /* si la fecha es inválida, lo dejamos pasar */ }
    }

    return true;
  }

  /* === PRESENTACIÓN === */
  public function pretty_reservation_name_cart($name, $cart_item, $cart_item_key){
    if (empty($cart_item['_ppd_is_reservation'])) return $name;
    $pid = intval($cart_item['_ppd_reserved_pid'] ?? 0);
    $p   = $pid ? wc_get_product($pid) : null;
    if ($p) {
      $link  = $p->is_visible() ? get_permalink($pid) : '';
      $label = 'Vorbestellung: ' . $p->get_name() . ' (Anzahlung)';
      $name  = $link ? sprintf('<a href="%s">%s</a>', esc_url($link), esc_html($label)) : esc_html($label);
    } else {
      $name = esc_html__('Vorbestellung (Anzahlung)', 'woocommerce');
    }
    return $name;
  }

  public function pretty_reservation_name_order($item_name, $item, $is_visible){
    if (!$item->get_meta('_ppd_is_reservation')) return $item_name;
    $pid = intval($item->get_meta('_ppd_reserved_pid'));
    $p   = $pid ? wc_get_product($pid) : null;
    if ($p) {
      $label = 'Vorbestellung: ' . $p->get_name() . ' (Anzahlung)';
      $item_name = esc_html($label);
    } else {
      $item_name = esc_html__('Vorbestellung (Anzahlung)', 'woocommerce');
    }
    return $item_name;
  }

  public function pretty_reservation_thumb($img_html, $cart_item, $cart_item_key){
    if (empty($cart_item['_ppd_is_reservation'])) return $img_html;
    $pid = intval($cart_item['_ppd_reserved_pid'] ?? 0);
    if ($pid) {
      $p = wc_get_product($pid);
      if ($p) {
        return wp_get_attachment_image(
          $p->get_image_id(),
          'woocommerce_thumbnail',
          false,
          ['alt' => $p->get_name()]
        );
      }
    }
    return $img_html;
  }

  public function pretty_reservation_meta($item_data, $cart_item){
    if (empty($cart_item['_ppd_is_reservation'])) return $item_data;

    $pid   = intval($cart_item['_ppd_reserved_pid'] ?? 0);
    $pname = $pid ? get_the_title($pid) : '';
    $dep   = floatval($cart_item['_ppd_deposit_amount'] ?? 0);

    if ($pname) {
      $item_data[] = [
        'name'    => __('Vorbestelltes Produkt', 'woocommerce'),
        'display' => esc_html($pname),
      ];
    }

    if ($dep > 0) {
      $item_data[] = [
        'name'    => __('Anzahlung', 'woocommerce'),
        'display' => wc_price($dep),
      ];
    }

    return $item_data;
  }

public function hook_sticky_footer_button(){
  if ( ! is_product() ) return;

  global $product;
  if ( ! $this->can_show_reserve_button( $product ) ) return;

  $opt     = get_option(self::OPT_KEY, self::defaults());
  $price   = floatval( wc_get_price_to_display( $product ) );
  $deposit = self::calc_deposit( $price );
  $sku     = $product->get_sku() ?: $product->get_id();
  ?>
  <script>
  (function(){
    if(!document.body.classList.contains('ppd-reservable')) return;

    var btn =
      document.querySelector('.sticky-buy-button .button-buy') ||
      document.querySelector('.sticky-buy-button button#add-to-cart') ||
      document.querySelector('.sticky-buy-button [type="submit"]');

    if(!btn) return;

    // 1) Neutralizar por completo el botón nativo
    btn.setAttribute('type','button');
    btn.removeAttribute('href');
    btn.removeAttribute('name');
    btn.removeAttribute('value');
    btn.removeAttribute('data-product_id');
    btn.removeAttribute('data-product_sku');
    btn.classList.remove('add_to_cart_button','ajax_add_to_cart','single_add_to_cart_button');

    // 2) Marcas/atributos de tu reserva
    btn.classList.add('ppd-reserve-btn');
    btn.setAttribute('data-sku','<?php echo esc_js($sku); ?>');
    btn.setAttribute('data-pid','<?php echo esc_js($product->get_id()); ?>');
    btn.setAttribute('data-deposit','<?php echo esc_js($deposit); ?>');

    // 3) UI del botón
    try {
      btn.innerHTML =
        '<div class="ppd-left-content">' +
          '<i class="fas fa-calendar-check"></i>' +
          '<span><?php echo esc_js($opt['btn_text']); ?></span>' +
        '</div>' +
        '<span class="ppd-price"><?php echo esc_js( wp_strip_all_tags( wc_price($deposit) ) ); ?></span>';
    } catch(e){}

    // 4) Capturar el clic en CAPTURA y bloquear cualquier handler del tema
    var handler = function(e){
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

      // AJAX -> tu endpoint
      if (window.PPD_RES && window.jQuery) {
        jQuery.post(
          PPD_RES.ajaxurl,
          {
            action: 'ppd_add_reservation',
            nonce:  PPD_RES.nonce,
            pid:    btn.getAttribute('data-pid'),
            sku:    btn.getAttribute('data-sku'),
            deposit:btn.getAttribute('data-deposit')
          }
        ).done(function(r){
          if (r && r.success && r.data && r.data.redirect) {
            window.location.href = r.data.redirect;
          } else {
            alert((r && r.data && r.data.message) || 'Vorbestellung konnte nicht hinzugefügt werden.');
          }
        }).fail(function(){
          alert('Fehler beim Senden der Vorbestellung.');
        });
      }
    };

    // Listener en fase de captura para imponernos a la delegación del tema
    btn.addEventListener('click', handler, true);
  })();
  </script>
  <style>
    /* Evita submits accidentales del botón del tema en product pages reservables */
    body.ppd-reservable .sticky-buy-button .button-buy[type="submit"]{ pointer-events:none; }
  </style>
  <?php
}


}

new PadelProfi_Reservas();
