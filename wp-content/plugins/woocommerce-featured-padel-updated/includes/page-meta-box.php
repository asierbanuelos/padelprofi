<?php
/**
 * Meta Box para seleccionar categoría y título personalizado en páginas/posts
 */

// Añadir meta box a páginas y posts
add_action('add_meta_boxes', 'wfpp_add_page_meta_box');
function wfpp_add_page_meta_box() {
    
    $post_types = array('page', 'post');
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'wfpp_category_selector',
            __('Categoría de Productos Destacados', 'wfpp'),
            'wfpp_category_selector_callback',
            $post_type,
            'side',
            'default'
        );
    }
}

// Callback del meta box
function wfpp_category_selector_callback($post) {
    
    wp_nonce_field('wfpp_category_selector_nonce', 'wfpp_category_selector_nonce_field');
    
    $selected_category = get_post_meta($post->ID, '_wfpp_category', true);
    $custom_title = get_post_meta($post->ID, '_wfpp_custom_title', true);
    
    // Obtener todas las categorías de productos
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    ?>
    <div class="wfpp-category-selector-meta">
        <p>
            <label for="wfpp_category_select">
                <?php _e('Selecciona la categoría para el shortcode [padel_featured_products]', 'wfpp'); ?>
            </label>
        </p>
        
        <select id="wfpp_category_select" name="wfpp_category" style="width: 100%;">
            <option value=""><?php _e('-- Sin categoría --', 'wfpp'); ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <p style="margin-top: 15px;">
            <label for="wfpp_custom_title">
                <strong><?php _e('Título personalizado (H2):', 'wfpp'); ?></strong>
            </label>
            <input type="text" 
                   id="wfpp_custom_title" 
                   name="wfpp_custom_title" 
                   value="<?php echo esc_attr($custom_title); ?>" 
                   placeholder="Ej: Die besten Padelschläger"
                   style="width:100%; margin-top:5px;">
        </p>
        
        <p class="description" style="margin-top: 10px;">
            <?php _e('Si usas el shortcode sin especificar categoría o título, se usarán los valores definidos aquí.', 'wfpp'); ?>
        </p>
    </div>
    <?php
}

// Guardar meta box
add_action('save_post', 'wfpp_save_category_selector');
function wfpp_save_category_selector($post_id) {
    
    // Verificar nonce
    if (!isset($_POST['wfpp_category_selector_nonce_field']) || 
        !wp_verify_nonce($_POST['wfpp_category_selector_nonce_field'], 'wfpp_category_selector_nonce')) {
        return;
    }
    
    // Verificar autoguardado
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Guardar categoría
    if (isset($_POST['wfpp_category'])) {
        if (!empty($_POST['wfpp_category'])) {
            update_post_meta($post_id, '_wfpp_category', sanitize_text_field($_POST['wfpp_category']));
        } else {
            delete_post_meta($post_id, '_wfpp_category');
        }
    }
    
    // Guardar título personalizado
    if (isset($_POST['wfpp_custom_title'])) {
        $title = sanitize_text_field($_POST['wfpp_custom_title']);
        if (!empty($title)) {
            update_post_meta($post_id, '_wfpp_custom_title', $title);
        } else {
            delete_post_meta($post_id, '_wfpp_custom_title');
        }
    }
}