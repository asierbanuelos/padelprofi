<?php
/**
 * Plugin Name:  Padel Schläger Berater Pro
 * Description:  Professioneller Schläger-Berater mit Admin-Panel. Shortcode: [padel_recommender]
 * Version:      1.0.0
 * Author:       Padel Profi Deutschland
 * License:      GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PR_VERSION', '1.0.2' );
define( 'PR_URL',  plugin_dir_url( __FILE__ ) );
define( 'PR_PATH', plugin_dir_path( __FILE__ ) );

/* ─── Activation ────────────────────────────────────── */
register_activation_hook( __FILE__, function () {
    if ( ! get_option('pr_brands') ) {
        update_option( 'pr_brands', [
            [ 'id' => 'babolat',   'name' => 'Babolat' ],
            [ 'id' => 'adidas',    'name' => 'Adidas' ],
            [ 'id' => 'nox',       'name' => 'NOX' ],
            [ 'id' => 'bullpadel', 'name' => 'Bullpadel' ],
            [ 'id' => 'head',      'name' => 'HEAD' ],
            [ 'id' => 'wilson',    'name' => 'Wilson' ],
        ]);
    }
    if ( ! get_option('pr_mappings') ) update_option( 'pr_mappings', [] );
});

/* ─── Helpers ───────────────────────────────────────── */
function pr_get_combos() {
    $out = [];
    foreach (['hombre','mujer','junior'] as $g)
        foreach (['competicion','avanzado','principiante'] as $n)
            foreach (['atacante','control','defensor'] as $e)
                $out[] = "{$g}_{$n}_{$e}";
    return $out;
}
function pr_labels() {
    return [
        'hombre'=>'Herr','mujer'=>'Dame','junior'=>'Junior',
        'competicion'=>'Wettkampf','avanzado'=>'Fortgeschritten','principiante'=>'Anfänger',
        'atacante'=>'Angreifer','control'=>'Kontrolle','defensor'=>'Defensiv',
    ];
}

/* ═══════════════════════════════════════════════════════
   ADMIN MENU
═══════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
    add_menu_page( 'Schläger Berater', 'Schläger Berater', 'manage_options',
        'padel-recommender', 'pr_page_mappings', 'dashicons-star-filled', 58 );
    add_submenu_page( 'padel-recommender', 'Schläger Zuordnungen', 'Zuordnungen',
        'manage_options', 'padel-recommender', 'pr_page_mappings' );
    add_submenu_page( 'padel-recommender', 'Marken verwalten', 'Marken',
        'manage_options', 'padel-recommender-brands', 'pr_page_brands' );
});

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'padel-recommender' ) === false ) return;
    wp_enqueue_style(  'pr-admin', PR_URL . 'assets/admin.css', [], PR_VERSION );
    wp_enqueue_script( 'pr-admin', PR_URL . 'assets/admin.js', ['jquery'], PR_VERSION, true );
    wp_localize_script( 'pr-admin', 'PR', [
        'ajax'     => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('pr_nonce'),
        'brands'   => get_option('pr_brands', []),
        'mappings' => get_option('pr_mappings', []),
        'combos'   => pr_get_combos(),
        'labels'   => pr_labels(),
    ]);
});

/* ═══════════════════════════════════════════════════════
   PAGE: ZUORDNUNGEN
═══════════════════════════════════════════════════════ */
function pr_page_mappings() {
    $brands  = get_option('pr_brands', []);
    $lbls    = pr_labels();
    $combos  = pr_get_combos();
    $mapping = get_option('pr_mappings', []);

    // Contar slots llenos
    $total  = count($combos) * count($brands);
    $filled = 0;
    foreach ($combos as $c)
        foreach ($brands as $b)
            if ( !empty($mapping[$c][$b['id']]) ) $filled++;
    ?>
    <div class="wrap pr-wrap">

      <div class="pr-topbar">
        <div>
          <h1 class="pr-h1">🏓 Schläger Zuordnungen</h1>
          <p class="pr-subtitle">Wähle ein Spielerprofil und weise jedem Brand einen Schläger zu.</p>
        </div>
        <div class="pr-topbar-right">
          <code class="pr-shortcode">[padel_recommender]</code>
          <a href="<?php echo admin_url('admin.php?page=padel-recommender-brands'); ?>" class="pr-btn-outline">⚙ Marken</a>
        </div>
      </div>

      <div class="pr-layout">

        <!-- SIDEBAR: Profil wählen -->
        <div class="pr-sidebar">
          <div class="pr-card">
            <div class="pr-card-title">📋 Spielerprofil</div>

            <div class="pr-field">
              <label>Spieler</label>
              <div class="pr-seg" id="seg-gender">
                <button class="pr-seg-btn active" data-val="hombre">Herr</button>
                <button class="pr-seg-btn" data-val="mujer">Dame</button>
                <button class="pr-seg-btn" data-val="junior">Junior</button>
              </div>
            </div>

            <div class="pr-field">
              <label>Spielniveau</label>
              <div class="pr-seg" id="seg-nivel">
                <button class="pr-seg-btn active" data-val="competicion">Wettkampf</button>
                <button class="pr-seg-btn active-no" data-val="avanzado">Fortge.</button>
                <button class="pr-seg-btn" data-val="principiante">Anfänger</button>
              </div>
            </div>

            <div class="pr-field">
              <label>Spielstil</label>
              <div class="pr-seg" id="seg-estilo">
                <button class="pr-seg-btn active" data-val="atacante">Angreifer</button>
                <button class="pr-seg-btn" data-val="control">Kontrolle</button>
                <button class="pr-seg-btn" data-val="defensor">Defensiv</button>
              </div>
            </div>

            <div class="pr-combo-label" id="pr-combo-label">
              <span>Herr / Wettkampf / Angreifer</span>
            </div>
          </div>
        </div>

        <!-- MAIN: Brand-Slots -->
        <div class="pr-main">
          <div class="pr-card">
            <div class="pr-card-header">
              <div class="pr-card-title" id="pr-panel-title">Schläger auswählen</div>
              <div class="pr-card-actions">
                <span class="pr-save-msg" id="pr-save-msg"></span>
                <button id="pr-save-btn" class="pr-btn-primary">💾 Speichern</button>
              </div>
            </div>

            <div id="pr-loading" class="pr-loading-state">
              <div class="pr-spinner"></div>
              <p>Produkte werden geladen…</p>
            </div>

            <div id="pr-slots" style="display:none">
              <?php foreach ($brands as $brand): ?>
              <div class="pr-slot" data-brand="<?php echo esc_attr($brand['id']); ?>">
                <div class="pr-slot-brand">
                  <div class="pr-brand-avatar"><?php echo esc_html(strtoupper(substr($brand['name'],0,1))); ?></div>
                  <div>
                    <div class="pr-brand-name"><?php echo esc_html($brand['name']); ?></div>
                    <div class="pr-brand-id"><?php echo esc_html($brand['id']); ?></div>
                  </div>
                </div>
                <div class="pr-slot-select-wrap">
                  <select class="pr-product-select" data-brand="<?php echo esc_attr($brand['id']); ?>">
                    <option value="">— Kein Schläger —</option>
                  </select>
                  <div class="pr-product-preview" id="prev-<?php echo esc_attr($brand['id']); ?>"></div>
                </div>
              </div>
              <?php endforeach; ?>

              <?php if (empty($brands)): ?>
              <div class="pr-empty-brands">
                Keine Marken konfiguriert. <a href="<?php echo admin_url('admin.php?page=padel-recommender-brands'); ?>">Marken hinzufügen →</a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- .pr-layout -->
    </div>
    <?php
}

/* ═══════════════════════════════════════════════════════
   PAGE: MARKEN
═══════════════════════════════════════════════════════ */
function pr_page_brands() {
    $brands = get_option('pr_brands', []);
    ?>
    <div class="wrap pr-wrap">
      <div class="pr-topbar">
        <div>
          <h1 class="pr-h1">⚙ Marken verwalten</h1>
          <p class="pr-subtitle">Füge alle Marken hinzu, für die du Schläger empfehlen möchtest.</p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=padel-recommender'); ?>" class="pr-btn-outline">← Zurück</a>
      </div>

      <div class="pr-brands-layout">

        <div class="pr-card">
          <div class="pr-card-title">Aktive Marken (<?php echo count($brands); ?>)</div>
          <div id="pr-brands-list">
            <?php if (empty($brands)): ?>
              <p class="pr-empty-msg">Noch keine Marken vorhanden.</p>
            <?php else: ?>
              <?php foreach ($brands as $b): ?>
              <div class="pr-brand-row" id="brow-<?php echo esc_attr($b['id']); ?>">
                <div class="pr-brand-avatar"><?php echo esc_html(strtoupper(substr($b['name'],0,1))); ?></div>
                <div class="pr-brand-row-info">
                  <strong><?php echo esc_html($b['name']); ?></strong>
                  <span>ID: <?php echo esc_html($b['id']); ?></span>
                </div>
                <button class="pr-btn-del" onclick="prDelBrand('<?php echo esc_js($b['id']); ?>','<?php echo esc_js($b['name']); ?>')">✕</button>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="pr-card pr-add-card">
          <div class="pr-card-title">Neue Marke hinzufügen</div>
          <div class="pr-field">
            <label>Markenname <span class="req">*</span></label>
            <input type="text" id="new-name" class="pr-input" placeholder="z.B. Wilson">
          </div>
          <div class="pr-field">
            <label>Marken-ID <span class="req">*</span></label>
            <input type="text" id="new-id" class="pr-input" placeholder="z.B. wilson">
            <p class="pr-field-hint">Nur Kleinbuchstaben, keine Leerzeichen. Wird automatisch befüllt.</p>
          </div>
          <button id="btn-add-brand" class="pr-btn-primary" style="width:100%">+ Marke hinzufügen</button>
          <div id="brand-msg" style="display:none;margin-top:12px;padding:10px;border-radius:6px;font-size:13px"></div>
        </div>

      </div>
    </div>
    <script>
    (function($){
      $('#new-name').on('input',function(){
        $('#new-id').val($(this).val().toLowerCase().replace(/[äöü]/g,function(m){return{ä:'ae',ö:'oe',ü:'ue'}[m]||m;}).replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''));
      });
      $('#btn-add-brand').on('click',function(){
        var name=$('#new-name').val().trim(), id=$('#new-id').val().trim();
        if(!name||!id){showMsg('Bitte Name und ID eingeben.','error');return;}
        $.post('<?php echo admin_url('admin-ajax.php'); ?>',{action:'pr_add_brand',nonce:'<?php echo wp_create_nonce("pr_nonce"); ?>',brand_name:name,brand_id:id},function(r){
          if(r.success) location.reload();
          else showMsg('Fehler: '+r.data,'error');
        });
      });
      function showMsg(t,type){$('#brand-msg').show().text(t).css({background:type==='error'?'#fef2f2':'#f0fdf4',color:type==='error'?'#dc2626':'#16a34a'});}
    })(jQuery);
    function prDelBrand(id,name){
      if(!confirm('Marke "'+name+'" wirklich entfernen?\n\nAlle Zuordnungen dieser Marke gehen verloren.')) return;
      jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>',{action:'pr_delete_brand',nonce:'<?php echo wp_create_nonce("pr_nonce"); ?>',brand_id:id},function(r){
        if(r.success) location.reload();
        else alert('Fehler: '+r.data);
      });
    }
    </script>
    <?php
}

/* ═══════════════════════════════════════════════════════
   AJAX — Admin: Produkte der Kategorie laden
═══════════════════════════════════════════════════════ */
add_action( 'wp_ajax_pr_get_products', function () {
    check_ajax_referer('pr_nonce','nonce');
    if (!current_user_can('manage_options')) wp_die();

    $cat = sanitize_text_field($_POST['category'] ?? 'padelschlaeger');

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [[
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $cat,
        ]],
    ];

    $q = new WP_Query($args);

    // Fallback: si la categoría no tiene productos, traer todos
    if (!$q->have_posts()) {
        unset($args['tax_query']);
        $q = new WP_Query($args);
    }

    $out = [];
    while ($q->have_posts()) {
        $q->the_post();
        $p = wc_get_product(get_the_ID());
        if (!$p) continue;

        $img_id = $p->get_image_id();
        $thumb  = $img_id ? wp_get_attachment_image_url($img_id, [60,60]) : '';

        $reg  = $p->get_regular_price();
        $sale = $p->get_sale_price();
        $price_str = $sale ? number_format((float)$sale,2,',','.').' €' : ($reg ? number_format((float)$reg,2,',','.').' €' : '');

        $out[] = [
            'id'    => $p->get_id(),
            'name'  => $p->get_name(),
            'price' => $price_str,
            'thumb' => $thumb,
            'link'  => get_permalink($p->get_id()),
            'sku'   => $p->get_sku(),
        ];
    }
    wp_reset_postdata();
    wp_send_json_success($out);
});

/* ─── Save combo ─── */
add_action( 'wp_ajax_pr_save_combo', function () {
    check_ajax_referer('pr_nonce','nonce');
    if (!current_user_can('manage_options')) wp_die();

    $combo     = sanitize_text_field($_POST['combo'] ?? '');
    $brand_map = $_POST['brand_map'] ?? [];
    if (!$combo) wp_send_json_error('Kein Profil');

    $maps = get_option('pr_mappings',[]);
    $maps[$combo] = [];
    foreach ($brand_map as $bid => $pid) {
        $bid = sanitize_key($bid);
        $pid = intval($pid);
        if ($pid) $maps[$combo][$bid] = $pid;
    }
    if (empty($maps[$combo])) unset($maps[$combo]);
    update_option('pr_mappings', $maps);

    // recalc stats
    $brands  = get_option('pr_brands',[]);
    $combos  = pr_get_combos();
    $filled  = 0;
    foreach ($combos as $c)
        foreach ($brands as $b)
            if (!empty($maps[$c][$b['id']])) $filled++;

    wp_send_json_success(['filled'=>$filled,'total'=>count($combos)*count($brands)]);
});

/* ─── Brand CRUD ─── */
add_action('wp_ajax_pr_add_brand', function(){
    check_ajax_referer('pr_nonce','nonce');
    if(!current_user_can('manage_options')) wp_die();
    $name=sanitize_text_field($_POST['brand_name']??'');
    $id=sanitize_key($_POST['brand_id']??'');
    if(!$name||!$id) wp_send_json_error('Name und ID erforderlich');
    $brands=get_option('pr_brands',[]);
    foreach($brands as $b) if($b['id']===$id) wp_send_json_error('ID existiert bereits');
    $brands[]=['id'=>$id,'name'=>$name];
    update_option('pr_brands',$brands);
    wp_send_json_success();
});
add_action('wp_ajax_pr_delete_brand', function(){
    check_ajax_referer('pr_nonce','nonce');
    if(!current_user_can('manage_options')) wp_die();
    $id=sanitize_key($_POST['brand_id']??'');
    $brands=get_option('pr_brands',[]);
    update_option('pr_brands',array_values(array_filter($brands,fn($b)=>$b['id']!==$id)));
    wp_send_json_success();
});

/* ═══════════════════════════════════════════════════════
   AJAX — Frontend: Empfehlungen
═══════════════════════════════════════════════════════ */
add_action('wp_ajax_pr_recommend',        'pr_ajax_recommend');
add_action('wp_ajax_nopriv_pr_recommend', 'pr_ajax_recommend');
function pr_ajax_recommend() {
    check_ajax_referer('pr_front_nonce','nonce');

    $g = sanitize_text_field($_POST['gender']??'');
    $n = sanitize_text_field($_POST['nivel'] ??'');
    $e = sanitize_text_field($_POST['estilo']??'');
    if(!$g||!$n||!$e) wp_send_json_error('Fehlende Parameter');

    $combo   = "{$g}_{$n}_{$e}";
    $maps    = get_option('pr_mappings',[]);
    $brands  = get_option('pr_brands',[]);
    $cmap    = $maps[$combo] ?? [];
    $results = [];

    foreach ($brands as $brand) {
        $bid = $brand['id'];
        $pid = $cmap[$bid] ?? 0;
        if (!$pid) continue;

        $p = wc_get_product($pid);
        if (!$p) continue;

        $img_id = $p->get_image_id();
        $img    = $img_id ? wp_get_attachment_image_url($img_id,'medium') : '';

        $reg  = $p->get_regular_price();
        $sale = $p->get_sale_price();
        $disc = ($reg && $sale && $sale < $reg)
            ? '-'.round((1-$sale/$reg)*100).'%' : '';

        $price_sale = $sale ? number_format((float)$sale,2,',','.').' €' : '';
        $price_reg  = $reg  ? number_format((float)$reg, 2,',','.').' €' : '';
        $price      = $price_sale ?: $price_reg;

        $results[] = [
            'brand_id'   => $bid,
            'brand_name' => $brand['name'],
            'product_id' => $pid,
            'name'       => $p->get_name(),
            'price'      => $price,
            'price_reg'  => $price_reg,
            'discount'   => $disc,
            'img'        => $img,
            'link'       => get_permalink($pid),
        ];
    }

    wp_send_json_success($results);
}

/* ═══════════════════════════════════════════════════════
   FRONTEND ASSETS + SHORTCODE
═══════════════════════════════════════════════════════ */
add_action('wp_enqueue_scripts', function(){
    global $post;
    if(!is_a($post,'WP_Post')||!has_shortcode($post->post_content,'padel_recommender')) return;
    wp_enqueue_style( 'pr-front', PR_URL.'assets/recommender.css', [], PR_VERSION);
    wp_enqueue_script('pr-front', PR_URL.'assets/recommender.js',  [], PR_VERSION, true);
    wp_localize_script('pr-front','PRFront',[
        'ajax'   => admin_url('admin-ajax.php'),
        'nonce'  => wp_create_nonce('pr_front_nonce'),
        'brands' => get_option('pr_brands',[]),
    ]);
});

add_shortcode('padel_recommender', function(){
    ob_start(); ?>
    <div id="padel-recommender" aria-label="Padelschläger Empfehlung">
        <noscript><p>Bitte JavaScript aktivieren.</p></noscript>
    </div>
    <?php return ob_get_clean();
});
