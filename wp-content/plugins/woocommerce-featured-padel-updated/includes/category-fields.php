<?php
/**
 * Campos personalizados para categorías de WooCommerce
 */

// Añadir campos al crear categoría
add_action('product_cat_add_form_fields', 'wfpp_add_category_fields', 10, 1);
function wfpp_add_category_fields($taxonomy) {
    ?>
    <div class="form-field">
        <label><?php _e('Productos Destacados de Padel', 'wfpp'); ?></label>
        <p><?php _e('Añade esta categoría primero. Después podrás seleccionar 3 productos destacados al editarla.', 'wfpp'); ?></p>
    </div>
    <?php
}

// Añadir campos al editar categoría
add_action('product_cat_edit_form_fields', 'wfpp_edit_category_fields', 10, 1);
function wfpp_edit_category_fields($term) {
    $term_id = $term->term_id;
    
    // Obtener productos destacados guardados
    $featured_products = get_term_meta($term_id, '_wfpp_featured_products', true);
    if (!is_array($featured_products)) {
        $featured_products = array('', '', '');
    }
    
    // Obtener textos individuales para cada pala
    $textos_palas = get_term_meta($term_id, '_wfpp_textos_palas', true);
    if (!is_array($textos_palas)) {
        $textos_palas = array('', '', '');
    }
    
    // Obtener productos de la categoría "padelschlaeger" o todos los productos
    // Puedes cambiar 'padelschlaeger' por el slug de tu categoría
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    );
    
    // Si quieres filtrar solo por la categoría "padelschlaeger", descomenta esto:
    /*
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => 'padelschlaeger', // Cambia esto al slug de tu categoría
        )
    );
    */
    
    $products = get_posts($args);
    ?>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label><?php _e('Productos Destacados de Padel', 'wfpp'); ?></label>
        </th>
        <td>
            <div class="wfpp-featured-products-wrapper">
                
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="wfpp-product-selector" style="margin-bottom: 20px;">
                        <label for="wfpp_featured_product_<?php echo $i; ?>">
                            <strong><?php echo sprintf(__('Producto Destacado %d:', 'wfpp'), $i + 1); ?></strong>
                        </label>
                        <select 
                            id="wfpp_featured_product_<?php echo $i; ?>" 
                            name="wfpp_featured_products[]" 
                            style="width: 100%; max-width: 500px; margin-top: 5px;"
                            class="wfpp-product-select"
                        >
                            <option value=""><?php _e('-- Seleccionar Producto --', 'wfpp'); ?></option>
                            <?php foreach ($products as $product): 
                                $product_obj = wc_get_product($product->ID);
                                $price = $product_obj->get_price_html();
                            ?>
                                <option value="<?php echo $product->ID; ?>" 
                                    <?php selected(isset($featured_products[$i]) ? $featured_products[$i] : '', $product->ID); ?>>
                                    <?php echo esc_html($product->post_title); ?> 
                                    <?php if ($product_obj->get_regular_price()): ?>
                                        - <?php echo strip_tags($price); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div style="margin-top: 10px;">
                            <label for="wfpp_texto_pala_<?php echo $i; ?>">
                                <strong><?php _e('Texto personalizado para esta pala:', 'wfpp'); ?></strong>
                            </label>
                            <textarea 
                                id="wfpp_texto_pala_<?php echo $i; ?>" 
                                name="wfpp_textos_palas[]" 
                                rows="3" 
                                style="width: 100%; max-width: 500px; margin-top: 5px;"
                                placeholder="<?php _e('Texto que aparecerá entre el título y las reseñas para esta pala específica', 'wfpp'); ?>"
                            ><?php echo esc_textarea(isset($textos_palas[$i]) ? $textos_palas[$i] : ''); ?></textarea>
                            <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                <?php _e('Este texto se mostrará como un párrafo entre el título y las reseñas de este producto específico.', 'wfpp'); ?>
                            </p>
                        </div>
                    </div>
                <?php endfor; ?>
                
            </div>
        </td>
    </tr>
    
    <style>
        .wfpp-featured-products-wrapper {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .wfpp-product-selector {
            background: #f9f9f9;
            padding: 15px;
            border-left: 3px solid #2271b1;
            border-radius: 3px;
        }
        .wfpp-product-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
    <?php
}

// Guardar campos de la categoría
add_action('edited_product_cat', 'wfpp_save_category_fields', 10, 1);
add_action('create_product_cat', 'wfpp_save_category_fields', 10, 1);
function wfpp_save_category_fields($term_id) {
    
    if (isset($_POST['wfpp_featured_products'])) {
        $featured_products = array_map('intval', $_POST['wfpp_featured_products']);
        // Filtrar valores vacíos pero mantener el array de 3 posiciones
        $featured_products = array_slice(array_pad($featured_products, 3, 0), 0, 3);
        update_term_meta($term_id, '_wfpp_featured_products', $featured_products);
    }
    
    // Guardar textos individuales de cada pala
    if (isset($_POST['wfpp_textos_palas'])) {
        $textos_palas = array_map('sanitize_textarea_field', $_POST['wfpp_textos_palas']);
        // Mantener el array de 3 posiciones
        $textos_palas = array_slice(array_pad($textos_palas, 3, ''), 0, 3);
        update_term_meta($term_id, '_wfpp_textos_palas', $textos_palas);
    }
    
    // NOTA: El campo antiguo 'campo_entre_titulo_resena' ya no se usa
    // pero lo dejamos por compatibilidad por si hay datos antiguos
    if (isset($_POST['campo_entre_titulo_resena'])) {
        $campo_entre_titulo_resena = sanitize_textarea_field($_POST['campo_entre_titulo_resena']);
        update_term_meta($term_id, 'campo_entre_titulo_resena', $campo_entre_titulo_resena);
    }
}

// Añadir columna en la lista de categorías
add_filter('manage_edit-product_cat_columns', 'wfpp_add_category_column');
function wfpp_add_category_column($columns) {
    $columns['wfpp_featured'] = __('Productos Destacados', 'wfpp');
    return $columns;
}

// Mostrar contenido de la columna
add_filter('manage_product_cat_custom_column', 'wfpp_category_column_content', 10, 3);
function wfpp_category_column_content($content, $column_name, $term_id) {
    
    if ($column_name === 'wfpp_featured') {
        $featured_products = get_term_meta($term_id, '_wfpp_featured_products', true);
        
        if (is_array($featured_products) && !empty(array_filter($featured_products))) {
            $count = count(array_filter($featured_products));
            $content = '<span class="dashicons dashicons-yes" style="color: #46b450;"></span> ' . 
                       sprintf(__('%d productos', 'wfpp'), $count);
        } else {
            $content = '<span class="dashicons dashicons-minus" style="color: #ddd;"></span> ' . 
                       __('Ninguno', 'wfpp');
        }
    }
    
    return $content;
}
