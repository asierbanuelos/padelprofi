<?php
/**
 * Gestión de iconos para términos de atributos
 * Permite asignar iconos personalizados (SVG o PNG) a cada término de atributo
 */

// Encolar scripts necesarios para el media uploader
add_action('admin_enqueue_scripts', 'wfpp_enqueue_media_uploader');
function wfpp_enqueue_media_uploader($hook) {
    // Solo cargar en las páginas de edición de términos
    if ($hook !== 'edit-tags.php' && $hook !== 'term.php') {
        return;
    }
    
    // Verificar que estamos en una taxonomía soportada
    $screen = get_current_screen();
    $supported_taxonomies = array('pa_spielniveau', 'pa_form', 'pa_spieltyp', 'product_cat');
    
    if ($screen && in_array($screen->taxonomy, $supported_taxonomies)) {
        wp_enqueue_media();
    }
}

// Atributos que soportan iconos personalizados
function wfpp_get_icon_supported_attributes() {
    return array('spielniveau', 'form', 'spieltyp');
}

// Añadir campos de icono al crear término de atributo
add_action('pa_spielniveau_add_form_fields', 'wfpp_add_attribute_icon_field', 10, 1);
add_action('pa_form_add_form_fields', 'wfpp_add_attribute_icon_field', 10, 1);
add_action('pa_spieltyp_add_form_fields', 'wfpp_add_attribute_icon_field', 10, 1);

function wfpp_add_attribute_icon_field($taxonomy) {
    ?>
    <div class="form-field">
        <label for="wfpp_term_icon"><?php _e('Icono (SVG o PNG)', 'wfpp'); ?></label>
        
        <div class="wfpp-icon-upload-wrapper">
            <input type="hidden" id="wfpp_term_icon" name="wfpp_term_icon" value="" />
            <button type="button" class="button wfpp-upload-icon-button">
                <?php _e('Subir Icono', 'wfpp'); ?>
            </button>
            <button type="button" class="button wfpp-remove-icon-button" style="display:none;">
                <?php _e('Eliminar Icono', 'wfpp'); ?>
            </button>
            
            <div id="wfpp-icon-preview" style="margin-top: 10px; display: none;">
                <img src="" style="max-width: 40px; max-height: 40px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />
            </div>
        </div>
        
        <p class="description">
            <?php _e('Sube un archivo SVG o PNG para usar como icono. Tamaño recomendado: 40x40px o similar.', 'wfpp'); ?>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('.wfpp-upload-icon-button').on('click', function(e) {
            e.preventDefault();
            
            // Si el uploader ya existe, abrirlo
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Crear el media uploader
            mediaUploader = wp.media({
                title: '<?php _e('Seleccionar Icono', 'wfpp'); ?>',
                button: {
                    text: '<?php _e('Usar este icono', 'wfpp'); ?>'
                },
                library: {
                    type: ['image/svg+xml', 'image/png']
                },
                multiple: false
            });
            
            // Cuando se selecciona una imagen
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $('#wfpp_term_icon').val(attachment.id);
                $('#wfpp-icon-preview img').attr('src', attachment.url);
                $('#wfpp-icon-preview').show();
                $('.wfpp-remove-icon-button').show();
            });
            
            mediaUploader.open();
        });
        
        // Eliminar icono
        $('.wfpp-remove-icon-button').on('click', function(e) {
            e.preventDefault();
            
            $('#wfpp_term_icon').val('');
            $('#wfpp-icon-preview').hide();
            $('#wfpp-icon-preview img').attr('src', '');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Añadir campos de icono al editar término de atributo
add_action('pa_spielniveau_edit_form_fields', 'wfpp_edit_attribute_icon_field', 10, 2);
add_action('pa_form_edit_form_fields', 'wfpp_edit_attribute_icon_field', 10, 2);
add_action('pa_spieltyp_edit_form_fields', 'wfpp_edit_attribute_icon_field', 10, 2);

function wfpp_edit_attribute_icon_field($term, $taxonomy) {
    $term_id = $term->term_id;
    $icon_id = get_term_meta($term_id, '_wfpp_icon', true);
    $icon_url = $icon_id ? wp_get_attachment_url($icon_id) : '';
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="wfpp_term_icon"><?php _e('Icono (SVG o PNG)', 'wfpp'); ?></label>
        </th>
        <td>
            <div class="wfpp-icon-upload-wrapper">
                <input type="hidden" id="wfpp_term_icon" name="wfpp_term_icon" value="<?php echo esc_attr($icon_id); ?>" />
                <button type="button" class="button wfpp-upload-icon-button">
                    <?php _e('Subir Icono', 'wfpp'); ?>
                </button>
                <button type="button" class="button wfpp-remove-icon-button" style="<?php echo $icon_url ? '' : 'display:none;'; ?>">
                    <?php _e('Eliminar Icono', 'wfpp'); ?>
                </button>
                
                <?php if ($icon_url): ?>
                    <div id="wfpp-icon-preview" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; display: inline-block;">
                        <p style="margin: 0 0 8px 0; font-weight: 600;"><?php _e('Vista previa:', 'wfpp'); ?></p>
                        <img src="<?php echo esc_url($icon_url); ?>" style="max-width: 60px; max-height: 60px; border: 1px solid #ddd; padding: 8px; border-radius: 4px; background: #fff; display: block;" />
                    </div>
                <?php else: ?>
                    <div id="wfpp-icon-preview" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; display: none;">
                        <p style="margin: 0 0 8px 0; font-weight: 600;"><?php _e('Vista previa:', 'wfpp'); ?></p>
                        <img src="" style="max-width: 60px; max-height: 60px; border: 1px solid #ddd; padding: 8px; border-radius: 4px; background: #fff; display: block;" />
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="description" style="margin-top: 10px;">
                <?php _e('Sube un archivo SVG o PNG para usar como icono. Tamaño recomendado: 40x40px o similar.', 'wfpp'); ?>
            </p>
        </td>
    </tr>
    
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('.wfpp-upload-icon-button').on('click', function(e) {
            e.preventDefault();
            
            // Si el uploader ya existe, abrirlo
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Crear el media uploader
            mediaUploader = wp.media({
                title: '<?php _e('Seleccionar Icono', 'wfpp'); ?>',
                button: {
                    text: '<?php _e('Usar este icono', 'wfpp'); ?>'
                },
                library: {
                    type: ['image/svg+xml', 'image/png']
                },
                multiple: false
            });
            
            // Cuando se selecciona una imagen
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $('#wfpp_term_icon').val(attachment.id);
                $('#wfpp-icon-preview img').attr('src', attachment.url);
                $('#wfpp-icon-preview').css('display', 'inline-block');
                $('.wfpp-remove-icon-button').show();
            });
            
            mediaUploader.open();
        });
        
        // Eliminar icono
        $('.wfpp-remove-icon-button').on('click', function(e) {
            e.preventDefault();
            
            $('#wfpp_term_icon').val('');
            $('#wfpp-icon-preview').hide();
            $('#wfpp-icon-preview img').attr('src', '');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Guardar el icono del término
add_action('created_term', 'wfpp_save_attribute_icon_field', 10, 3);
add_action('edited_term', 'wfpp_save_attribute_icon_field', 10, 3);

function wfpp_save_attribute_icon_field($term_id, $tt_id, $taxonomy) {
    // Solo guardar para las taxonomías que soportamos
    $supported = array('pa_spielniveau', 'pa_form', 'pa_spieltyp', 'product_cat');
    
    if (!in_array($taxonomy, $supported)) {
        return;
    }
    
    if (isset($_POST['wfpp_term_icon'])) {
        $icon_id = intval($_POST['wfpp_term_icon']);
        
        if ($icon_id > 0) {
            update_term_meta($term_id, '_wfpp_icon', $icon_id);
        } else {
            delete_term_meta($term_id, '_wfpp_icon');
        }
    }
}

// Función para obtener el icono de un término
function wfpp_get_term_icon($term_id) {
    $icon_id = get_term_meta($term_id, '_wfpp_icon', true);
    
    if (!$icon_id) {
        return '';
    }
    
    $icon_url = wp_get_attachment_url($icon_id);
    
    if (!$icon_url) {
        return '';
    }
    
    // Obtener la extensión del archivo
    $file_extension = pathinfo($icon_url, PATHINFO_EXTENSION);
    
    // Si es SVG, obtener el contenido inline para mejor control de estilos
    if (strtolower($file_extension) === 'svg') {
        $icon_path = get_attached_file($icon_id);
        
        if ($icon_path && file_exists($icon_path)) {
            $svg_content = file_get_contents($icon_path);
            
            // Limpiar el SVG
            $svg_content = preg_replace('/<\?xml.*?\?>/i', '', $svg_content);
            $svg_content = preg_replace('/<!DOCTYPE.*?>/i', '', $svg_content);
            $svg_content = trim($svg_content);
            
            // Eliminar width y height existentes para controlarlos mejor
            $svg_content = preg_replace('/width="[^"]*"/', '', $svg_content);
            $svg_content = preg_replace('/height="[^"]*"/', '', $svg_content);
            
            // Añadir tamaño consistente
            $svg_content = str_replace('<svg', '<svg width="28" height="28"', $svg_content);
            
            // Forzar que el SVG use currentColor para heredar el color del CSS
            $svg_content = str_replace('fill="none"', 'fill="currentColor"', $svg_content);
            
            // Si no tiene fill definido, añadir currentColor
            if (strpos($svg_content, 'fill=') === false) {
                $svg_content = str_replace('<svg', '<svg fill="currentColor"', $svg_content);
            }
            
            return $svg_content;
        }
    }
    
    // Si es PNG o si falla la lectura del SVG, devolver como <img>
    return '<img src="' . esc_url($icon_url) . '" width="28" height="28" alt="" />';
}

// Función para obtener icono por nombre de término y atributo
function wfpp_get_icon_by_attribute_value($attribute_name, $term_name) {
    // Buscar el término
    $term = get_term_by('name', $term_name, 'pa_' . $attribute_name);
    
    if (!$term || is_wp_error($term)) {
        // Si no se encuentra como taxonomía global, retornar icono por defecto
        return wfpp_get_default_icon($attribute_name);
    }
    
    $icon = wfpp_get_term_icon($term->term_id);
    
    // Si no tiene icono configurado, usar el predeterminado
    if (empty($icon)) {
        return wfpp_get_default_icon($attribute_name);
    }
    
    return $icon;
}

// Iconos predeterminados por atributo (si no se sube ninguno)
function wfpp_get_default_icon($attribute_name) {
    // Devolver vacío - no mostrar iconos si no se han subido
    return '';
    
    /* Si quieres usar iconos predeterminados, descomenta esto:
    $defaults = array(
        'spielniveau' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'form' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/><circle cx="12" cy="12" r="5" fill="currentColor"/></svg>',
        'spieltyp' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    );
    
    return isset($defaults[$attribute_name]) ? $defaults[$attribute_name] : '';
    */
}