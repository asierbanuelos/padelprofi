<?php
/**
 * Plugin Name: Sitemap Pro
 * Description: Control total del sitemap XML con historial de cambios
 * Version: 2.0
 * Author: Padel Profi Deutschland
 */

if (!defined('ABSPATH')) exit;

class SitemapPro {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activar'));
        add_action('admin_menu', array($this, 'menu'));
        add_action('save_post_product', array($this, 'regenerar'), 10, 3);
        add_action('before_delete_post', array($this, 'regenerar_delete'));
        add_action('wp_ajax_sitemap_action', array($this, 'ajax_handler'));
    }
    
    public function activar() {
        if (!get_option('sitemap_urls')) {
            update_option('sitemap_urls', array(array('loc' => home_url('/'))), false);
        }
        if (!get_option('sitemap_excluded')) update_option('sitemap_excluded', array());
        if (!get_option('sitemap_history')) update_option('sitemap_history', array(), false);
        $this->generar();
    }
    
    public function menu() {
        add_menu_page('Sitemap Pro', 'Sitemap Pro', 'manage_options', 'sitemap-pro', array($this, 'pagina_urls'), 'dashicons-networking', 30);
        add_submenu_page('sitemap-pro', 'URLs', 'URLs', 'manage_options', 'sitemap-pro', array($this, 'pagina_urls'));
        add_submenu_page('sitemap-pro', 'Historial', 'Historial', 'manage_options', 'sitemap-historial', array($this, 'pagina_historial'));
    }
    
    public function pagina_urls() {
        $urls = get_option('sitemap_urls', array());
        $excluded = get_option('sitemap_excluded', array());
        
        $productos = get_posts(array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        $categorias = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
        $ubicaciones = get_posts(array(
            'post_type'              => 'standort',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        
        $todas = array();
        foreach ($urls as $i => $u) {
            $u['id'] = 'custom_'.$i;
            $u['tipo'] = 'personalizada';
            $u['index'] = $i;
            $todas[] = $u;
        }
        foreach ($productos as $p) {
            $id = 'product_'.$p->ID;
            $todas[] = array('id' => $id, 'loc' => get_permalink($p->ID), 'titulo' => $p->post_title, 'lastmod' => get_the_modified_date('Y-m-d', $p->ID), 'tipo' => 'producto', 'excluida' => in_array($id, $excluded));
        }
        foreach ($categorias as $c) {
            $id = 'cat_'.$c->term_id;
            $todas[] = array('id' => $id, 'loc' => get_term_link($c), 'titulo' => $c->name, 'lastmod' => date('Y-m-d'), 'tipo' => 'categoria', 'excluida' => in_array($id, $excluded));
        }
        foreach ($ubicaciones as $u) {
            $id = 'ubicacion_'.$u->ID;
            $todas[] = array('id' => $id, 'loc' => get_permalink($u->ID), 'titulo' => $u->post_title, 'lastmod' => get_the_modified_date('Y-m-d', $u->ID), 'tipo' => 'ubicacion', 'excluida' => in_array($id, $excluded));
        }
        
        $activas = count(array_filter($todas, function($u) { return !isset($u['excluida']) || !$u['excluida']; }));
        ?>
        <div class="wrap" style="max-width:1400px">
            <h1>🗺️ Sitemap Pro</h1>
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin:20px 0">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px">
                    <div style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:20px;border-radius:8px;text-align:center">
                        <div style="font-size:14px;opacity:0.9">Total URLs</div>
                        <div style="font-size:32px;font-weight:bold;margin:10px 0"><?php echo count($todas); ?></div>
                    </div>
                    <div style="background:linear-gradient(135deg,#11998e,#38ef7d);color:#fff;padding:20px;border-radius:8px;text-align:center">
                        <div style="font-size:14px;opacity:0.9">✅ En Sitemap</div>
                        <div style="font-size:32px;font-weight:bold;margin:10px 0"><?php echo $activas; ?></div>
                    </div>
                    <div style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;padding:20px;border-radius:8px;text-align:center">
                        <div style="font-size:14px;opacity:0.9">❌ Excluidas</div>
                        <div style="font-size:32px;font-weight:bold;margin:10px 0"><?php echo count($todas) - $activas; ?></div>
                    </div>
                </div>
                
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
                    <div style="background:#e3f2fd;padding:12px;border-radius:6px;text-align:center">
                        <div style="font-size:12px;color:#1976d2;font-weight:600">PRODUCTOS</div>
                        <div style="font-size:24px;font-weight:bold;color:#1976d2"><?php echo count($productos); ?></div>
                    </div>
                    <div style="background:#f3e5f5;padding:12px;border-radius:6px;text-align:center">
                        <div style="font-size:12px;color:#7b1fa2;font-weight:600">CATEGORÍAS</div>
                        <div style="font-size:24px;font-weight:bold;color:#7b1fa2"><?php echo count($categorias); ?></div>
                    </div>
                    <div style="background:#fff3e0;padding:12px;border-radius:6px;text-align:center">
                        <div style="font-size:12px;color:#e65100;font-weight:600">UBICACIONES</div>
                        <div style="font-size:24px;font-weight:bold;color:#e65100"><?php echo count($ubicaciones); ?></div>
                    </div>
                    <div style="background:#e8f5e9;padding:12px;border-radius:6px;text-align:center">
                        <div style="font-size:12px;color:#388e3c;font-weight:600">PERSONALIZADAS</div>
                        <div style="font-size:24px;font-weight:bold;color:#388e3c"><?php echo count($urls); ?></div>
                    </div>
                </div>
                
                <div style="background:#d1ecf1;border:1px solid #bee5eb;padding:15px;border-radius:6px;margin-bottom:20px">
                    <strong>📍 Sitemap:</strong> <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank"><?php echo home_url('/sitemap.xml'); ?></a>
                    <button class="button button-primary" style="margin-left:15px" onclick="regenerarSitemap()">🔄 Regenerar</button>
                </div>
                
                <h2>➕ Añadir URL</h2>
                <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:20px">
                    <div style="display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end">
                        <div>
                            <label style="display:block;margin-bottom:5px;font-weight:600">URL</label>
                            <input type="url" id="new_url" placeholder="https://..." style="width:100%;padding:8px">
                        </div>
                        <button class="button button-primary" onclick="addURL()">Añadir</button>
                    </div>
                </div>
                
                <h2>📋 Todas las URLs</h2>
                <input type="text" id="search" placeholder="🔍 Buscar..." style="width:300px;padding:8px;margin-bottom:15px">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:50px">Estado</th>
                            <th>URL / Título</th>
                            <th style="width:120px">Tipo</th>
                            <th style="width:120px">Última Modificación</th>
                            <th style="width:80px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="urls-table">
                        <?php foreach ($todas as $u): 
                            $ex = isset($u['excluida']) && $u['excluida'];
                        ?>
                        <tr style="<?php echo $ex ? 'opacity:0.5;background:#fff3cd' : ''; ?>" data-url="<?php echo esc_attr($u['loc']); ?>">
                            <td>
                                <input type="checkbox" class="toggle" data-id="<?php echo $u['id']; ?>" <?php checked(!$ex); ?>>
                            </td>
                            <td>
                                <?php if (isset($u['titulo'])): ?>
                                    <strong><?php echo esc_html($u['titulo']); ?></strong><br>
                                    <small style="color:#666"><?php echo esc_html($u['loc']); ?></small>
                                <?php else: ?>
                                    <?php echo esc_html($u['loc']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="padding:4px 10px;background:<?php echo $u['tipo']=='producto'?'#e3f2fd':($u['tipo']=='categoria'?'#f3e5f5':($u['tipo']=='ubicacion'?'#fff3e0':'#e8f5e9')); ?>;border-radius:4px;font-size:11px;font-weight:600;color:<?php echo $u['tipo']=='producto'?'#1976d2':($u['tipo']=='categoria'?'#7b1fa2':($u['tipo']=='ubicacion'?'#e65100':'#388e3c')); ?>">
                                    <?php echo strtoupper($u['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <small style="color:#666"><?php echo isset($u['lastmod']) ? date('d/m/Y', strtotime($u['lastmod'])) : '-'; ?></small>
                            </td>
                            <td>
                                <?php if ($u['tipo'] == 'personalizada'): ?>
                                    <button class="button" onclick="deleteURL(<?php echo $u['index']; ?>)" title="Eliminar">🗑️</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        function sitemapAjax(action, data, callback) {
            data.action = 'sitemap_action';
            data.sub_action = action;
            data.nonce = '<?php echo wp_create_nonce('sitemap'); ?>';
            jQuery.post(ajaxurl, data, callback);
        }
        function addURL() {
            var url = jQuery('#new_url').val();
            if (!url) return alert('Introduce una URL');
            sitemapAjax('add_url', {url: url}, function(r) {
                if (r.success) location.reload();
            });
        }
        function deleteURL(idx) {
            if (confirm('¿Eliminar esta URL del sitemap?')) {
                sitemapAjax('delete_url', {index: idx}, function(r) {
                    if (r.success) location.reload();
                });
            }
        }
        function regenerarSitemap() {
            sitemapAjax('regenerar', {}, function(r) {
                if (r.success) alert('✅ Sitemap regenerado correctamente');
            });
        }
        jQuery(function($) {
            $('.toggle').change(function() {
                var $this = $(this);
                sitemapAjax('toggle', {id: $this.data('id'), incluir: $this.is(':checked')}, function(r) {
                    if (r.success) setTimeout(function() { location.reload(); }, 500);
                });
            });
            $('#search').keyup(function() {
                var term = $(this).val().toLowerCase();
                $('#urls-table tr').each(function() {
                    var url = $(this).data('url') || '';
                    $(this).toggle(url.toLowerCase().indexOf(term) > -1);
                });
            });
        });
        </script>
        <?php
    }
    
    public function pagina_historial() {
        $historial = get_option('sitemap_history', array());
        ?>
        <div class="wrap">
            <h1>📜 Historial de Cambios</h1>
            <div style="background:#fff;padding:20px;border-radius:8px;margin:20px 0">
                <button class="button" onclick="if(confirm('¿Limpiar todo el historial?')) limpiarHistorial()">🗑️ Limpiar Historial</button>
                <div style="margin-top:20px;max-height:70vh;overflow-y:auto">
                    <?php if (empty($historial)): ?>
                        <div style="text-align:center;padding:40px;color:#999">📭 Sin registros</div>
                    <?php else: ?>
                        <?php foreach ($historial as $h): ?>
                            <div style="border-left:4px solid #667eea;background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px">
                                <strong><?php echo esc_html($h['desc']); ?></strong>
                                <div style="font-size:12px;color:#666;margin-top:5px">
                                    <?php echo date('d/m/Y H:i:s', strtotime($h['fecha'])); ?> • <?php echo esc_html($h['usuario']); ?>
                                </div>
                                <?php if (!empty($h['detalles'])): ?>
                                    <div style="background:#fff;padding:10px;margin-top:10px;border-radius:4px;font-size:13px">
                                        <?php foreach ($h['detalles'] as $k => $v): ?>
                                            <div><strong><?php echo ucfirst($k); ?>:</strong> <?php echo esc_html($v); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
        function limpiarHistorial() {
            jQuery.post(ajaxurl, {action: 'sitemap_action', sub_action: 'clear_history', nonce: '<?php echo wp_create_nonce('sitemap'); ?>'}, function(r) {
                if (r.success) location.reload();
            });
        }
        </script>
        <?php
    }
    
    public function ajax_handler() {
        check_ajax_referer('sitemap', 'nonce');
        $action = $_POST['sub_action'];
        
        if ($action == 'add_url') {
            $urls = get_option('sitemap_urls', array());
            $urls[] = array('loc' => esc_url_raw($_POST['url']));
            update_option('sitemap_urls', $urls, false);
            $this->log('URL añadida', array('url' => $_POST['url']));
            $this->generar();
            wp_send_json_success();
        }
        
        if ($action == 'delete_url') {
            $urls = get_option('sitemap_urls', array());
            $idx = intval($_POST['index']);
            if (isset($urls[$idx])) {
                $this->log('URL eliminada', array('url' => $urls[$idx]['loc']));
                unset($urls[$idx]);
                update_option('sitemap_urls', array_values($urls), false);
                $this->generar();
                wp_send_json_success();
            }
        }
        
        if ($action == 'toggle') {
            $excluded = get_option('sitemap_excluded', array());
            $id = sanitize_text_field($_POST['id']);
            $incluir = $_POST['incluir'] == 'true';
            if ($incluir) {
                $excluded = array_diff($excluded, array($id));
                $this->log('URL incluida', array('id' => $id));
            } else {
                if (!in_array($id, $excluded)) $excluded[] = $id;
                $this->log('URL excluida', array('id' => $id));
            }
            update_option('sitemap_excluded', array_values($excluded));
            $this->generar();
            wp_send_json_success();
        }
        
        if ($action == 'regenerar') {
            $this->generar();
            wp_send_json_success();
        }
        
        if ($action == 'clear_history') {
            update_option('sitemap_history', array(), false);
            wp_send_json_success();
        }
    }
    
    public function regenerar($post_id, $post, $update) {
        if ($post->post_status == 'publish' && !$update) {
            $this->log('Producto añadido', array('producto' => $post->post_title));
        }
        $this->generar();
    }
    
    public function regenerar_delete($post_id) {
        if (get_post_type($post_id) == 'product') {
            $p = get_post($post_id);
            $this->log('Producto eliminado', array('producto' => $p->post_title));
            $this->generar();
        }
    }
    
    public function generar() {
        $urls = get_option('sitemap_urls', array());
        $excluded = get_option('sitemap_excluded', array());
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n\n";
        
        // URLs personalizadas
        foreach ($urls as $i => $u) {
            $id = 'custom_'.$i;
            if (!in_array($id, $excluded)) {
                $xml .= '  <url>'."\n";
                $xml .= '    <loc>'.esc_url($u['loc']).'</loc>'."\n";
                $xml .= '    <lastmod>'.date('Y-m-d').'</lastmod>'."\n";
                $xml .= '  </url>'."\n\n";
            }
        }
        
        // Productos
        $productos = get_posts(array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        foreach ($productos as $p) {
            $id = 'product_'.$p->ID;
            if (!in_array($id, $excluded)) {
                $xml .= '  <url>'."\n";
                $xml .= '    <loc>'.get_permalink($p->ID).'</loc>'."\n";
                $xml .= '    <lastmod>'.get_the_modified_date('Y-m-d', $p->ID).'</lastmod>'."\n";
                $xml .= '  </url>'."\n\n";
            }
        }
        
        // Categorías
        $categorias = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
        foreach ($categorias as $c) {
            $id = 'cat_'.$c->term_id;
            if (!in_array($id, $excluded)) {
                $xml .= '  <url>'."\n";
                $xml .= '    <loc>'.get_term_link($c).'</loc>'."\n";
                $xml .= '    <lastmod>'.date('Y-m-d').'</lastmod>'."\n";
                $xml .= '  </url>'."\n\n";
            }
        }
        
        // Ubicaciones
        $ubicaciones = get_posts(array(
            'post_type'              => 'standort',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        foreach ($ubicaciones as $u) {
            $id = 'ubicacion_'.$u->ID;
            if (!in_array($id, $excluded)) {
                $xml .= '  <url>'."\n";
                $xml .= '    <loc>'.get_permalink($u->ID).'</loc>'."\n";
                $xml .= '    <lastmod>'.get_the_modified_date('Y-m-d', $u->ID).'</lastmod>'."\n";
                $xml .= '  </url>'."\n\n";
            }
        }
        
        $xml .= '</urlset>';
        file_put_contents(ABSPATH . 'sitemap.xml', $xml);
    }
    
    private function log($desc, $detalles = array()) {
        $h = get_option('sitemap_history', array());
        array_unshift($h, array(
            'desc' => $desc,
            'detalles' => $detalles,
            'fecha' => current_time('mysql'),
            'usuario' => wp_get_current_user()->display_name
        ));
        if (count($h) > 200) $h = array_slice($h, 0, 200);
        update_option('sitemap_history', $h, false);
    }
}

new SitemapPro();