<?php
/**
 * Campos para seleccionar productos destacados a nivel de producto individual
 */

add_action('add_meta_boxes', 'wfpp_add_product_featured_meta_box');
function wfpp_add_product_featured_meta_box() {
    add_meta_box(
        'wfpp_product_featured_box',
        'Palas Recomendadas en esta Ficha',
        'wfpp_product_featured_meta_box_callback',
        'product',
        'normal',
        'default'
    );
}

function wfpp_product_featured_meta_box_callback($post) {
    wp_nonce_field('wfpp_product_featured_nonce', 'wfpp_product_featured_nonce_field');

    $saved_ids    = get_post_meta($post->ID, '_wfpp_product_featured_ids', true);
    $saved_title  = get_post_meta($post->ID, '_wfpp_product_featured_title', true);
    $saved_textos = get_post_meta($post->ID, '_wfpp_product_featured_textos', true);
    if (!is_array($saved_textos)) $saved_textos = array('', '', '');

    $manual_ids = array_filter(array_map('intval', explode(',', (string) $saved_ids)));
    $is_manual  = !empty($manual_ids);

    // Si es manual, usar esos IDs; si es automático, calcular cuáles salen ahora mismo
    if ($is_manual) {
        $slot_ids = array_values($manual_ids);
        while (count($slot_ids) < 3) $slot_ids[] = 0;
        $slot_ids = array_slice($slot_ids, 0, 3);
        $modo = 'manual';
    } else {
        // Llamar al auto-recomendador para saber qué palas mostraría ahora
        $auto_ids = function_exists('wfpp_auto_similar_palas')
            ? wfpp_auto_similar_palas($post->ID)
            : array();
        $slot_ids = array_values($auto_ids);
        while (count($slot_ids) < 3) $slot_ids[] = 0;
        $slot_ids = array_slice($slot_ids, 0, 3);
        $modo = 'auto';
    }

    // Lista de todos los productos para los selectores
    $all_products = get_posts(array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
        'post__not_in'   => array($post->ID),
        'fields'         => 'ids',
    ));

    ?>
    <style>
        #wfpp_product_featured_box .inside { padding: 12px; }

        .wfpp-admin-notice {
            border: 1px solid #c3c4c7;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #1d2327;
            background: #f6f7f7;
        }

        .wfpp-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        .wfpp-slot {
            border: 1px solid #c3c4c7;
        }

        .wfpp-slot-header {
            background: #f6f7f7;
            border-bottom: 1px solid #c3c4c7;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 10px;
        }

        .wfpp-slot-body { padding: 10px; }

        .wfpp-slot-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            min-height: 58px;
            background: #fff;
        }

        .wfpp-slot-preview.empty {
            color: #888;
            font-style: italic;
            font-size: 12px;
            justify-content: center;
        }

        .wfpp-slot-thumb {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border: 1px solid #eee;
            flex-shrink: 0;
            background: #fafafa;
        }

        .wfpp-slot-info { flex: 1; min-width: 0; }

        .wfpp-slot-product-name {
            font-size: 12px;
            font-weight: 600;
            color: #1d2327;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .wfpp-slot-product-price {
            font-size: 12px;
            color: #444;
        }

        .wfpp-slot-product-attrs {
            font-size: 11px;
            color: #777;
            margin-top: 2px;
        }

        .wfpp-slot label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #555;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .wfpp-slot select {
            width: 100%;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .wfpp-slot textarea {
            width: 100%;
            font-size: 12px;
            resize: vertical;
            min-height: 54px;
            box-sizing: border-box;
        }

        .wfpp-title-row label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .wfpp-title-row input[type="text"] {
            width: 100%;
            max-width: 500px;
        }
    </style>

    <div>

        <?php if ($modo === 'auto'): ?>
            <div class="wfpp-admin-notice">
                Modo automático — Las palas que aparecen abajo son las que se muestran ahora mismo en el frontend (calculadas por nivel, forma y precio). Para fijar palas concretas, selecciónalas en los desplegables.
            </div>
        <?php else: ?>
 
        <?php endif; ?>

        <div class="wfpp-slots-grid">
            <?php for ($i = 0; $i < 3; $i++):
                $pid   = isset($slot_ids[$i]) ? intval($slot_ids[$i]) : 0;
                $texto = isset($saved_textos[$i]) ? $saved_textos[$i] : '';

                $pala_name  = '';
                $pala_price = '';
                $pala_thumb = '';
                $pala_attrs = '';

                if ($pid > 0) {
                    $p = wc_get_product($pid);
                    if ($p) {
                        $pala_name  = $p->get_name();
                        $pala_price = strip_tags($p->get_price_html());
                        $img        = wp_get_attachment_image_src($p->get_image_id(), 'thumbnail');
                        $pala_thumb = $img ? $img[0] : '';

                        $nivel = wfpp_get_attr_label($p, 'spielniveau');
                        $forma = wfpp_get_attr_label($p, 'form');
                        $tipo  = wfpp_get_attr_label($p, 'spieltyp');
                        $pala_attrs = implode(' · ', array_filter(array($nivel, $forma, $tipo)));
                    }
                }

                // En modo auto los selectores quedan en 0 (sin asignar)
                $select_value = $is_manual ? $pid : 0;
            ?>
            <div class="wfpp-slot">
                <div class="wfpp-slot-header">
                    Pala <?php echo $i + 1; ?><?php if ($modo === 'auto'): ?> <span style="font-weight:400;font-size:11px;">(automática)</span><?php endif; ?>
                </div>
                <div class="wfpp-slot-body">

                    <?php if ($pid > 0 && $pala_name): ?>
                        <div class="wfpp-slot-preview">
                            <?php if ($pala_thumb): ?>
                                <img class="wfpp-slot-thumb" src="<?php echo esc_url($pala_thumb); ?>" alt="">
                            <?php endif; ?>
                            <div class="wfpp-slot-info">
                                <div class="wfpp-slot-product-name"><?php echo esc_html($pala_name); ?></div>
                                <div class="wfpp-slot-product-price"><?php echo esc_html($pala_price); ?></div>
                                <?php if ($pala_attrs): ?>
                                    <div class="wfpp-slot-product-attrs"><?php echo esc_html($pala_attrs); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="wfpp-slot-preview empty">Sin pala para este slot</div>
                    <?php endif; ?>

                    <label>Cambiar pala asignada:</label>
                    <select name="wfpp_featured_products[]">
                        <option value="0">— Sin asignar (automático) —</option>
                        <?php foreach ($all_products as $apid):
                            $ap = wc_get_product($apid);
                            if (!$ap) continue;
                            $label = $ap->get_name();
                            $ap_price = strip_tags($ap->get_price_html());
                            if ($ap_price) $label .= ' — ' . $ap_price;
                        ?>
                            <option value="<?php echo $apid; ?>" <?php selected($select_value, $apid); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Texto descriptivo (opcional):</label>
                    <textarea name="wfpp_product_featured_textos[]"
                        placeholder="Ej: Mejor control, ideal para nivel avanzado..."
                    ><?php echo esc_textarea($texto); ?></textarea>

                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="wfpp-title-row">
            <label for="_wfpp_product_featured_title_meta">Título de la sección (vacío = automático)</label>
            <input type="text"
                   id="_wfpp_product_featured_title_meta"
                   name="_wfpp_product_featured_title"
                   value="<?php echo esc_attr($saved_title); ?>"
                   placeholder="Ej: Ähnliche Artikel">
        </div>

    </div>
    <?php
}

// Función auxiliar: obtener nombre de atributo
function wfpp_get_attr_label($product, $attribute_name) {
    $attributes = $product->get_attributes();
    foreach ($attributes as $attribute) {
        if ($attribute->get_name() === $attribute_name || $attribute->get_name() === 'pa_' . $attribute_name) {
            $terms = $attribute->get_terms();
            if ($terms && !is_wp_error($terms)) return $terms[0]->name;
            $options = $attribute->get_options();
            return !empty($options) ? $options[0] : '';
        }
    }
    return '';
}

// Guardar campos del producto
add_action('woocommerce_process_product_meta', 'wfpp_save_product_level_fields');
function wfpp_save_product_level_fields($post_id) {

    if (!isset($_POST['wfpp_product_featured_nonce_field']) ||
        !wp_verify_nonce($_POST['wfpp_product_featured_nonce_field'], 'wfpp_product_featured_nonce')) {
        return;
    }

    if (isset($_POST['wfpp_featured_products']) && is_array($_POST['wfpp_featured_products'])) {
        $ids = array_map('intval', $_POST['wfpp_featured_products']);
        $ids = array_slice(array_pad($ids, 3, 0), 0, 3);

        $has_any = !empty(array_filter($ids));

        if ($has_any) {
            // Si hay al menos una asignada manualmente, rellenar los slots vacíos
            // con las palas automáticas para que no queden huecos
            $auto_ids = function_exists('wfpp_auto_similar_palas')
                ? wfpp_auto_similar_palas($post_id)
                : array();

            // Completar slots vacíos con automáticas (sin repetir IDs ya asignados)
            $auto_index = 0;
            for ($i = 0; $i < 3; $i++) {
                if (empty($ids[$i])) {
                    // Buscar la siguiente auto que no esté ya en $ids
                    while ($auto_index < count($auto_ids)) {
                        $candidate = $auto_ids[$auto_index];
                        $auto_index++;
                        if (!in_array($candidate, $ids, true)) {
                            $ids[$i] = $candidate;
                            break;
                        }
                    }
                }
            }

            update_post_meta($post_id, '_wfpp_product_featured_ids', implode(',', array_filter($ids)));
        } else {
            // Todos en 0 → volver a modo automático
            update_post_meta($post_id, '_wfpp_product_featured_ids', '');
        }
    } else {
        update_post_meta($post_id, '_wfpp_product_featured_ids', '');
    }

    if (isset($_POST['_wfpp_product_featured_title'])) {
        update_post_meta($post_id, '_wfpp_product_featured_title',
            sanitize_text_field($_POST['_wfpp_product_featured_title']));
    }

    if (isset($_POST['wfpp_product_featured_textos']) && is_array($_POST['wfpp_product_featured_textos'])) {
        $textos = array_map('sanitize_text_field', $_POST['wfpp_product_featured_textos']);
        $textos = array_slice(array_pad($textos, 3, ''), 0, 3);
        update_post_meta($post_id, '_wfpp_product_featured_textos', $textos);
    }
}