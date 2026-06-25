<?php
/**
 * Shortcode para mostrar productos destacados
 */

add_shortcode('padel_featured_products', 'wfpp_featured_products_shortcode');
function wfpp_featured_products_shortcode($atts) {

    global $post;

    $atts = shortcode_atts(array(
        'category'   => '',
        'title'      => '',
        'show_specs' => 'yes'
    ), $atts);

    $title = $atts['title'];

    // ── RUTA A: Página de categoría → usa SIEMPRE datos de la categoría ─────────
    // Totalmente independiente de los datos de producto.
    if (is_product_category() && empty($atts['category'])) {
        $category = get_queried_object();

        if (!$category || !isset($category->term_id)) {
            return '';
        }

        $custom_h1     = get_term_meta($category->term_id, 'custom_h1', true);
        $cat_custom_h2 = get_term_meta($category->term_id, 'h2_titulo', true);

        if (empty($title) && !empty($custom_h1))     $title = 'Die 3 besten ' . $custom_h1;
        if (empty($title) && !empty($cat_custom_h2)) $title = $cat_custom_h2;
        if (empty($title))                           $title = 'Die 3 besten ' . $category->name;

        $featured_product_ids = get_term_meta($category->term_id, '_wfpp_featured_products', true);

        if (empty($featured_product_ids) || !is_array($featured_product_ids)) {
            return '';
        }

        $featured_product_ids = array_filter($featured_product_ids);

        if (empty($featured_product_ids)) {
            return '';
        }

        $textos_palas = get_term_meta($category->term_id, '_wfpp_textos_palas', true);
        if (!is_array($textos_palas)) {
            $textos_palas = array('', '', '');
        }

        return wfpp_render_products($featured_product_ids, $title, $atts['show_specs'], $textos_palas);
    }

    // ── RUTA B: Página de producto → manual primero, auto-similar si no hay ─────
    // Nunca usa datos de categoría. Completamente independiente de RUTA A.
    if (is_singular('product') && $post) {
        $current_product_id = $post->ID;

        $product_ids_raw   = get_post_meta($current_product_id, '_wfpp_product_featured_ids', true);
        $product_title_raw = get_post_meta($current_product_id, '_wfpp_product_featured_title', true);

        // Prioridad 1: asignación manual en el admin del producto
        if (!empty($product_ids_raw)) {
            $featured_product_ids = array_filter(array_map('intval', explode(',', $product_ids_raw)));

            if (!empty($featured_product_ids)) {
                if (empty($title) && !empty($product_title_raw)) {
                    $title = $product_title_raw;
                }
                if (empty($title)) {
                    $title = 'Ähnliche Artikel';
                }

                $textos_palas = get_post_meta($current_product_id, '_wfpp_product_featured_textos', true);
                if (!is_array($textos_palas)) $textos_palas = array();

                return wfpp_render_products($featured_product_ids, $title, $atts['show_specs'], $textos_palas);
            }
        }

        // Prioridad 2: auto-similar por criterios (spielniveau + form + precio)
$auto_ids = wfpp_auto_similar_palas($current_product_id);
$auto_ids = apply_filters('wfpp_auto_similar_ids', $auto_ids, $current_product_id);        if (!empty($auto_ids)) {
            if (empty($title) && !empty($product_title_raw)) $title = $product_title_raw;
            if (empty($title)) $title = 'Ähnliche Artikel';

            $textos_palas = get_post_meta($current_product_id, '_wfpp_product_featured_textos', true);
            if (!is_array($textos_palas)) $textos_palas = array();

            return wfpp_render_products($auto_ids, $title, $atts['show_specs'], $textos_palas);
        }

        return '';
    }

    // ── RUTA C: Uso con atributo category= explícito (shortcode manual) ─────────
    if (!empty($atts['category'])) {
        $category = get_term_by('slug', $atts['category'], 'product_cat');

        if (!$category || !isset($category->term_id)) {
            return '';
        }

        $custom_h1     = get_term_meta($category->term_id, 'custom_h1', true);
        $cat_custom_h2 = get_term_meta($category->term_id, 'h2_titulo', true);

        if (empty($title) && !empty($custom_h1))     $title = 'Die 3 besten ' . $custom_h1;
        if (empty($title) && !empty($cat_custom_h2)) $title = $cat_custom_h2;
        if (empty($title))                           $title = 'Die 3 besten ' . $category->name;

        $featured_product_ids = get_term_meta($category->term_id, '_wfpp_featured_products', true);

        if (empty($featured_product_ids) || !is_array($featured_product_ids)) {
            return '';
        }

        $featured_product_ids = array_filter($featured_product_ids);

        if (empty($featured_product_ids)) {
            return '';
        }

        $textos_palas = get_term_meta($category->term_id, '_wfpp_textos_palas', true);
        if (!is_array($textos_palas)) {
            $textos_palas = array('', '', '');
        }

        return wfpp_render_products($featured_product_ids, $title, $atts['show_specs'], $textos_palas);
    }

    return '';
}


// ── Auto-recomendación: 3 palas similares de padelschlaeger ──────────────────
/**
 * Busca hasta 3 palas de la categoría "padelschlaeger" similares al producto dado.
 *
 * Criterios de similitud en orden de exigencia:
 *   1. spielniveau + form + precio (±30%)
 *   2. spielniveau + form
 *   3. spielniveau + precio (±30%)
 *   4. spielniveau
 *   5. form
 *   6. cualquier pala de padelschlaeger
 *
 * Solo actúa si el producto actual pertenece a padelschlaeger.
 * Excluye siempre el producto actual.
 *
 * @param  int   $product_id
 * @return int[]
 */
function wfpp_auto_similar_palas($product_id) {

    $cache_key = 'wfpp_similar_' . $product_id;
    $cached    = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $current = wc_get_product($product_id);
    if (!$current) return array();

    // Solo actuar si el producto pertenece a padelschlaeger
    $cats = get_the_terms($product_id, 'product_cat');
    if (!$cats || is_wp_error($cats)) return array();
    $cat_slugs = wp_list_pluck($cats, 'slug');
    if (!in_array('padelschlaeger', $cat_slugs, true)) return array();

    // Características del producto actual
    $nivel  = wfpp_get_product_attribute($current, 'spielniveau');
    $forma  = wfpp_get_product_attribute($current, 'form');
    $precio = floatval($current->get_price());

    // Todos los candidatos de padelschlaeger (excluido el actual)
    $candidates = get_posts(array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'post__not_in'           => array($product_id),
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'padelschlaeger',
            ),
        ),
    ));

    if (empty($candidates)) return array();

    // Evalúa si un candidato pasa los filtros activos
    $pasa_filtros = function($cid, $usar_nivel, $usar_forma, $usar_precio) use ($nivel, $forma, $precio) {
        $p = wc_get_product($cid);
        if (!$p) return false;

        if ($usar_nivel && !empty($nivel)) {
            if (wfpp_get_product_attribute($p, 'spielniveau') !== $nivel) return false;
        }
        if ($usar_forma && !empty($forma)) {
            if (wfpp_get_product_attribute($p, 'form') !== $forma) return false;
        }
        if ($usar_precio && $precio > 0) {
            $cp = floatval($p->get_price());
            if ($cp <= 0) return false;
            if (abs($cp - $precio) / $precio > 0.30) return false;
        }
        return true;
    };

    // Intentos de mayor a menor exigencia
    $intentos = array(
        array(true,  true,  true ),   // nivel + forma + precio
        array(true,  true,  false),   // nivel + forma
        array(true,  false, true ),   // nivel + precio
        array(true,  false, false),   // solo nivel
        array(false, true,  false),   // solo forma
        array(false, false, false),   // cualquier pala de padelschlaeger
    );

    $resultado = array();
    foreach ($intentos as $filtros) {
        $resultado = array();
        foreach ($candidates as $cid) {
            if ($pasa_filtros($cid, $filtros[0], $filtros[1], $filtros[2])) {
                $resultado[] = $cid;
                if (count($resultado) === 3) break;
            }
        }
        if (count($resultado) === 3) return $resultado;
    }

    $result = array_slice($resultado, 0, 3);
    set_transient($cache_key, $result, HOUR_IN_SECONDS);
    return $result;
}

add_action('save_post_product', function($post_id) {
    delete_transient('wfpp_similar_' . $post_id);
});


// ── Función de render reutilizable ────────────────────────────────────────────
function wfpp_render_products($featured_product_ids, $title, $show_specs = 'yes', $textos_palas = array()) {
    $args = array(
        'post_type'      => 'product',
        'post__in'       => $featured_product_ids,
        'posts_per_page' => count($featured_product_ids),
        'orderby'        => 'post__in',
        'post_status'    => 'publish',
    );

    $products = get_posts($args);

    if (empty($products)) {
        return '';
    }

    ob_start();

    if (!is_array($textos_palas)) {
        $textos_palas = array('', '', '');
    }
    ?>

    <div class="wfpp-featured-container">
        <div class="wfpp-featured-header">
<h2><?php echo esc_html($title); ?></h2>
        </div>

        <div class="wfpp-products-grid">
            <?php
            $product_index = 0;
            foreach ($products as $product_post):
                $product = wc_get_product($product_post->ID);

                if (!$product) continue;

                $texto_pala = isset($textos_palas[$product_index]) ? $textos_palas[$product_index] : '';
                $product_index++;

                $product_id    = $product->get_id();
                $product_name  = str_replace('Padelschläger', '', $product->get_name());
                $product_name  = trim($product_name);
                $product_url   = $product->get_permalink();
                $product_image = wp_get_attachment_image_src($product->get_image_id(), 'medium');
                $image_url     = $product_image ? $product_image[0] : wc_placeholder_img_src();

                $regular_price = $product->get_regular_price();
                $sale_price    = $product->get_sale_price();
                $current_price = $product->get_price();

                $discount = 0;
                if ($sale_price && $regular_price) {
                    $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
                }

                $average_rating = $product->get_average_rating();
                $review_count   = $product->get_review_count();

                $spielniveau = wfpp_get_product_attribute($product, 'spielniveau');
                $form        = wfpp_get_product_attribute($product, 'form');
                $spieltyp    = wfpp_get_product_attribute($product, 'spieltyp');

                $free_shipping = wfpp_has_free_shipping($product);
            ?>

            <div class="wfpp-product-card">

                <div class="wfpp-product-image">
                    <a href="<?php echo esc_url($product_url); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_name); ?>">
                    </a>
                </div>

<p class="wfpp-product-title">
                    <a href="<?php echo esc_url($product_url); ?>">
                        <?php echo esc_html($product_name); ?>
                    </a>
                </p>

                <?php if (!empty($texto_pala)): ?>
                    <p class="wfpp-campo-entre-titulo-resena"><?php echo esc_html($texto_pala); ?></p>
                <?php endif; ?>

                <div class="wfpp-rating">
                    <?php if ($average_rating > 0): ?>
                        <span class="wfpp-rating-number"><?php echo number_format($average_rating, 1, ',', '.'); ?></span>
                        <span class="wfpp-stars"><?php echo wfpp_generate_stars($average_rating); ?></span>
                        <?php if ($review_count > 0): ?>
<a href="<?php echo esc_url($product_url); ?>#reviews" class="wfpp-reviews-link">
    <span class="wfpp-reviews-arrow">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="wfpp-reviews-count">(<?php echo $review_count; ?>)</span>
</a>

                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="wfpp-price-container">
                    <?php if ($sale_price): ?>
                        <span class="wfpp-price"><?php echo wc_price($sale_price); ?></span>
                        <span class="wfpp-old-price"><?php echo wc_price($regular_price); ?></span>
                    <?php else: ?>
                        <span class="wfpp-price"><?php echo wc_price($current_price); ?></span>
                    <?php endif; ?>
                </div>

                <div class="wfpp-shipping">
                    <?php if ($free_shipping): ?>
                        <span class="wfpp-shipping-text">Gratisversand.</span>
                        <span class="wfpp-delivery-time">Lieferung in 4-5 Tagen</span>
                    <?php endif; ?>
                </div>

                <div class="wfpp-product-specs">
                    <?php if ($show_specs === 'yes' && ($spielniveau || $form || $spieltyp)): ?>
                        <?php if ($spielniveau): ?>
                            <div class="wfpp-spec">
                                <div class="wfpp-spec-icon wfpp-spielniveau-icon">
                                    <?php echo wfpp_get_icon_by_attribute_value('spielniveau', $spielniveau); ?>
                                </div>
                                <span class="wfpp-spec-label"><?php echo esc_html($spielniveau); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($form): ?>
                            <div class="wfpp-spec">
                                <div class="wfpp-spec-icon wfpp-form-icon">
                                    <?php echo wfpp_get_icon_by_attribute_value('form', $form); ?>
                                </div>
                                <span class="wfpp-spec-label"><?php echo esc_html($form); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($spieltyp): ?>
                            <div class="wfpp-spec">
                                <div class="wfpp-spec-icon wfpp-spieltyp-icon">
                                    <?php echo wfpp_get_icon_by_attribute_value('spieltyp', $spieltyp); ?>
                                </div>
                                <span class="wfpp-spec-label"><?php echo esc_html($spieltyp); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <a href="<?php echo esc_url($product_url); ?>" class="wfpp-cta-button">
                    Zum Produkt
                </a>
            </div>

            <?php endforeach; ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}


// ── Funciones auxiliares ──────────────────────────────────────────────────────

function wfpp_get_product_attribute($product, $attribute_name) {
    $attributes = $product->get_attributes();

    foreach ($attributes as $attribute) {
        if ($attribute->get_name() === $attribute_name || $attribute->get_name() === 'pa_' . $attribute_name) {
            $terms = $attribute->get_terms();
            if ($terms && !is_wp_error($terms)) {
                return $terms[0]->name;
            }
            $options = $attribute->get_options();
            return !empty($options) ? $options[0] : '';
        }
    }

    return '';
}

function wfpp_has_free_shipping($product) {
    $shipping_class = $product->get_shipping_class();
    return false;
}

function wfpp_generate_stars($rating) {
    $full_stars  = floor($rating);
    $half_star   = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    $html = '';

    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<span class="star star-full">★</span>';
    }

    if ($half_star) {
        $html .= '<span class="star star-half">★</span>';
    }

    for ($i = 0; $i < $empty_stars; $i++) {
        $html .= '<span class="star star-empty">☆</span>';
    }

    return $html;
}
