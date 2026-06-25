<?php
/**
 * Plugin Name: Grupos de Categorías WooCommerce
 * Description: Permite agrupar categorías de productos y mostrarlas con shortcodes
 * Version: 1.0
 * Author: Tu Nombre
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

class WC_Category_Groups {
    
    private $option_name = 'wc_category_groups';
    
    public function __construct() {
        // Agregar campos a la taxonomía
        add_action('product_cat_add_form_fields', array($this, 'add_category_fields'), 10);
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_fields'), 10);
        
        // Guardar campos
        add_action('created_product_cat', array($this, 'save_category_fields'), 10, 1);
        add_action('edited_product_cat', array($this, 'save_category_fields'), 10, 1);
        
        // Menú de administración para gestionar grupos
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar shortcode
        add_shortcode('productos_grupo', array($this, 'shortcode_productos_grupo'));
        
        // Estilos admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Obtener todos los grupos
     */
    public function get_groups() {
        $groups = get_option($this->option_name, array());
        if (!is_array($groups)) {
            $groups = array();
        }
        return $groups;
    }
    
    /**
     * Guardar grupos
     */
    public function save_groups($groups) {
        update_option($this->option_name, $groups);
    }
    
    /**
     * Agregar campos en el formulario de nueva categoría
     */
    public function add_category_fields() {
        $groups = $this->get_groups();
        ?>
        <div class="form-field">
            <label>Grupos</label>
            <?php if (empty($groups)): ?>
                <p>No hay grupos creados. <a href="<?php echo admin_url('admin.php?page=wc-category-groups'); ?>">Crear grupos</a></p>
            <?php else: ?>
                <?php foreach ($groups as $group_id => $group_name): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="category_groups[]" value="<?php echo esc_attr($group_id); ?>">
                        <?php echo esc_html($group_name); ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
            <p class="description">Selecciona los grupos a los que pertenece esta categoría</p>
        </div>
        <?php
    }
    
    /**
     * Agregar campos en el formulario de edición de categoría
     */
    public function edit_category_fields($term) {
        $groups = $this->get_groups();
        $term_groups = get_term_meta($term->term_id, 'category_groups', true);
        if (!is_array($term_groups)) {
            $term_groups = array();
        }
        ?>
        <tr class="form-field">
            <th scope="row"><label>Grupos</label></th>
            <td>
                <?php if (empty($groups)): ?>
                    <p>No hay grupos creados. <a href="<?php echo admin_url('admin.php?page=wc-category-groups'); ?>">Crear grupos</a></p>
                <?php else: ?>
                    <?php foreach ($groups as $group_id => $group_name): ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="category_groups[]" value="<?php echo esc_attr($group_id); ?>" 
                                <?php checked(in_array($group_id, $term_groups)); ?>>
                            <?php echo esc_html($group_name); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p class="description">Selecciona los grupos a los que pertenece esta categoría</p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Guardar los campos de la categoría
     */
    public function save_category_fields($term_id) {
        if (isset($_POST['category_groups'])) {
            $groups = array_map('sanitize_text_field', $_POST['category_groups']);
            update_term_meta($term_id, 'category_groups', $groups);
        } else {
            delete_term_meta($term_id, 'category_groups');
        }
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=product',
            'Grupos de Categorías',
            'Grupos de Categorías',
            'manage_options',
            'wc-category-groups',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página de administración de grupos
     */
    public function admin_page() {
        // Procesar acciones
        if (isset($_POST['add_group']) && check_admin_referer('wc_add_group')) {
            $group_name = sanitize_text_field($_POST['group_name']);
            if (!empty($group_name)) {
                $groups = $this->get_groups();
                $group_id = sanitize_title($group_name);
                $groups[$group_id] = $group_name;
                $this->save_groups($groups);
                echo '<div class="notice notice-success"><p>Grupo añadido correctamente</p></div>';
            }
        }
        
        if (isset($_POST['delete_group']) && check_admin_referer('wc_delete_group')) {
            $group_id = sanitize_text_field($_POST['group_id']);
            $groups = $this->get_groups();
            if (isset($groups[$group_id])) {
                unset($groups[$group_id]);
                $this->save_groups($groups);
                echo '<div class="notice notice-success"><p>Grupo eliminado correctamente</p></div>';
            }
        }
        
        $groups = $this->get_groups();
        ?>
        <div class="wrap">
            <h1>Gestión de Grupos de Categorías</h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Añadir Nuevo Grupo</h2>
                <form method="post">
                    <?php wp_nonce_field('wc_add_group'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="group_name">Nombre del Grupo</label></th>
                            <td>
                                <input type="text" name="group_name" id="group_name" class="regular-text" required>
                                <p class="description">Introduce el nombre del nuevo grupo</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_group" class="button button-primary" value="Añadir Grupo">
                    </p>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Grupos Existentes</h2>
                <?php if (empty($groups)): ?>
                    <p>No hay grupos creados todavía.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID del Grupo</th>
                                <th>Nombre del Grupo</th>
                                <th>Shortcode</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group_id => $group_name): ?>
                                <tr>
                                    <td><code><?php echo esc_html($group_id); ?></code></td>
                                    <td><?php echo esc_html($group_name); ?></td>
                                    <td>
                                        <input type="text" readonly value="[productos_grupo id=&quot;<?php echo esc_attr($group_id); ?>&quot;]" 
                                               class="regular-text" onclick="this.select();">
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('wc_delete_group'); ?>
                                            <input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>">
                                            <button type="submit" name="delete_group" class="button button-small" 
                                                    onclick="return confirm('¿Estás seguro de eliminar este grupo?');">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Uso del Shortcode</h2>
                <p>Utiliza el shortcode para mostrar productos de un grupo específico:</p>
                <code>[productos_grupo id="grupo-id"]</code>
                
                <h3>Parámetros disponibles:</h3>
                <ul>
                    <li><strong>id</strong>: ID del grupo (obligatorio)</li>
                    <li><strong>limite</strong>: Número de productos a mostrar (por defecto: -1, todos)</li>
                    <li><strong>columnas</strong>: Número de columnas (por defecto: 4)</li>
                    <li><strong>orden</strong>: ASC o DESC (por defecto: ASC)</li>
                </ul>
                
                <h3>Ejemplo:</h3>
                <code>[productos_grupo id="ofertas" limite="8" columnas="4" orden="DESC"]</code>
            </div>
        </div>
        <?php
    }
    
    /**
     * Shortcode para mostrar productos por grupo
     */
    public function shortcode_productos_grupo($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'limite' => -1,
            'columnas' => 4,
            'orden' => 'ASC'
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>Error: Debes especificar el ID del grupo</p>';
        }
        
        // Obtener todas las categorías que pertenecen a este grupo
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        $category_ids = array();
        foreach ($terms as $term) {
            $term_groups = get_term_meta($term->term_id, 'category_groups', true);
            if (is_array($term_groups) && in_array($atts['id'], $term_groups)) {
                $category_ids[] = $term->term_id;
            }
        }
        
        if (empty($category_ids)) {
            return '<p>No hay categorías asignadas a este grupo</p>';
        }
        
        // Construir el shortcode de WooCommerce
        $shortcode = sprintf(
            '[products limit="%d" columns="%d" orderby="date" order="%s" category="%s"]',
            intval($atts['limite']),
            intval($atts['columnas']),
            esc_attr($atts['orden']),
            implode(',', $category_ids)
        );
        
        return do_shortcode($shortcode);
    }
    
    /**
     * Estilos para el admin
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'product_page_wc-category-groups') {
            wp_add_inline_style('wp-admin', '
                .wrap input[readonly] {
                    background: #f0f0f1;
                    cursor: pointer;
                }
            ');
        }
    }
}

// Inicializar el plugin
new WC_Category_Groups();