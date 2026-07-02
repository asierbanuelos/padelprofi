<?php
// Cargar estilos del tema padre y del tema hijo
function hello_elementor_child_enqueue_styles() {
    $parent_style = 'hello-elementor'; // Identificador de la hoja de estilos del padre

    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style('hello-elementor-child',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles');

// --- Checkout multi-step estilo MediaMarkt ---
require_once get_stylesheet_directory() . '/inc/checkout-functions.php';

// --- Popup carrito + shortcode [fk_cart_menu] ---
require_once get_stylesheet_directory() . '/inc/cart-popup.php';

// --- Página de carrito personalizada ---
require_once get_stylesheet_directory() . '/inc/cart-page.php';



/**
 * Añadir canonical autorreferencial en páginas paginadas
 */
add_action('wp_head', function() {
    if (is_paged()) {
        // Eliminar el canonical que pueda estar poniendo RankMath
        remove_action('wp_head', 'rel_canonical');
        
        // Obtener la URL actual completa
        global $wp;
        $current_url = home_url($wp->request);
        
        // Añadir barra final si es necesario
        if (get_option('permalink_structure') && substr($current_url, -1) !== '/') {
            $current_url .= '/';
        }
        
        // Imprimir el canonical
        echo '<link rel="canonical" href="' . esc_url($current_url) . '" />' . "\n";
    }
}, 1);


remove_action('wp_head', 'wp_site_icon', 99);

add_filter('wpml_language_url', function($url, $language) {
    $custom_urls = array(
        'de' => 'https://padelprofideutschland.de/',
        'en' => 'https://padelprofishop.com/',
        'es' => 'https://padelprofishop.com/es/',
        'fr' => 'https://padelprofishop.com/fr/',
        'it' => 'https://padelprofishop.com/it/',
        'nl' => 'https://padelprofishop.com/nl/',
        'pl' => 'https://padelprofishop.com/pl/',
        'cs' => 'https://padelprofishop.com/cs/',
        'hu' => 'https://padelprofishop.com/hu/',
        'ro' => 'https://padelprofishop.com/ro/',
        'sk' => 'https://padelprofishop.com/sk/',
        'sl' => 'https://padelprofishop.com/sl/',
        'tr' => 'https://padelprofishop.com/tr/',
        'uk' => 'https://padelprofishop.com/uk/',
    );
    return isset($custom_urls[$language]) ? $custom_urls[$language] : $url;
}, 10, 2);

add_filter('wpml_home_url', function($url) {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'padelprofishop.com') !== false) {
        return 'https://padelprofishop.com/';
    }
    return $url;
});

// Cambiar título del gateway Stripe — doble capa para cubrir cualquier versión del plugin
// Capa 1: filtro de título (prioridad 9999)
add_filter( 'woocommerce_gateway_title', function( $title, $id ) {
	if ( in_array( $id, [ 'stripe', 'stripe_cc', 'woocommerce_payments', 'stripe_upe' ], true ) ) {
		return 'Kredit- / Debitkarte';
	}
	return $title;
}, 9999, 2 );

// Capa 2: modificar directamente la propiedad ->title del objeto gateway
add_filter( 'woocommerce_available_payment_gateways', function( $gateways ) {
	$cc_ids = [ 'stripe', 'stripe_cc', 'woocommerce_payments', 'stripe_upe' ];
	foreach ( $cc_ids as $id ) {
		if ( isset( $gateways[ $id ] ) ) {
			$gateways[ $id ]->title = 'Kredit- / Debitkarte';
		}
	}

	// Klarna: mostrar solo "Sofort bezahlen" (pago inmediato), ocultar plazos y pago diferido
	unset( $gateways['klarna_payments_pay_later'] );
	unset( $gateways['klarna_payments_pay_over_time'] );

	return $gateways;
}, 9999 );

// Traducir strings WooCommerce al alemán (fallback cuando el .mo de WC no está cargado)
add_filter( 'gettext', function( $translated, $original, $domain ) {
	if ( $domain !== 'woocommerce' ) return $translated;
	static $map = [
		'Continue shopping'  => 'Weiter einkaufen',
		'Continue Shopping'  => 'Weiter einkaufen',
		'View cart'          => 'Warenkorb ansehen',
	];
	return $map[ $original ] ?? $translated;
}, 20, 3 );

add_filter( 'ngettext', function( $translated, $single, $plural, $number, $domain ) {
	if ( $domain !== 'woocommerce' ) return $translated;
	if ( strpos( $single, 'has been added to your cart' ) !== false ) {
		return $number === 1
			? '&ldquo;%s&rdquo; wurde in deinen Warenkorb gelegt.'
			: '&ldquo;%s&rdquo; wurden in deinen Warenkorb gelegt.';
	}
	return $translated;
}, 20, 5 );

// Fix: el script de sticky header de Elementor hace getElementById('main-header')
// que devuelve null en algunas páginas (checkout, etc.) → crash JS.
// Parcheamos el HTML generado añadiendo un guard antes del offsetTop.
add_action( 'template_redirect', function () {
    ob_start( function ( $html ) {
        // Fix 1: sticky header crash cuando #main-header no existe en la página
        $html = str_replace(
            "const stickyPoint = header.offsetTop;",
            "if ( ! header ) { return; }\n\t\tconst stickyPoint = header.offsetTop;",
            $html
        );
        // Fix 2: script triggerButton crash cuando el elemento no existe
        $html = str_replace(
            "document.getElementById('triggerButton').addEventListener",
            "var _tb=document.getElementById('triggerButton'); if(_tb) _tb.addEventListener",
            $html
        );
        return $html;
    } );
} );

add_filter('option_home', function($url) {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'padelprofishop.com') !== false) {
        return 'https://padelprofishop.com';
    }
    return $url;
});

add_filter('option_siteurl', function($url) {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'padelprofishop.com') !== false) {
        return 'https://padelprofishop.com';
    }
    return $url;
});
/* ============================================
   1. AÑADIR CAMPO EN CREAR CATEGORÍA PRODUCTOS
============================================ */
add_action('product_cat_add_form_fields', 'agregar_campo_h2_titulo_add');
function agregar_campo_h2_titulo_add() {
    ?>
    <div class="form-field">
        <label for="h2_titulo">H2 Título</label>
        <input type="text" name="h2_titulo" id="h2_titulo" value="">
        <p class="description">Introduce el título H2 para esta categoría.</p>
    </div>
    <?php
}

/* ============================================
   2. AÑADIR CAMPO EN EDITAR CATEGORÍA PRODUCTOS
============================================ */
add_action('product_cat_edit_form_fields', 'agregar_campo_h2_titulo_edit', 10, 2);
function agregar_campo_h2_titulo_edit($term, $taxonomy) {
    $h2_titulo = get_term_meta($term->term_id, 'h2_titulo', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="h2_titulo">H2 Título</label></th>
        <td>
            <input type="text" name="h2_titulo" id="h2_titulo" value="<?php echo esc_attr($h2_titulo); ?>">
            <p class="description">Introduce el título H2 para esta categoría.</p>
        </td>
    </tr>
    <?php
}

/* ============================================
   3. GUARDAR CAMPO EN CREAR Y EDITAR CATEGORÍA
============================================ */
add_action('created_product_cat', 'guardar_campo_h2_titulo', 10, 2);
add_action('edited_product_cat', 'guardar_campo_h2_titulo', 10, 2);

function guardar_campo_h2_titulo($term_id) {
    if (isset($_POST['h2_titulo'])) {
        update_term_meta($term_id, 'h2_titulo', sanitize_text_field($_POST['h2_titulo']));
    }
}

/* ============================================
   4. SHORTCODE PARA MOSTRAR EL H2 DE LA CATEGORÍA
   Uso: [categoria_h2]
============================================ */
function shortcode_categoria_h2() {
    if (!is_tax('product_cat')) return ''; // Solo funciona en categorías de productos

    $term_id = get_queried_object_id();
    $h2_titulo = get_term_meta($term_id, 'h2_titulo', true);

    if ($h2_titulo) {
        return '<h2>' . esc_html($h2_titulo) . '</h2>';
    }

    return ''; // No mostrar nada si no hay valor
}
add_shortcode('categoria_h2', 'shortcode_categoria_h2');

add_action('wp_head', function() {
    if ($_SERVER['HTTP_HOST'] === 'padelprofishop.com') {
        echo '<meta name="robots" content="noindex,nofollow">';
    }
}, 1);

// Añadir campos al crear categoría
add_action('product_cat_add_form_fields', 'agregar_campo_video_categoria');
function agregar_campo_video_categoria() {
    ?>
    <div class="form-field">
        <label for="video_youtube">Video de YouTube</label>
        <input type="text" name="video_youtube" id="video_youtube" value="" />
        <p>Pega aquí el enlace del video de YouTube</p>
    </div>
    <div class="form-field">
        <label for="titulo_video">Título del Video (H2)</label>
        <input type="text" name="titulo_video" id="titulo_video" value="" />
        <p>Título que aparecerá encima del video</p>
    </div>
    <div class="form-field">
        <label for="bullet_point_1">Punto Clave 1</label>
        <input type="text" name="bullet_point_1" id="bullet_point_1" value="" />
    </div>
    <div class="form-field">
        <label for="bullet_point_2">Punto Clave 2</label>
        <input type="text" name="bullet_point_2" id="bullet_point_2" value="" />
    </div>
    <div class="form-field">
        <label for="bullet_point_3">Punto Clave 3</label>
        <input type="text" name="bullet_point_3" id="bullet_point_3" value="" />
    </div>
    <?php
}

// Añadir campos al editar categoría
add_action('product_cat_edit_form_fields', 'editar_campo_video_categoria', 10, 2);
function editar_campo_video_categoria($term) {
    $video = get_term_meta($term->term_id, 'video_youtube', true);
    $titulo = get_term_meta($term->term_id, 'titulo_video', true);
    $bullet1 = get_term_meta($term->term_id, 'bullet_point_1', true);
    $bullet2 = get_term_meta($term->term_id, 'bullet_point_2', true);
    $bullet3 = get_term_meta($term->term_id, 'bullet_point_3', true);
    ?>
    <tr class="form-field">
        <th><label for="video_youtube">Video de YouTube</label></th>
        <td>
            <input type="text" name="video_youtube" id="video_youtube" value="<?php echo esc_attr($video); ?>" style="width:100%;" />
            <p>Pega aquí el enlace del video de YouTube</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="titulo_video">Título del Video (H2)</label></th>
        <td>
            <input type="text" name="titulo_video" id="titulo_video" value="<?php echo esc_attr($titulo); ?>" style="width:100%;" />
            <p>Título que aparecerá encima del video</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="bullet_point_1">Punto Clave 1</label></th>
        <td>
            <input type="text" name="bullet_point_1" id="bullet_point_1" value="<?php echo esc_attr($bullet1); ?>" style="width:100%;" />
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="bullet_point_2">Punto Clave 2</label></th>
        <td>
            <input type="text" name="bullet_point_2" id="bullet_point_2" value="<?php echo esc_attr($bullet2); ?>" style="width:100%;" />
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="bullet_point_3">Punto Clave 3</label></th>
        <td>
            <input type="text" name="bullet_point_3" id="bullet_point_3" value="<?php echo esc_attr($bullet3); ?>" style="width:100%;" />
        </td>
    </tr>
    <?php
}

// Guardar los campos
add_action('created_product_cat', 'guardar_campo_video_categoria');
add_action('edited_product_cat', 'guardar_campo_video_categoria');
function guardar_campo_video_categoria($term_id) {
    if (isset($_POST['video_youtube'])) {
        update_term_meta($term_id, 'video_youtube', sanitize_text_field($_POST['video_youtube']));
    }
    if (isset($_POST['titulo_video'])) {
        update_term_meta($term_id, 'titulo_video', sanitize_text_field($_POST['titulo_video']));
    }
    if (isset($_POST['bullet_point_1'])) {
        update_term_meta($term_id, 'bullet_point_1', sanitize_text_field($_POST['bullet_point_1']));
    }
    if (isset($_POST['bullet_point_2'])) {
        update_term_meta($term_id, 'bullet_point_2', sanitize_text_field($_POST['bullet_point_2']));
    }
    if (isset($_POST['bullet_point_3'])) {
        update_term_meta($term_id, 'bullet_point_3', sanitize_text_field($_POST['bullet_point_3']));
    }
}


// Shortcode para mostrar el video con título, bullet points y datos estructurados VideoObject
add_shortcode('category_video', 'mostrar_video_categoria');
function mostrar_video_categoria($atts) {
    $atts = shortcode_atts(array(
        'id' => ''
    ), $atts);
    
    $category_id = $atts['id'];
    
    if (empty($category_id) && is_product_category()) {
        $category = get_queried_object();
        $category_id = $category->term_id;
    }
    
    if (empty($category_id)) {
        return '<!-- No se encontró categoría -->';
    }
    
    $video     = get_term_meta($category_id, 'video_youtube', true);
    $titulo    = get_term_meta($category_id, 'titulo_video', true);
    $bullet1   = get_term_meta($category_id, 'bullet_point_1', true);
    $bullet2   = get_term_meta($category_id, 'bullet_point_2', true);
    $bullet3   = get_term_meta($category_id, 'bullet_point_3', true);
    
    $descripcion       = get_term_meta($category_id, 'descripcion_video', true);
    $fecha_publicacion = get_term_meta($category_id, 'fecha_publicacion_video', true);
    $thumbnail_url     = get_term_meta($category_id, 'thumbnail_video', true);
    $duracion          = get_term_meta($category_id, 'duracion_video', true);
    
    if (empty($video)) {
        return '<!-- No hay video configurado para esta categoría -->';
    }
    
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $video, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $video, $id)) {
        $video_id = $id[1];
    }
    
    $output = '';
    
    if ($video_id) {
        $thumbnail = !empty($thumbnail_url)
            ? esc_url($thumbnail_url)
            : 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        
        // ⭐ CORREGIDO: uploadDate en formato ISO 8601 completo
        if (!empty($fecha_publicacion)) {
            $upload_date = (strlen($fecha_publicacion) === 10)
                ? $fecha_publicacion . 'T00:00:00+01:00'
                : $fecha_publicacion;
        } else {
            $upload_date = get_the_date('Y-m-d\TH:i:sP');
        }
        
        $schema = array(
            '@context'         => 'https://schema.org',
            '@type'            => 'VideoObject',
            'name'             => !empty($titulo) ? $titulo : get_the_title(),
            'description'      => !empty($descripcion) ? $descripcion : (!empty($titulo) ? $titulo : get_the_title()),
            'thumbnailUrl'     => $thumbnail,
            'uploadDate'       => $upload_date, // ⭐ CORREGIDO
            'contentUrl'       => 'https://www.youtube.com/watch?v=' . $video_id,
            'embedUrl'         => 'https://www.youtube.com/embed/' . $video_id,
            'publisher'        => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => get_site_url(),
            ),
        );
        
        if (!empty($duracion)) {
            $schema['duration'] = $duracion;
        }
        
        $output .= '<script type="application/ld+json">'
            . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }
    
    $output .= '<div class="category-video-wrapper">';
    
    if (!empty($titulo)) {
        $output .= '<h2 class="video-titulo">' . esc_html($titulo) . '</h2>';
    }
    
    $output .= '<div class="category-video">';
    if ($video_id) {
        $output .= '<iframe width="100%" height="500" '
            . 'src="https://www.youtube.com/embed/' . $video_id . '" '
            . 'title="' . esc_attr($titulo) . '" '
            . 'frameborder="0" allowfullscreen loading="lazy"></iframe>';
    } else {
        $output .= $video;
    }
    $output .= '</div>';
    
    $bullets        = array($bullet1, $bullet2, $bullet3);
    $bullets_filled = array_filter($bullets);
    
    if (!empty($bullets_filled)) {
        $output .= '<ul class="video-bullet-points">';
        foreach ($bullets_filled as $bullet) {
            $output .= '<li>' . esc_html($bullet) . '</li>';
        }
        $output .= '</ul>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// ── Estilos CSS ───────────────────────────────────────────────────────────────
add_action('wp_head', 'category_video_custom_styles');
function category_video_custom_styles() {
    ?>
    <style>
        /* Contenedor principal */
        .category-video-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Título centrado */
        .category-video-wrapper .video-titulo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }

        /* Contenedor del video - responsivo 16:9 */
        .category-video {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .category-video iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Título dinámico sobre bullets */
        .video-bullets-title {
            text-align: center;
            font-size: 1.3em;
            font-weight: 700;
            margin: 20px 0 10px;
            color: #111;
        }

        /* Bullet points centrados */
        .video-bullet-points {
            list-style: none;
            padding: 0;
            margin: 0 !important;
            text-align: center;
        }

        .video-bullet-points li {
            margin-bottom: 15px;
            font-size: 1.1em;
            line-height: 1.6;
            text-align: center;
            display: block;
        }

        .video-bullet-points li:before {
            content: "✓ ";
            color: #00bf63;
            font-weight: bold;
            font-size: 1.2em;
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .category-video-wrapper .video-titulo {
                font-size: 1.5em;
                margin-bottom: 20px;
            }

            .video-bullet-points {
                max-width: 100%;
                padding: 0 10px;
            }

            .video-bullet-points li {
                font-size: 1em;
            }
        }
    </style>
    <?php
}

function padelprofi_custom_styles() {
    wp_enqueue_style(
        'padelprofi-custom-css',
        get_stylesheet_directory_uri() . '/css/custom-padel.css',
        array(),
        '1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'padelprofi_custom_styles' );


// Forzar que el título del loop sea un <span> y que solo se pinte una vez
add_action( 'init', function() {

    // Elimina TODAS las funciones enganchadas al hook del título
    remove_all_actions( 'woocommerce_shop_loop_item_title' );

    // Añade nuestro título personalizado
    add_action( 'woocommerce_shop_loop_item_title', function() {
        echo '<span class="woocommerce-loop-product__title">' . get_the_title() . '</span>';
    }, 10 );

});




// Cargar login.css personalizado desde el tema hijo
function cargar_login_css_personalizado() {
wp_enqueue_style('login-personalizado', get_stylesheet_directory_uri() . '/login.css', array(), '1.7');
}
add_action('login_enqueue_scripts', 'cargar_login_css_personalizado');

// Shortcode específico para categorías Black Friday
function render_blackfriday_cats_slider() {
    // Definir las categorías en el orden deseado (solo las 3 primeras)
    $category_slugs = array(
        'padelschlaeger-black-friday',
        'padelbaelle-black-friday',
        'padeltaschen-black-friday'
    );
    
    // Preparar array de categorías para el slider
    $categories = array();
    
    foreach ($category_slugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        
        if ($term && !is_wp_error($term)) {
            $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            $image_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : wc_placeholder_img_src();
            $cat_link = get_term_link($term);
            
            $categories[] = array(
                'title'   => $term->name,
                'image'   => $image_url,
                'url'     => $cat_link,
                'term_id' => $term->term_id
            );
        }
    }
    
    // Si no hay categorías, no mostrar nada
    if (empty($categories)) {
        return '';
    }
    
    ob_start();
    ?>
    <section class="category-slider-section blackfriday-cats-slider">
        <div class="container">

            <div class="swiper category-swiper blackfriday-swiper-center">
                <div class="swiper-wrapper">
                    <?php foreach ($categories as $cat): ?>
                        <div class="swiper-slide">
                            <a href="<?php echo esc_url($cat['url']); ?>" class="category-card">
                                <div class="category-image-circle">
                                    <img src="<?php echo esc_url($cat['image']); ?>" 
                                         alt="<?php echo esc_attr($cat['title']); ?>"
                                         loading="lazy">
                                </div>
                                <h3 class="category-title"><?php echo esc_html($cat['title']); ?></h3>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Navegación -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                
                <!-- Paginación -->
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('.blackfriday-cats-slider .category-swiper', {
                slidesPerView: 3,
                spaceBetween: 0, // sin espacio entre elementos
                loop: false,
                centeredSlides: false,
                navigation: {
                    nextEl: '.blackfriday-cats-slider .swiper-button-next',
                    prevEl: '.blackfriday-cats-slider .swiper-button-prev',
                },
                pagination: {
                    el: '.blackfriday-cats-slider .swiper-pagination',
                    clickable: true,
                },
                breakpoints: {
                    640: {
                        slidesPerView: 3,
                        spaceBetween: 0,
                        centeredSlides: false,
                    },
                    768: {
                        slidesPerView: 3,
                        spaceBetween: 0,
                        centeredSlides: false,
                    },
                    1024: {
                        slidesPerView: 3,
                        spaceBetween: 0,
                        centeredSlides: false,
                    }
                }
            });
        }
    });
    </script>

    <style>
    .blackfriday-cats-slider {
        padding: 15px 0; /* menos espacio vertical arriba/abajo */
    }
    
    .blackfriday-cats-slider .slider-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
    }
    
    /* Evitar espacios entre slides que mete Elementor/Swiper */
    .blackfriday-cats-slider .swiper-slide {
        margin-right: 0 !important;
        margin-left: 0 !important;
        padding: 0 !important;
    }

    .blackfriday-swiper-center .swiper-wrapper {
        justify-content: flex-start !important;
        gap: 0 !important;
    }
    
    .blackfriday-cats-slider .category-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
    }
    
    .blackfriday-cats-slider .category-image-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        background: #f5f5f5;
        border: 3px solid #e0e0e0;
        margin-bottom: 10px;
    }
    
    .blackfriday-cats-slider .category-image-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .blackfriday-cats-slider .category-title {
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        color: #333;
        margin: 0;
        max-width: 140px;
        line-height: 1.3;
    }
    
    .blackfriday-cats-slider .swiper-button-next,
    .blackfriday-cats-slider .swiper-button-prev {
        color: #333;
    }
    
    .blackfriday-cats-slider .swiper-button-next:after,
    .blackfriday-cats-slider .swiper-button-prev:after {
        font-size: 24px;
    }
    
    .blackfriday-cats-slider .swiper-pagination-bullet {
        background: #333;
    }
    
    .blackfriday-cats-slider .swiper-pagination-bullet-active {
        background: #000;
    }
    
    @media (max-width: 767px) {
        .blackfriday-cats-slider .category-image-circle {
            width: 100px;
            height: 100px;
        }
        
        .blackfriday-cats-slider .category-title {
            font-size: 12px;
        }
        
        .blackfriday-cats-slider .swiper-pagination {
            bottom: -20px;
        }
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('blackfriday_slider', 'render_blackfriday_cats_slider');









// 1) Quitamos el botón por defecto del loop (si no lo necesitas)
add_action( 'after_setup_theme', 'mi_quitar_add_to_cart_loop', 20 );
function mi_quitar_add_to_cart_loop() {
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
}

// 2) Quitamos el thumbnail por defecto y lo reemplazamos por uno con hover de imagen
add_action( 'after_setup_theme', 'mi_reemplazar_thumbnail_loop', 20 );
function mi_reemplazar_thumbnail_loop() {
    // quitar la salida por defecto de la miniatura
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
    // añadir la nuestra (misma prioridad 10)
    add_action( 'woocommerce_before_shop_loop_item_title', 'mi_thumbnail_con_overlay', 10 );
}

function mi_thumbnail_con_overlay() {
    global $product;
    if ( ! $product ) {
        return;
    }
    
    // Abrimos el wrapper del thumbnail
    echo '<div class="mi-thumb-wrap">';
        
        // Imagen principal
        $image_id = $product->get_image_id();
        $image_size = 'woocommerce_thumbnail';
        
        if ( $image_id ) {
            echo '<div class="mi-imagen-principal">';
            echo wp_get_attachment_image( $image_id, $image_size );
            echo '</div>';
        }
        
        // ✅ Segunda imagen (de la galería) - MANTENER PARA HOVER
        $gallery_image_ids = $product->get_gallery_image_ids();
        if ( ! empty( $gallery_image_ids ) ) {
            $second_image_id = $gallery_image_ids[0]; // Primera imagen de la galería
            echo '<div class="mi-imagen-hover">';
            echo wp_get_attachment_image( $second_image_id, $image_size );
            echo '</div>';
        }
        
        // ❌ OVERLAY CON BOTÓN ELIMINADO - AHORA USAMOS EL BOTÓN FIJO NARANJA
        /*
        if ( $product->is_purchasable() ) {
            $url   = esc_url( $product->add_to_cart_url() );
            $id    = absint( $product->get_id() );
            $label = esc_html__( 'In den Warenkorb', 'tu-textdomain' );
            ?>
            <div class="mi-overlay-add-to-cart">
                <a href="<?php echo $url; ?>"
                   data-quantity="1"
                   data-product_id="<?php echo $id; ?>"
                   data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                   class="button mi-btn-add-to-cart ajax_add_to_cart">
                   <?php echo $label; ?>
                </a>
            </div>
            <?php
        }
        */
        
    echo '</div>';
}

add_action('template_redirect', function() {
    global $wp_query;
    
    // Solo actuar si estamos en una categoría de producto con un número directo
    if (is_tax('product_cat') && isset($wp_query->query_vars['page'])) {
        $page_num = $wp_query->query_vars['page'];
        
        // Solo procesar si es un número (evitar procesar slugs con letras)
        if (is_numeric($page_num) && $page_num > 0) {
            // Verificar si existe una categoría real con ese slug numérico
            $term_exists = term_exists($page_num, 'product_cat');
            
            // Si NO existe, forzar 404
            if (!$term_exists) {
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
        }
    }
}, 5);

// Añadir campo personalizado "tags" al agregar categoría
add_action('category_add_form_fields', function() {
    ?>
    <div class="form-field">
        <label for="cat_tags">Etiquetas personalizadas</label>
        <input type="text" name="cat_tags" id="cat_tags" placeholder="ej: etiqueta1, etiqueta2, etiqueta3">
    </div>
    <?php
});

// Añadir campo personalizado "tags" al editar categoría
add_action('category_edit_form_fields', function($term) {
    $tags = get_term_meta($term->term_id, 'cat_tags', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="cat_tags">Etiquetas personalizadas</label></th>
        <td>
            <input type="text" name="cat_tags" id="cat_tags" value="<?php echo esc_attr($tags); ?>" class="regular-text">
            <p class="description">Separadas por comas.</p>
        </td>
    </tr>
    <?php
});

// Guardar el valor
add_action('edited_category', 'save_cat_tags');
add_action('created_category', 'save_cat_tags');
function save_cat_tags($term_id) {
    if (isset($_POST['cat_tags'])) {
        update_term_meta($term_id, 'cat_tags', sanitize_text_field($_POST['cat_tags']));
    }
}






/*funcion para que coja el precio del producto en la pagina de standort */
function mostrar_precio_y_descuento_producto() {
    $ubicacion = pods('standort', get_the_ID());
    $producto = $ubicacion->field('meistverkauftes_produkt');

    if ($producto) {
        $precio_regular = get_post_meta($producto['ID'], '_regular_price', true);
        $precio_oferta = get_post_meta($producto['ID'], '_sale_price', true);

        $output = '';

        if ($precio_oferta && $precio_regular) {
            $descuento = (($precio_regular - $precio_oferta) / $precio_regular) * 100;

            $output .= '<span style="color: #BF0019; font-size: 20px; font-weight: bold;">' . esc_html($precio_oferta) . ' €</span> ';
            $output .= '<span style="text-decoration: line-through; color: gray; font-size: 16px;">' . esc_html($precio_regular) . ' €</span> ';
            $output .= '<span style="background-color: #BF0019; color: white; padding: 2px 5px; border-radius: 3px; font-size: 14px;">-' . round($descuento, 1) . '%</span>';
        } elseif ($precio_regular) {
            $output .= esc_html($precio_regular) . ' €';
        }

        return $output;
    } else {
        return '<p>No se encontró un producto relacionado.</p>';
    }
}
add_shortcode('mostrar_precio_producto', 'mostrar_precio_y_descuento_producto');




add_filter('action_scheduler_retention_period', function() {
    return DAY_IN_SECONDS * 7; // Mantener registros solo 7 días
});

// Google Pay / Apple Pay envían la dirección combinada en billing_address_1
// (ej. "Musterstraße 5") con billing_address_2 vacío. Extraer el número de casa
// antes de que WC valide los campos obligatorios, prioridad 5 = antes de WC.
add_action( 'woocommerce_checkout_process', function() {
    $addr1 = trim( $_POST['billing_address_1'] ?? '' );
    $addr2 = trim( $_POST['billing_address_2'] ?? '' );
    if ( $addr1 && '' === $addr2 ) {
        if ( preg_match( '/^(.+?)\s+(\d+[\w\s\-\/]*)$/u', $addr1, $m ) ) {
            $_POST['billing_address_1'] = trim( $m[1] );
            $_POST['billing_address_2'] = trim( $m[2] );
        }
    }
    $saddr1 = trim( $_POST['shipping_address_1'] ?? '' );
    $saddr2 = trim( $_POST['shipping_address_2'] ?? '' );
    if ( $saddr1 && '' === $saddr2 ) {
        if ( preg_match( '/^(.+?)\s+(\d+[\w\s\-\/]*)$/u', $saddr1, $m ) ) {
            $_POST['shipping_address_1'] = trim( $m[1] );
            $_POST['shipping_address_2'] = trim( $m[2] );
        }
    }
}, 5 );

// PayPal PPCP usa SET_PROVIDED_ADDRESS y necesita shipping_address_1 en sesión.
// Este hook dispara DESPUÉS de que WC ya procesó y guardó los datos del cliente,
// por lo que no puede ser sobreescrito por set_props(). Si billing está relleno
// pero shipping está vacío, copiamos billing → shipping y guardamos.
add_action( 'woocommerce_after_calculate_totals', function() {
    if ( ! WC()->customer ) return;
    if ( WC()->customer->get_shipping_address_1() ) return;
    $addr1 = WC()->customer->get_billing_address_1();
    if ( ! $addr1 ) return;
    WC()->customer->set_shipping_first_name( WC()->customer->get_billing_first_name() );
    WC()->customer->set_shipping_last_name( WC()->customer->get_billing_last_name() );
    WC()->customer->set_shipping_address_1( $addr1 );
    WC()->customer->set_shipping_address_2( WC()->customer->get_billing_address_2() );
    WC()->customer->set_shipping_city( WC()->customer->get_billing_city() );
    WC()->customer->set_shipping_postcode( WC()->customer->get_billing_postcode() );
    WC()->customer->set_shipping_country( WC()->customer->get_billing_country() );
    WC()->customer->set_shipping_state( WC()->customer->get_billing_state() );
    WC()->customer->save();
}, 10 );


//añade la plantilla slider pods para ubicaciones
function render_pods_carousel() {
    ob_start();
include get_template_directory() . '/carousel-slider/loop/prod-carousel-pods.php';
    return ob_get_clean();
}
add_shortcode('pods_carousel', 'render_pods_carousel');

function enqueue_slick_scripts() {
    // Cargar Slick solo en la home y páginas de categoría donde se usa el carrusel
    if (!is_front_page() && !is_product_category()) return;
    $slick_ver = '1.8.1';
    wp_enqueue_style('slick-css', get_stylesheet_directory_uri() . '/assets/plugins/slick/slick.css', array(), $slick_ver);
    wp_enqueue_style('slick-theme-css', get_stylesheet_directory_uri() . '/assets/plugins/slick/slick-theme.css', array(), $slick_ver);
    wp_enqueue_script('slick-js', get_stylesheet_directory_uri() . '/assets/plugins/slick/slick.min.js', array('jquery'), $slick_ver, true);
}
add_action('wp_enqueue_scripts', 'enqueue_slick_scripts');




// Shortcode para mostrar el título de la entrada con un SVG delante
function wc_entrada_titulo_con_svg_shortcode() {
    // Verificar si estamos en una entrada
    if (is_singular('post')) {
        // Código SVG personalizado
        $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
        </svg>';

        // Obtener el título de la entrada
        $titulo_entrada = get_the_title();

        // Retornar el SVG seguido del título
        return "<div class='entrada-titulo'>{$svg_icon} <span>{$titulo_entrada}</span></div>";
    } else {
        return '';
    }
}
add_shortcode('entry_title_icon', 'wc_entrada_titulo_con_svg_shortcode');

function preload_imagen_critica_movil() {
    if (wp_is_mobile()) {
        echo '<link rel="preload" href="https://padelprofideutschland.de/wp-content/uploads/2025/01/padel-profi-scaled_1_11zon-1.webp" as="image" type="image/webp" importance="high">';
    }
}


add_action('wp_footer', function() {
    if (is_front_page()) { // o is_page('inicio') si no es la home
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Espera a que se cargue todo el carrusel
            setTimeout(function() {
                // 1️⃣ Eliminar el icono
                document.querySelectorAll('.free-shipping-loop img[alt="Free Shipping"]').forEach(el => {
                    el.remove();
                });

                // 2️⃣ Añadir margen izquierdo al texto
                document.querySelectorAll('.free-shipping-loop .textInLoop').forEach(el => {
                    el.style.marginLeft = '5px';
                });
            }, 800);

        });
        </script>
        <?php
    }
});

add_action('wp_head', 'preload_imagen_critica_movil');


function mostrar_categoria_secundaria() {
    $categories = get_the_category();
    
    // Filtrar categorías para excluir la principal
    if (!empty($categories)) {
        foreach ($categories as $category) {
            if ($category->slug !== 'blog') { // Excluye la categoría principal "Blog"
                return esc_html($category->name); // Devuelve el nombre de la categoría secundaria
            }
        }
    }

    return ''; // Si no hay categorías secundarias
}
add_shortcode('categoria_secundaria', 'mostrar_categoria_secundaria');

// Agregar un campo personalizado en la configuración del producto
add_action('woocommerce_product_options_general_product_data', 'add_progress_bar_fields');
function add_progress_bar_fields() {
    // Checkbox para mostrar/ocultar la barra
    woocommerce_wp_checkbox(array(
        'id'            => '_show_progress_bar',
        'label'         => __('Mostrar barra de ofertas stock', 'woocommerce'),
        'description'   => __('Activar esta opción para mostrar ofertas.', 'woocommerce'),
    ));
    
    echo '<div id="progress_bar_title_field" style="display: none;">';
    woocommerce_wp_text_input(array(
        'id'            => '_progress_bar_title',
        'label'         => __('Título de la oferta', 'woocommerce'),
        'description'   => __('Texto del título (H2) que aparecerá en la barra de progreso.', 'woocommerce'),
        'desc_tip'      => true,
    ));
    echo '</div>';
}

// Guardar los valores de los campos personalizados
add_action('woocommerce_process_product_meta', 'save_progress_bar_fields');
function save_progress_bar_fields($post_id) {
    
    $show_progress_bar = isset($_POST['_show_progress_bar']) ? 'yes' : 'no';
    update_post_meta($post_id, '_show_progress_bar', $show_progress_bar);

    if (isset($_POST['_progress_bar_title'])) {
        update_post_meta($post_id, '_progress_bar_title', sanitize_text_field($_POST['_progress_bar_title']));
    }
}

// Shortcode semáforo de stock automático
function shortcode_dynamic_stock_bar($atts) {
    global $product;

    if (!$product) {
        $product = wc_get_product(get_the_ID());
    }
    if (!$product) return '';

    $stock_status = $product->get_stock_status();
    $managing     = $product->managing_stock();
    $qty          = $product->get_stock_quantity();

    $dot = 'display:inline-block;width:12px;height:12px;min-width:12px;min-height:12px;border-radius:50%;vertical-align:middle;margin-right:6px;';

    $style = '<style>
    @keyframes ppPulseGreen{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.5)}50%{box-shadow:0 0 0 8px rgba(34,197,94,0)}}
    @keyframes ppPulseOrange{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,0.5)}50%{box-shadow:0 0 0 8px rgba(245,158,11,0)}}
    @keyframes ppPulseRed{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.5)}50%{box-shadow:0 0 0 8px rgba(239,68,68,0)}}
    </style>';

    // Producto agotado
    if ($stock_status === 'outofstock') {
        return $style . '<p class="pp-stock-bar"><span style="' . $dot . 'background:#ef4444;animation:ppPulseRed 2s infinite;"></span><span style="color:#ef4444;">Produkt ausverkauft</span></p>';
    }

    // Si gestiona stock y quedan entre 1 y 5
    if ($managing && $qty !== null && $qty >= 1 && $qty <= 5) {
        return $style . '<p class="pp-stock-bar"><span style="' . $dot . 'background:#f59e0b;animation:ppPulseOrange 2s infinite;"></span><span style="color:#f59e0b;">Nur noch ' . esc_html($qty) . ' verfügbar</span></p>';
    }

    // Más de 5 o no gestiona stock pero está en stock
    if ($stock_status === 'instock') {
        return $style . '<p class="pp-stock-bar"><span style="' . $dot . 'background:#22c55e;animation:ppPulseGreen 2s infinite;"></span><span style="color:#22c55e;">Produkt verfügbar</span></p>';
    }

    return '';
}
add_shortcode('dynamic_stock_bar', 'shortcode_dynamic_stock_bar');



add_shortcode('product_specs', 'shortcode_product_specs');
function shortcode_product_specs() {
    global $product;
    if (!$product) {
        $product = wc_get_product(get_queried_object_id());
    }
    if (!$product) return '';

    // ── Solo mostrar en estas categorías ─────────────────────────────────────
    $allowed_categories = array('padelschlaeger', 'padel-set');
    $product_cats = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));
    if (empty(array_intersect($allowed_categories, $product_cats))) return '';
    // ─────────────────────────────────────────────────────────────────────────

    $specs = array(
        'pa_form' => array('label' => 'Form', 'mobile' => true, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>'),
        'pa_spieltyp' => array('label' => 'Spieltyp', 'mobile' => true, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>'),
        'pa_spielniveau' => array('label' => 'Spielniveau', 'mobile' => true, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'),
        'pa_balance' => array('label' => 'Balance', 'mobile' => false, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="4 8 12 3 20 8"/><line x1="4" y1="8" x2="4" y2="14"/><line x1="20" y1="8" x2="20" y2="14"/><rect x="1" y="14" width="6" height="4" rx="1"/><rect x="17" y="14" width="6" height="4" rx="1"/></svg>'),
        'pa_kernmaterial' => array('label' => 'Material', 'mobile' => false, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><path d="M2 6l4-2 4 2 4-2 4 2 4-2v14l-4 2-4-2-4 2-4-2-4 2z"/></svg>'),
        'gewicht' => array('label' => 'Gewicht', 'mobile' => true, 'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe6100" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>'),
    );

    $items = array();
    $attributes = $product->get_attributes();

    foreach ($specs as $slug => $spec) {
        if (!isset($attributes[$slug])) continue;
        $attr = $attributes[$slug];
        if ($attr->is_taxonomy()) {
            $values = wc_get_product_terms($product->get_id(), $slug, array('fields' => 'names'));
        } else {
            $values = $attr->get_options();
        }
        if (!empty($values)) {
            $items[] = array(
                'label'  => $spec['label'],
                'value'  => implode(', ', $values),
                'icon'   => $spec['icon'],
                'mobile' => $spec['mobile'],
            );
        }
    }

    if (empty($items)) return '';

    $out = '<style>
        .pp-specs-grid{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:10px!important;margin:15px 0!important;max-width:100%!important}
        .pp-spec-card{display:flex!important;align-items:center!important;gap:10px!important;background:#f8f8f8!important;border-radius:10px!important;padding:10px 12px!important;border:1px solid #eee!important}
        .pp-spec-icon{flex-shrink:0;width:34px;height:34px;display:flex!important;align-items:center!important;justify-content:center!important;background:#fff!important;border-radius:8px!important;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .pp-spec-icon svg{display:block;width:18px;height:18px}
        .pp-spec-info{display:flex!important;flex-direction:column!important;gap:1px;min-width:0}
        .pp-spec-label{font-size:10px!important;color:#999!important;font-weight:400!important;text-transform:uppercase;letter-spacing:.5px}
        .pp-spec-value{font-size:12px!important;color:#333!important;font-weight:600!important;word-break:break-word}
        .pp-specs-grid{justify-items:stretch}
        @media(max-width:767px){
            .pp-specs-grid{grid-template-columns:1fr!important}
            .pp-spec-card.desktop-only{display:none!important}
        }
    </style>';

    $out .= '<div class="pp-specs-grid">';
    foreach ($items as $item) {
        $mobile_class = $item['mobile'] ? '' : ' desktop-only';
        $out .= '<div class="pp-spec-card' . $mobile_class . '">';
        $out .= '<div class="pp-spec-icon">' . $item['icon'] . '</div>';
        $out .= '<div class="pp-spec-info">';
        $out .= '<span class="pp-spec-label">' . esc_html($item['label']) . '</span>';
        $out .= '<span class="pp-spec-value">' . esc_html($item['value']) . '</span>';
        $out .= '</div></div>';
    }
    $out .= '</div>';

    return $out;
}

// Open Sans ya se carga localmente via WOFF2 (preload_opensans_woff2 + use_opensans_woff2)
// No cargar desde Google Fonts para evitar solicitud externa duplicada





// Registrar el shortcode para mostrar productos de cross-sell
function custom_cross_sell_shortcode() {
    ob_start();
    
    // Verificar si estamos en la página del carrito
    if ( is_cart() ) {
        // Obtener el carrito
        $cart = WC()->cart;
        
        // Mostrar productos de cross-sell
        if ( $cart ) {
            // Iterar sobre los productos en el carrito
            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                $product_id = $cart_item['product_id'];
                $product = wc_get_product( $product_id );

                // Obtener los productos de cross-sell
                $cross_sells = $product->get_cross_sells();

                // Mostrar los productos de cross-sell
                if ( ! empty( $cross_sells ) ) {
                    $args = array(
                        'posts_per_page' => -1,
                        'post_type'      => 'product',
                        'post__in'       => $cross_sells,
                        'orderby'        => 'post__in'
                    );

                    $loop = new WP_Query( $args );

                    if ( $loop->have_posts() ) {
                        echo '<ul class="cross-sell-products">';
                        while ( $loop->have_posts() ) : $loop->the_post();
                            wc_get_template_part( 'content', 'product' );
                        endwhile;
                        echo '</ul>';
                    }

                    wp_reset_postdata();
                }
            }
        }
    }

    return ob_get_clean();
}
add_shortcode( 'custom_cross_sell', 'custom_cross_sell_shortcode' );




function enqueue_custom_carousel_styles() {
    $ver = wp_get_theme()->get('Version');

    wp_enqueue_style( 'custom-carousel-styles', get_stylesheet_directory_uri() . '/assets/css/carousel-product.css', array(), $ver );
    wp_enqueue_style( 'custom-review-styles', get_stylesheet_directory_uri() . '/assets/css/custom-styles.css', array(), $ver );
    wp_enqueue_style( 'custom-product-detail', get_stylesheet_directory_uri() . '/assets/css/product-detail.css', array(), $ver );
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_carousel_styles', 100 );


/**
 * Enqueue Swiper.js scripts and styles for the subcategories slider
 */
function enqueue_subcategories_slider_assets() {
    $ver = wp_get_theme()->get('Version');
    // Swiper CSS/JS ya cargado globalmente (v11 via swiper-css/swiper-js)
    // Encolar el script de inicialización con dependencia al swiper global
    wp_enqueue_script( 'subcat-slider-init', get_stylesheet_directory_uri() . '/assets/js/subcat-slider-init.js', array( 'swiper-js' ), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_subcategories_slider_assets' );


function wc_get_rating_html_2( $rating, $count = 0 ) {
	$html = '';

	if ( 0 < $rating ) {
		/* translators: %s: rating */
		$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
		$html  = '<div class="star-rating" role="img" aria-label="' . esc_attr( $label ) . '">' . wc_get_star_rating_html( $rating, $count ) . '</div>';
	}

	return apply_filters( 'woocommerce_product_get_rating_html_2', $html, $rating, $count );
}


// Filtro para modificar la visualización del rating y número de opiniones
add_filter( 'woocommerce_product_get_rating_html_2', 'custom_reviews_rating_output', 10, 3 );

function custom_reviews_rating_output( $html, $rating = 0, $count = 0 ) {
	global $product;

	$count  = $product->get_review_count();

    if ( $rating >= 0 ) {
        $html  = '<div class="rating-container">';
        $html .= '<span class="rating-value"><strong>' . esc_html( $rating ) . '/5</strong></span>'; // Puntuación
        $html .= '<span class="star-icon">' . custom_star_svg() . '</span>'; // Estrella custom SVG
//         if ( $count > 0 ) { Condicional del reviews para que aparezca solo sí es mayor a cero
            $html .= '<span class="review-count">' . esc_html( $count ) . ' reviews</span>'; // Número de opiniones
//         }
        $html .= '</div>';
    }
    return $html;
}

// Icono SVG star for reviews with styles custom
function custom_star_svg(){
	return '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#ffa90d" viewBox="0 0 16 16">
        <path d="M3.612 15.443c-.396.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.32-.158-.888.283-.95l4.898-.696 2.182-4.327c.197-.39.73-.39.927 0l2.182 4.327 4.898.696c.441.062.612.63.283.95l-3.523 3.356.83 4.73c.078.443-.35.79-.746.592L8 13.187l-4.389 2.256z"/>
    </svg>';
}


function custom_title_producto_loop() {

	global $product;
	if ( $product->is_on_sale()) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters(
			'woocommerce_sale_flash',
			'<div class="trending-label">' . __( 'Sale!', 'carousel-slider' ) . '</div>',
			get_post( $product->get_id() ),
			$product
		);
	}

    // Aquí puedes personalizar cómo quieres mostrar el título
    echo '<h2 class="woocommerce-loop-product__title">' . get_the_title() . '</h2>';
}

// Eliminar la acción original
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

// Agregar la nueva acción
add_action( 'woocommerce_shop_loop_item_title', 'custom_title_producto_loop', 10 );

add_action('pre_get_posts', function($query) {
    if ($query->is_search() && $query->is_main_query() && !is_admin()) {
        $query->set('posts_per_page', 24); // ajusta el número que quieras
    }
});

// Add custom fields to the 'product_cat' edit screen
function add_custom_subcategories_fields($term) {
    $custom_subcategories_enabled = get_term_meta($term->term_id, 'custom_subcategories_enabled', true);
    $custom_subcategories = get_term_meta($term->term_id, 'custom_subcategories', true);
    if (!is_array($custom_subcategories)) {
        $custom_subcategories = array();
    }
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="custom_subcategories_enabled"><?php _e('Enable Custom Subcategories'); ?></label></th>
        <td>
            <input type="checkbox" name="custom_subcategories_enabled" id="custom_subcategories_enabled" value="1" <?php checked($custom_subcategories_enabled, '1'); ?> />
            <p class="description"><?php _e('Check to use custom subcategories for this category.'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="custom_subcategories"><?php _e('Custom Subcategories'); ?></label></th>
        <td>
            <div id="custom-subcategories-wrapper">
                <?php
                if (!empty($custom_subcategories)) {
                    foreach ($custom_subcategories as $index => $subcat) {
                        ?>
                        <div class="custom-subcategory-item">
                            <p>
                                <label>Title:<br/>
                                <input type="text" name="custom_subcategories[<?php echo $index; ?>][title]" value="<?php echo esc_attr($subcat['title']); ?>" /></label>
                            </p>
                            <p>
                                <label>Image URL:<br/>
                                <input type="text" name="custom_subcategories[<?php echo $index; ?>][image]" value="<?php echo esc_url($subcat['image']); ?>" /></label>
                            </p>
                            <p>
                                <label>URL:<br/>
                                <input type="text" name="custom_subcategories[<?php echo $index; ?>][url]" value="<?php echo esc_url($subcat['url']); ?>" /></label>
                            </p>
                            <p>
                                <label>H2:<br/>
                                <input type="text" name="custom_subcategories[<?php echo $index; ?>][h2]" value="<?php echo esc_attr($subcat['h2']); ?>" /></label>
                            </p>
                            <p>
                                <a href="#" class="remove-custom-subcategory">Remove</a>
                            </p>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <p>
                <a href="#" id="add-custom-subcategory" class="button">Add Subcategory</a>
            </p>
            <p class="description"><?php _e('Add custom subcategories to display in the slider.'); ?></p>
        </td>
    </tr>
    <script>
    jQuery(document).ready(function($){
        var index = <?php echo count($custom_subcategories); ?>;
        $('#add-custom-subcategory').on('click', function(e){
            e.preventDefault();
            var html = '<div class="custom-subcategory-item">';
            html += '<p><label>Title:<br/><input type="text" name="custom_subcategories['+index+'][title]" value="" /></label></p>';
            html += '<p><label>Image URL:<br/><input type="text" name="custom_subcategories['+index+'][image]" value="" /></label></p>';
            html += '<p><label>URL:<br/><input type="text" name="custom_subcategories['+index+'][url]" value="" /></label></p>';
            html += '<p><label>H2:<br/><input type="text" name="custom_subcategories['+index+'][h2]" value="" /></label></p>';
            html += '<p><a href="#" class="remove-custom-subcategory">Remove</a></p>';
            html += '</div>';
            $('#custom-subcategories-wrapper').append(html);
            index++;
        });
        $(document).on('click', '.remove-custom-subcategory', function(e){
            e.preventDefault();
            $(this).closest('.custom-subcategory-item').remove();
        });
    });
    </script>
    <?php
}
add_action('product_cat_edit_form_fields', 'add_custom_subcategories_fields', 10, 1);









function get_custom_h2_field() {
    // Obtén el ID de la categoría actual
    $category_id = get_queried_object_id();

    // Obtén las subcategorías personalizadas
    $custom_subcategories = get_term_meta($category_id, 'custom_subcategories', true);

    if (!empty($custom_subcategories)) {
        ob_start();
        foreach ($custom_subcategories as $subcat) {
            if (!empty($subcat['h2'])) {
                // Muestra el campo H2
                echo '<h2>' . esc_html($subcat['h2']) . '</h2>';
            }
        }
        return ob_get_clean();
    }

    return ''; 
}
add_shortcode('custom_h2', 'get_custom_h2_field');



// Save the custom fields when the category is edited
function save_custom_subcategories_fields($term_id) {
    if (isset($_POST['custom_subcategories_enabled'])) {
        update_term_meta($term_id, 'custom_subcategories_enabled', '1');
    } else {
        delete_term_meta($term_id, 'custom_subcategories_enabled');
    }
    if (isset($_POST['custom_subcategories']) && is_array($_POST['custom_subcategories'])) {
        $custom_subcategories = array_values($_POST['custom_subcategories']); // Reindex array
        update_term_meta($term_id, 'custom_subcategories', $custom_subcategories);
    } else {
        delete_term_meta($term_id, 'custom_subcategories');
    }
}
add_action('edited_product_cat', 'save_custom_subcategories_fields', 10, 1);


function render_product_subcategories_slider() {
    $category_id = get_queried_object_id();
    
    // Metas personalizadas (si existen)
    $custom_subcategories_enabled = get_term_meta( $category_id, 'custom_subcategories_enabled', true );
    $custom_subcategories         = get_term_meta( $category_id, 'custom_subcategories', true );
    
    if ( $custom_subcategories_enabled && ! empty( $custom_subcategories ) ) {
        $subcategories = $custom_subcategories;
    } else {
        $args  = array(
            'taxonomy'   => 'product_cat',
            'parent'     => $category_id,
            'hide_empty' => false,
        );
        $terms = get_terms( $args );
        
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }
        
        $subcategories = array();
        foreach ( $terms as $term ) {
            $thumbnail_id     = get_term_meta( $term->term_id, 'thumbnail_id', true );
            $image_url        = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : wc_placeholder_img_src();
            $subcategory_link = get_term_link( $term->term_id );
            
            $subcategories[] = array(
                'title' => $term->name,
                'image' => $image_url,
                'url'   => $subcategory_link,
                'name'  => $term->name,
            );
        }
        
        // Ordenar: primero 2026, luego marcas en orden específico, luego el resto
        usort( $subcategories, function( $a, $b ) {
            $a_name_lower = strtolower( $a['name'] );
            $b_name_lower = strtolower( $b['name'] );
            
            // Verificar si contienen "2026"
            $a_has_2026 = stripos( $a_name_lower, '2026' ) !== false;
            $b_has_2026 = stripos( $b_name_lower, '2026' ) !== false;
            
            // Si una tiene 2026 y la otra no, la de 2026 va primero
            if ( $a_has_2026 && ! $b_has_2026 ) {
                return -1;
            }
            if ( ! $a_has_2026 && $b_has_2026 ) {
                return 1;
            }
            
            // Si ambas tienen 2026, orden alfabético entre ellas
            if ( $a_has_2026 && $b_has_2026 ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
            
            // Ninguna tiene 2026, aplicar orden de marcas
            $brand_order = array( 'adidas', 'nox', 'bullpadel', 'babolat' );
            
            $a_brand_index = -1;
            $b_brand_index = -1;
            
            foreach ( $brand_order as $index => $brand ) {
                if ( stripos( $a_name_lower, $brand ) !== false ) {
                    $a_brand_index = $index;
                }
                if ( stripos( $b_name_lower, $brand ) !== false ) {
                    $b_brand_index = $index;
                }
            }
            
            // Si ambas están en el array de marcas prioritarias
            if ( $a_brand_index !== -1 && $b_brand_index !== -1 ) {
                return $a_brand_index - $b_brand_index;
            }
            
            // Si solo una está en el array, esa va primero
            if ( $a_brand_index !== -1 ) {
                return -1;
            }
            if ( $b_brand_index !== -1 ) {
                return 1;
            }
            
            // Ninguna está en el array, orden alfabético
            return strcasecmp( $a['name'], $b['name'] );
        });
    }
    
    if ( empty( $subcategories ) ) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="subcat-slider-wrapper">
        <div class="subcat-slider-container swiper-container js-subcat-swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $subcategories as $subcategory ) : ?>
                    <div class="swiper-slide subcat-slide-item">
                        <a href="<?php echo esc_url( $subcategory['url'] ); ?>" class="subcat-slide-link">
                            <div class="subcat-slide-image">
                                <img src="<?php echo esc_url( $subcategory['image'] ); ?>"
                                     alt="<?php echo esc_attr( $subcategory['title'] ); ?>"
                                     loading="eager"
                                     fetchpriority="high"
                                     data-no-lazy="1">
                            </div>
                            <h3 class="subcat-slide-title">
                                <?php echo esc_html( $subcategory['title'] ); ?>
                            </h3>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Paginación para móvil -->
            <div class="subcat-slider-pagination swiper-pagination"></div>
            <!-- Flechas para desktop -->
            <div class="subcat-slider-button-prev swiper-button-prev">
                <svg viewBox="0 0 20 20" class="carousel-slider-nav-icon">
                    <path d="M14 5l-5 5 5 5-1 2-7-7 7-7z"></path>
                </svg>
            </div>
            <div class="subcat-slider-button-next swiper-button-next">
                <svg viewBox="0 0 20 20" class="carousel-slider-nav-icon">
                    <path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path>
                </svg>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'render_subcategories_slider', 'render_product_subcategories_slider' );




// Agregar campos de encabezado, texto y Preguntas Frecuentes en la edición de categorías de WooCommerce
function add_custom_faqs_fields($term) {
    $custom_faqs_header = get_term_meta($term->term_id, 'custom_faqs_header', true);
    $custom_faqs_text = get_term_meta($term->term_id, 'custom_faqs_text', true);
    $custom_faqs = get_term_meta($term->term_id, 'custom_faqs', true);
    
    if (!is_array($custom_faqs)) {
        $custom_faqs = array();
    }
    ?>
    
    <!-- Encabezado para la sección de FAQs -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="custom_faqs_header"><?php _e('h2 para faqs', 'woocommerce'); ?></label></th>
        <td>
            <input type="text" name="custom_faqs_header" id="custom_faqs_header" value="<?php echo esc_attr($custom_faqs_header); ?>" />
            <p class="description"><?php _e('Introduce un título para la sección de Preguntas Frecuentes.', 'woocommerce'); ?></p>
        </td>
    </tr>

    <!-- Preguntas Frecuentes -->
    <tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Preguntas Frecuentes', 'woocommerce'); ?></label></th>
        <td>
            <div id="custom-faqs-wrapper">
                <?php
                if (!empty($custom_faqs)) {
                    foreach ($custom_faqs as $index => $faq) {
                        ?>
                        <div class="custom-faq-item">
                            <p>
                                <label><?php _e('Pregunta:', 'woocommerce'); ?><br/>
                                <input type="text" name="custom_faqs[<?php echo $index; ?>][question]" value="<?php echo esc_attr($faq['question']); ?>" /></label>
                            </p>
                            <p>
                                <label><?php _e('Respuesta:', 'woocommerce'); ?></label>
                                <?php
                                $editor_id = 'custom_faqs_' . $index . '_answer';
                                wp_editor($faq['answer'], $editor_id, array(
                                    'textarea_name' => "custom_faqs[{$index}][answer]",
                                    'media_buttons' => false,
                                    'teeny' => false,
                                    'quicktags' => true
                                ));
                                ?>
                            </p>
                            <p>
                                <a href="#" class="remove-custom-faq"><?php _e('Eliminar', 'woocommerce'); ?></a>
                            </p>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <p>
                <a href="#" id="add-custom-faq" class="button"><?php _e('Añadir Pregunta', 'woocommerce'); ?></a>
            </p>
            <p class="description"><?php _e('Añade preguntas frecuentes para esta categoría.', 'woocommerce'); ?></p>
        </td>
    </tr>

    <script>
    jQuery(document).ready(function($){
        var index = <?php echo count($custom_faqs); ?>;
        
        // CORRECCIÓN 1: Sincronizar TinyMCE antes de enviar el formulario
        $('form').on('submit', function() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
        });

        $('#add-custom-faq').on('click', function(e){
            e.preventDefault();
            
            var editorId = 'custom_faqs_' + index + '_answer';

            var html = '<div class="custom-faq-item">';
            html += '<p><label>Pregunta:<br/><input type="text" name="custom_faqs['+index+'][question]" value="" /></label></p>';
            html += '<p><label>Respuesta:</label>';
            html += '<textarea name="custom_faqs['+index+'][answer]" id="'+editorId+'"></textarea></p>';
            html += '<p><a href="#" class="remove-custom-faq">Eliminar</a></p>';
            html += '</div>';

            $('#custom-faqs-wrapper').append(html);

            // Esperar un momento y luego inicializar TinyMCE en el nuevo campo de respuesta
            setTimeout(function(){
                tinymce.init({
                    selector: '#' + editorId,
                    menubar: false,
                    toolbar: 'bold italic underline | bullist numlist | link',
                    quicktags: true
                });
            }, 100);

            index++;
        });

        // CORRECCIÓN 2: Destruir instancia TinyMCE antes de eliminar el elemento del DOM
        $(document).on('click', '.remove-custom-faq', function(e){
            e.preventDefault();
            var $item = $(this).closest('.custom-faq-item');
            var $textarea = $item.find('textarea');
            if ($textarea.length && typeof tinymce !== 'undefined') {
                tinymce.remove('#' + $textarea.attr('id'));
            }
            $item.remove();
        });
    });
    </script>

    <?php
}
add_action('product_cat_edit_form_fields', 'add_custom_faqs_fields', 10, 1);


// Guardar datos de encabezado, texto y preguntas frecuentes
function save_custom_faqs_fields($term_id) {
    if (isset($_POST['custom_faqs_header'])) {
        update_term_meta($term_id, 'custom_faqs_header', sanitize_text_field($_POST['custom_faqs_header']));
    }

    if (isset($_POST['custom_faqs_text'])) {
        update_term_meta($term_id, 'custom_faqs_text', wp_kses_post($_POST['custom_faqs_text']));
    }

    if (isset($_POST['custom_faqs']) && is_array($_POST['custom_faqs'])) {
        $custom_faqs = array_map(function($faq) {
            return [
                'question' => sanitize_text_field($faq['question']),
                'answer' => wp_kses_post($faq['answer'])
            ];
        }, $_POST['custom_faqs']);
        // CORRECCIÓN 3: Reindexar el array para evitar índices discontinuos tras eliminar FAQs
        $custom_faqs = array_values($custom_faqs);
        update_term_meta($term_id, 'custom_faqs', $custom_faqs);
    } else {
        delete_term_meta($term_id, 'custom_faqs');
    }
}
add_action('edited_product_cat', 'save_custom_faqs_fields', 10, 1);


function display_custom_faqs() {
    if (!is_tax('product_cat')) {
        return '';
    }

    $category_id = get_queried_object_id();
    if (!$category_id) return '';

    $custom_faqs = get_term_meta($category_id, 'custom_faqs', true);

    ob_start();
    
    if (!empty($custom_faqs)) {
        echo '<div class="faq-accordion">';
        foreach ($custom_faqs as $faq) {
            echo '<div class="faq-item">';
            echo '<h3 class="faq-question">' . esc_html($faq['question']) . ' <span class="faq-icon">+</span></h3>';
            echo '<div class="faq-answer">' . wp_kses_post($faq['answer']) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        $faq_schema = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => []
        ];

        foreach ($custom_faqs as $faq) {
            $faq_schema['mainEntity'][] = [
                "@type" => "Question",
                "name" => wp_strip_all_tags($faq['question']),
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => wp_strip_all_tags($faq['answer'])
                ]
            ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    return ob_get_clean();
}
add_shortcode('custom_faqs', 'display_custom_faqs');

// Ocultar contenedor de preguntas si no hay FAQs
function hide_empty_faqs_container() {
    if (!is_tax('product_cat')) {
        return;
    }
    
    $category_id = get_queried_object_id();
    if (!$category_id) return;
    
    $custom_faqs = get_term_meta($category_id, 'custom_faqs', true);
    
    // Si no hay FAQs, agregar CSS para ocultar el contenedor
    if (empty($custom_faqs)) {
        ?>
        <style>
        .preguntas {
            display: none !important;
            height: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'hide_empty_faqs_container');

function display_custom_faqs_header() {
    if (!is_tax('product_cat')) {
        return '';
    }
    $category_id = get_queried_object_id();
    if (!$category_id) return '';
    $custom_faqs_header = get_term_meta($category_id, 'custom_faqs_header', true);
    if (!empty($custom_faqs_header)) {
        return '<h2 style="font-size:22px; text-align:center;">' . esc_html($custom_faqs_header) . '</h2>';
    }
    return '';
}
add_shortcode('custom_faqs_header', 'display_custom_faqs_header');





// Agregar el campo H1 en la edición de categorías de WooCommerce
function add_custom_h1_field($term) {
    $custom_h1 = get_term_meta($term->term_id, 'custom_h1', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="custom_h1"><?php _e('Título H1', 'woocommerce'); ?></label></th>
        <td>
            <input type="text" name="custom_h1" id="custom_h1" value="<?php echo esc_attr($custom_h1); ?>" />
            <p class="description"><?php _e('Introduce un H1 personalizado para esta categoría.', 'woocommerce'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'add_custom_h1_field', 10, 1);

// Guardar el campo H1 en la categoría
function save_custom_h1_field($term_id) {
    if (isset($_POST['custom_h1'])) {
        update_term_meta($term_id, 'custom_h1', sanitize_text_field($_POST['custom_h1']));
    }
}
add_action('edited_product_cat', 'save_custom_h1_field', 10, 1);

function display_custom_h1_shortcode() {
    if (!is_tax('product_cat')) {
        return '';
    }

    $category_id = get_queried_object_id();
    if (!$category_id) return '';

    $custom_h1 = get_term_meta($category_id, 'custom_h1', true);

    if (!empty($custom_h1)) {
        return '<h1 class="custom-h1-category">' . esc_html($custom_h1) . '</h1>';
    }

    return '';
}
add_shortcode('custom_h1', 'display_custom_h1_shortcode');



// Register Custom Post Type for Popular Filters
function register_popular_filters_cpt() {
    $labels = array(
        'name'               => 'Popular Filters',
        'singular_name'      => 'Popular Filter',
        'menu_name'          => 'Popular Filters',
        'name_admin_bar'     => 'Popular Filter',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Filter',
        'new_item'           => 'New Filter',
        'edit_item'          => 'Edit Filter',
        'view_item'          => 'View Filter',
        'all_items'          => 'All Filters',
        'search_items'       => 'Search Filters',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_menu'       => true,
        'supports'           => array('title', 'editor'),
        'menu_icon'          => 'dashicons-filter', // Admin menu icon
    );

    register_post_type('popular_filters', $args);
}
add_action('init', 'register_popular_filters_cpt');


// Shortcode para Mostrar Filtros Populares
function display_popular_filters_shortcode($atts) {
    // Extrae los atributos del shortcode, por defecto id vacío
    $atts = shortcode_atts(
        array(
            'id' => '', // ID del post del filtro popular
        ), $atts, 'display_popular_filters');

    $post_id = intval($atts['id']);

    // Asegúrate de que tenemos un ID de post válido y que es del tipo 'popular_filters'
    if (!$post_id || get_post_type($post_id) !== 'popular_filters') {
        return '<p>No filters available.</p>';
    }

    // Obtener las subcategorías desde los meta campos
    $subcategory_1 = get_post_meta($post_id, '_subcategory_1', true);
    $subcategory_2 = get_post_meta($post_id, '_subcategory_2', true);
    $subcategory_3 = get_post_meta($post_id, '_subcategory_3', true);
    $subcategory_4 = get_post_meta($post_id, '_subcategory_4', true);

    // Iniciar el almacenamiento en buffer
    ob_start();

    // Salida HTML de los filtros
    ?>
    <div class="popular-filters">
        <h3><?php //echo esc_html(get_the_title($post_id)); ?>Beliebte Filter</h3>
        <ul>
            <?php 
            // Agrupar las subcategorías para facilitar el manejo
            $subcategories = array($subcategory_1, $subcategory_2, $subcategory_3, $subcategory_4);
            foreach ($subcategories as $subcategory) {
                if (is_array($subcategory) && !empty($subcategory['name']) && !empty($subcategory['url'])): ?>
                    <li class="item-popular-filters"><a href="<?php echo esc_url($subcategory['url']); ?>"><?php echo esc_html($subcategory['name']); ?></a></li>
                <?php endif;
            }
            ?>
        </ul>
    </div>
    <?php

    // Retornar el contenido del buffer
    return ob_get_clean();
}

// Registrar el shortcode
add_shortcode('display_popular_filters', 'display_popular_filters_shortcode');


// Register Meta Boxes for Popular Filters CPT
function add_popular_filters_metaboxes() {
    add_meta_box(
        'popular_filters_meta',       // Unique ID of the meta box
        'Subcategory Filters',        // Meta box title
        'popular_filters_meta_callback', // Callback function
        'popular_filters',            // Post type where the meta box appears
        'normal',                     // Context (normal, side, etc.)
        'high'                        // Priority
    );
}
add_action('add_meta_boxes', 'add_popular_filters_metaboxes');

// Callback function to render meta box fields
// Callback function to render meta box fields
function popular_filters_meta_callback($post) {
    // Retrieve existing values from the post meta (if any)
    $subcategory_1 = get_post_meta($post->ID, '_subcategory_1', true);
    $subcategory_2 = get_post_meta($post->ID, '_subcategory_2', true);
    $subcategory_3 = get_post_meta($post->ID, '_subcategory_3', true);
    $subcategory_4 = get_post_meta($post->ID, '_subcategory_4', true);

    // Add nonce field for security
    wp_nonce_field('save_popular_filters_meta', 'popular_filters_nonce');

    // If no values exist, make sure $subcategory_1['name'] or $subcategory_1['url'] are empty strings by default
    $subcategory_1_name = isset($subcategory_1['name']) ? esc_attr($subcategory_1['name']) : '';
    $subcategory_1_url = isset($subcategory_1['url']) ? esc_attr($subcategory_1['url']) : '';

    $subcategory_2_name = isset($subcategory_2['name']) ? esc_attr($subcategory_2['name']) : '';
    $subcategory_2_url = isset($subcategory_2['url']) ? esc_attr($subcategory_2['url']) : '';

    $subcategory_3_name = isset($subcategory_3['name']) ? esc_attr($subcategory_3['name']) : '';
    $subcategory_3_url = isset($subcategory_3['url']) ? esc_attr($subcategory_3['url']) : '';

    $subcategory_4_name = isset($subcategory_4['name']) ? esc_attr($subcategory_4['name']) : '';
    $subcategory_4_url = isset($subcategory_4['url']) ? esc_attr($subcategory_4['url']) : '';

    ?>
    <div class="popular-filters-meta">
		<table style="width: 60%;">
			<tr>
				<td style="text-align: right;">
					<p><strong>Subcategory 1:</strong></p>
					<label for="subcategory_1_name">Name:</label>
					<input type="text" id="subcategory_1_name" name="subcategory_1[name]" value="<?php echo $subcategory_1_name; ?>" size="30" /><br />
					<label for="subcategory_1_url">URL:</label>
					<input type="text" id="subcategory_1_url" name="subcategory_1[url]" value="<?php echo $subcategory_1_url; ?>" size="30" /><br /><br />
				</td>
				<td style="text-align: right;">
					
					

					<p><strong>Subcategory 2:</strong></p>
					<label for="subcategory_2_name">Name:</label>
					<input type="text" id="subcategory_2_name" name="subcategory_2[name]" value="<?php echo $subcategory_2_name; ?>" size="30" /><br />
					<label for="subcategory_2_url">URL:</label>
					<input type="text" id="subcategory_2_url" name="subcategory_2[url]" value="<?php echo $subcategory_2_url; ?>" size="30" /><br /><br />
				</td>
			</tr>
			<tr>
				<td style="text-align: right;">

				<p><strong>Subcategory 3:</strong></p>
				<label for="subcategory_3_name">Name:</label>
				<input type="text" id="subcategory_3_name" name="subcategory_3[name]" value="<?php echo $subcategory_3_name; ?>" size="30" /><br />
				<label for="subcategory_3_url">URL:</label>
				<input type="text" id="subcategory_3_url" name="subcategory_3[url]" value="<?php echo $subcategory_3_url; ?>" size="30" /><br /><br />

				</td>
				<td style="text-align: right;">
					<p><strong>Subcategory 4:</strong></p>
					<label for="subcategory_4_name">Name:</label>
					<input type="text" id="subcategory_4_name" name="subcategory_4[name]" value="<?php echo $subcategory_4_name; ?>" size="30" /><br />
					<label for="subcategory_4_url">URL:</label>
					<input type="text" id="subcategory_4_url" name="subcategory_4[url]" value="<?php echo $subcategory_4_url; ?>" size="30" /><br /><br />
				</td>
			</tr>
		</table>
    </div>
    <?php
}



// Save meta box data when saving the post
function save_popular_filters_meta($post_id) {
    // Check if nonce is set
    if (!isset($_POST['popular_filters_nonce']) || !wp_verify_nonce($_POST['popular_filters_nonce'], 'save_popular_filters_meta')) {
        return $post_id;
    }

    // Check if this is an autosave. If so, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // Validate and sanitize subcategory 1
    if (isset($_POST['subcategory_1'])) {
        $subcategory_1 = array(
            'name' => sanitize_text_field($_POST['subcategory_1']['name']),
            'url' => esc_url_raw($_POST['subcategory_1']['url']),
        );
        update_post_meta($post_id, '_subcategory_1', $subcategory_1);
    }

    // Validate and sanitize subcategory 2
    if (isset($_POST['subcategory_2'])) {
        $subcategory_2 = array(
            'name' => sanitize_text_field($_POST['subcategory_2']['name']),
            'url' => esc_url_raw($_POST['subcategory_2']['url']),
        );
        update_post_meta($post_id, '_subcategory_2', $subcategory_2);
    }

    // Validate and sanitize subcategory 3
    if (isset($_POST['subcategory_3'])) {
        $subcategory_3 = array(
            'name' => sanitize_text_field($_POST['subcategory_3']['name']),
            'url' => esc_url_raw($_POST['subcategory_3']['url']),
        );
        update_post_meta($post_id, '_subcategory_3', $subcategory_3);
    }

    // Validate and sanitize subcategory 4
    if (isset($_POST['subcategory_4'])) {
        $subcategory_4 = array(
            'name' => sanitize_text_field($_POST['subcategory_4']['name']),
            'url' => esc_url_raw($_POST['subcategory_4']['url']),
        );
        update_post_meta($post_id, '_subcategory_4', $subcategory_4);
    }
}
add_action('save_post', 'save_popular_filters_meta');

// 1. Añadir una nueva columna "Shortcode" al listado de popular_filters
function add_popular_filters_shortcode_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') { // Después de la columna de título
            $new_columns['shortcode'] = 'Shortcode';
        }
    }
    return $new_columns;
}
add_filter('manage_popular_filters_posts_columns', 'add_popular_filters_shortcode_column');

// 2. Rellenar la columna "Shortcode" con el shortcode correspondiente
function populate_popular_filters_shortcode_column($column, $post_id) {
    if ($column == 'shortcode') {
        $shortcode = '[display_popular_filters id="' . $post_id . '"]';
        echo '<input type="text" value="' . esc_attr($shortcode) . '" readonly size="30" onclick="this.select();" style="border:none; background:transparent; cursor:pointer;" title="Haz clic para copiar">';
    }
}
add_action('manage_popular_filters_posts_custom_column', 'populate_popular_filters_shortcode_column', 10, 2);

// 3. (Opcional) Añadir estilos personalizados para la columna "Shortcode"
function popular_filters_admin_styles() {
    echo '<style>
        .column-shortcode input {
            width: 100%;
            box-sizing: border-box;
        }
    </style>';
}
add_action('admin_head', 'popular_filters_admin_styles');


/**
 * Add Rich Text Editor to Add New Product Category Form
 *
 * @param string $taxonomy The taxonomy slug.
 */
function wcpc_add_category_fields( $taxonomy ) {
    ?>
    <div class="form-field term-group">
        <label for="wcpc_additional_description"><?php esc_html_e( 'Additional Description', 'wc-product-category' ); ?></label>
        <?php
            $content   = '';
            $editor_id = 'wcpc_additional_description';
            $settings  = array(
                'textarea_name' => 'wcpc_additional_description',
                'textarea_rows' => 10,
                'media_buttons' => true,
                'tinymce'       => array(
                    'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,wp_more',
                    'toolbar2' => '',
                    // Aquí puedes añadir el tamaño de la fuente dentro de TinyMCE
                    'fontsize_formats' => '16px', // Definir el tamaño por defecto
                ),
            );
            wp_editor( $content, $editor_id, $settings );
        ?>
        <h2 class="description"><?php esc_html_e( 'Add an additional description with rich text formatting.', 'wc-product-category' ); ?>.<br/>Using shortcode [wcpc_additional_description]</p>
    </div>

    <style>
        /* Aplica el tamaño de fuente al campo del editor */
        #wcpc_additional_description {
            font-size: 16px !important;
        }
    </style>
    <?php
}
add_action( 'product_cat_add_form_fields', 'wcpc_add_category_fields', 10, 1 );



/**
 * Add Rich Text Editor to Edit Product Category Form
 *
 * @param WP_Term $term     The term object.
 * @param string  $taxonomy The taxonomy slug.
 */
function wcpc_edit_category_fields( $term, $taxonomy ) {
    // Retrieve the existing value for the additional description.
    $additional_description = get_term_meta( $term->term_id, 'wcpc_additional_description', true );
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="wcpc_additional_description"><?php esc_html_e( 'Additional Description', 'wc-product-category' ); ?></label></th>
        <td>
            <?php
                $editor_id = 'wcpc_additional_description';
                $settings  = array(
                    'textarea_name' => 'wcpc_additional_description',
                    'textarea_rows' => 10,
                    'media_buttons' => true,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,wp_more',
                        'toolbar2' => '',
                    ),
                );
                wp_editor( wp_kses_post( $additional_description ), $editor_id, $settings );
            ?>
            <p class="description"><?php esc_html_e( 'Add an additional description with rich text formatting.', 'wc-product-category' ); ?><br/>Using shortcode [wcpc_additional_description]</p>
        </td>
    </tr>
    <?php
}
add_action( 'product_cat_edit_form_fields', 'wcpc_edit_category_fields', 10, 2 );

/**
 * Save Additional Description for Product Category
 *
 * @param int $term_id The term ID.
 * @param int $tt_id   The taxonomy term ID.
 */
function wcpc_save_category_meta( $term_id, $tt_id ) {
    // Check if the additional description is set in the POST request.
    if ( isset( $_POST['wcpc_additional_description'] ) ) {
        // Sanitize the content, allowing only safe HTML tags.
        $sanitized_description = wp_kses_post( wp_unslash( $_POST['wcpc_additional_description'] ) );

        // Update the term meta with the sanitized content.
        update_term_meta( $term_id, 'wcpc_additional_description', $sanitized_description );
    }
}
add_action( 'created_product_cat', 'wcpc_save_category_meta', 10, 2 );
add_action( 'edited_product_cat', 'wcpc_save_category_meta', 10, 2 );

/**
 * Shortcode to Display Additional Description for Product Categories
 *
 * @return string The HTML content of the additional description.
 */
function wcpc_additional_description_shortcode() {
    // Check if we're on a product category page.
    if ( is_product_category() ) {
        // Get the current queried term.
        $term = get_queried_object();

        // Retrieve the additional description from term meta.
        $additional_description = get_term_meta( $term->term_id, 'wcpc_additional_description', true );

        if ( ! empty( $additional_description ) ) {
            // Start output buffering to capture HTML.
            ob_start();
            ?>
<div class="wcpc-additional-description">
    <p class="description-content-2" style="font-weight: 500;">
        <?php echo wp_kses_post( $additional_description ); ?>
    </p>
</div>

            <?php
            // Return the buffered content.
            return ob_get_clean();
        }
    }

    // Handle shortcode attributes for displaying specific category descriptions.
    $atts = shortcode_atts( array(
        'id'   => '',
        'slug' => '',
    ), array_slice( func_get_args(), 0, 2 ), 'wcpc_additional_description' );

    $term = null;

    if ( ! empty( $atts['id'] ) ) {
        $term = get_term( intval( $atts['id'] ), 'product_cat' );
    } elseif ( ! empty( $atts['slug'] ) ) {
        $term = get_term_by( 'slug', sanitize_title( $atts['slug'] ), 'product_cat' );
    }

    if ( $term && ! is_wp_error( $term ) ) {
        $additional_description = get_term_meta( $term->term_id, 'wcpc_additional_description', true );

        if ( ! empty( $additional_description ) ) {
            ob_start();
            ?>
            <div class="wcpc-additional-description">
                <h2><?php esc_html_e( 'Additional Information', 'wc-product-category' ); ?></h2>
                <div class="description-content">
                    <?php echo wp_kses_post( $additional_description ); ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
    }

    // Return nothing if no description is found.
    return '';
}
add_shortcode( 'wcpc_additional_description', 'wcpc_additional_description_shortcode' );






// Crear la página de configuración en el menú de administración de WordPress
function cross_selling_admin_menu() {
    add_menu_page(
        'Cross Selling',
        'Cross Selling',
        'manage_options',
        'cross-selling-settings',
        'cross_selling_settings_page',
        'dashicons-cart',
        20
    );
}
add_action('admin_menu', 'cross_selling_admin_menu');

// Crear el contenido de la página de configuración
function cross_selling_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configuración de Productos de Cross Selling</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cross_selling_settings_group');
            do_settings_sections('cross-selling-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}



// Registrar la configuración de los productos de cross selling
function cross_selling_settings_init() {
    register_setting('cross_selling_settings_group', 'cross_selling_product_ids');

    add_settings_section(
        'cross_selling_settings_section',
        'Productos de Cross Selling',
        'cross_selling_settings_section_callback',
        'cross-selling-settings'
    );

    add_settings_field(
        'cross_selling_product_ids',
        'IDs de Productos (separados por comas)',
        'cross_selling_product_ids_callback',
        'cross-selling-settings',
        'cross_selling_settings_section'
    );
}
add_action('admin_init', 'cross_selling_settings_init');

// Callback para la descripción de la sección
function cross_selling_settings_section_callback() {
    echo 'Selecciona manualmente los productos de cross selling ingresando los IDs de producto, separados por comas.';
}

// Callback para el campo de IDs de producto
function cross_selling_product_ids_callback() {
    $product_ids = get_option('cross_selling_product_ids', '');
    echo '<input type="text" name="cross_selling_product_ids" value="' . esc_attr($product_ids) . '" style="width:100%;" />';
}



// Agregar el banner de cross-selling en la página del carrito
function display_cross_selling_banner() {
    // Obtener los IDs de productos configurados en el administrador
    $product_ids = get_option('cross_selling_product_ids', '');
    if (!$product_ids) {
        return;
    }

    // Convertir los IDs en un array
    $product_ids = array_map('trim', explode(',', $product_ids));

    // Obtener los productos de cross-selling
    $args = array(
        'post_type' => 'product',
        'post__in' => $product_ids,
        'posts_per_page' => 4, // Cambia la cantidad de productos si es necesario
    );
    $cross_sell_query = new WP_Query($args);

    if ($cross_sell_query->have_posts()) {
        echo '<div class="woocommerce-info cross-selling-banner">';
        echo '<p><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Empfohlene Produkte</strong></p>';
        echo '<table class="shop_table shop_table_responsive shop_cross">'; // Inicio de la tabla con la clase de WooCommerce

        // Encabezado de la tabla
        echo '<thead><tr>';
        echo '<th class="product-thumbnail">&nbsp;</th>';
        echo '<th class="product-name" >Produkt</th>';
        echo '<th class="product-price" style="text-align:center">Preis</th>';
        echo '<th class="product-add-to-cart">&nbsp;</th>';
        echo '</tr></thead><tbody>';

        // Mostrar cada producto en una fila de la tabla
        while ($cross_sell_query->have_posts()) {
            $cross_sell_query->the_post();
            global $product;

            echo '<tr>';
            echo '<td class="product-thumbnail" style="text-align:center"><a href="' . get_the_permalink() . '">' . woocommerce_get_product_thumbnail() . '</a></td>';
            echo '<td class="product-name"><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></td>';
            echo '<td class="product-price" style="text-align:center">' . $product->get_price_html() . '</td>';
            echo '<td class="product-add-to-cart">';
            echo '<a href="' . esc_url( '?add-to-cart=' . $product->get_id() ) . '" class="button">In den Warenkorb</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>'; // Fin de la tabla y el banner
        wp_reset_postdata();
    }
}


add_shortcode( 'pp_add_to_cart', function( $atts ) {
	global $product;

	$atts = shortcode_atts( [
		'text'  => 'In den Warenkorb',
		'width' => '100%',
	], $atts );

	// Obtener el producto del contexto actual
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) return '';

	$pid   = $product->get_id();
	$type  = $product->get_type();
	$class = 'pp-add-btn product_type_' . esc_attr( $type );
	$href  = esc_url( '?add-to-cart=' . $pid );
	$text  = esc_html( $atts['text'] );
	$width = esc_attr( $atts['width'] );

	return '
		<a href="' . $href . '"
		   class="' . $class . '"
		   data-product_id="' . $pid . '"
		   data-quantity="1"
		   rel="nofollow"
		   style="display:inline-flex;align-items:center;justify-content:center;
		          width:' . $width . ';padding:14px 24px;background:#FE6100;color:#fff;
		          text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;
		          font-family:Open Sans,sans-serif;cursor:pointer;
		          transition:background .2s ease;letter-spacing:.02em;"
		   onmouseover="this.style.background=\'#333\'"
		   onmouseout="this.style.background=\'#FE6100\'">' .
		$text .
		'</a>';
} );



add_filter('wp_footer', function() {
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Seleccionar todas las imágenes con la clase 'custom-img-large'
            let images = document.querySelectorAll("img.custom-img-large");
            images.forEach(function(img) {
                img.src = "https://padelprofideutschland.de/wp-content/uploads/2024/12/PadelLogopng.png";
            });
        });
    </script>
    <?php
});


// Mostrar checkbox en pestaña "Allgemein"
add_action('woocommerce_product_options_pricing', 'custom_delivery_checkbox_field');
function custom_delivery_checkbox_field() {
    woocommerce_wp_checkbox( array(
        'id' => '_custom_delivery_delay',
        'label' => __('Activar entrega en 3–4 días', 'woocommerce'),
        'description' => __('Marca esta opción para mostrar "Lieferung in 3–4 Tagen".', 'woocommerce')
    ));
}

// Guardar valor del checkbox
add_action('woocommerce_process_product_meta', 'save_custom_delivery_checkbox_field');
function save_custom_delivery_checkbox_field($post_id) {
    $checkbox = isset($_POST['_custom_delivery_delay']) ? 'yes' : 'no';
    update_post_meta($post_id, '_custom_delivery_delay', $checkbox);
}

// Modificar el título del producto en el loop - quitar "Padelschläger" y hacer más gordo
add_filter('the_title', 'custom_product_title_loop', 10, 2);

function custom_product_title_loop($title, $id) {
    // Solo en loop de productos
    if (is_shop() || is_product_category() || is_product_tag()) {
        if (get_post_type($id) === 'product') {
            $title = str_replace('Padelschläger', '', $title);
            $title = trim($title);
        }
    }
    return $title;
}

// Añadir CSS para el título en una línea y más gordo
add_action('wp_head', 'custom_product_title_styles');

function custom_product_title_styles() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        ?>
        <style>
        .woocommerce-loop-product__title {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            margin-bottom: 8px !important;
            line-height: 1.2 !important;
            height: auto !important;
            min-height: 0 !important;
            font-weight: 600 !important;
        }
        </style>
        <?php
    }
}

/**
 * Texto de envío en el loop de productos.
 * Prioridades:
 * 1) Categoría "envios-urgentes" (solo L-J y <16:00) → "Gratisversand. Lieferung am {Día, den d. Mes}" (mañana laboral)
 * 2) _custom_delivery_delay = 'yes' → "Lieferung in 4–5 Tagen"
 * 3) Por defecto → "Lieferung in 48–72 Std."
 *
 * MODO SIMULACIÓN:
 * Añade ?simulate_monday=1 a la URL para simular "próximo lunes 10:00" (hora del sitio).
 * Ej: /padeltaschen/?simulate_monday=1
 */
// Añadir texto de envío gratis en el loop
add_action('woocommerce_after_shop_loop_item', 'custom_add_shipping_text_loop', 15);

// Añadir texto de envío gratis Y BOTÓN en el loop
add_action('woocommerce_after_shop_loop_item', 'custom_add_shipping_text_loop', 15);

function custom_add_shipping_text_loop() {
    global $product;
    
    if ( empty($product) || !is_a($product, 'WC_Product') ) return;
    
    // Envío gratis SIN icono
    $custom_delivery = get_post_meta($product->get_id(), '_custom_delivery_delay', true);
    $now_ts = current_time('timestamp');
    $dayN  = date('N', $now_ts);
    $hourG = date('G', $now_ts);
    
    $format_date_de_den = function($timestamp) {
        if (class_exists('IntlDateFormatter')) {
            $tz = get_option('timezone_string');
            if (!$tz) { $tz = 'Europe/Berlin'; }
            $fmt = new IntlDateFormatter(
                'de_DE',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE,
                $tz,
                IntlDateFormatter::GREGORIAN,
                "EEEE, 'den' d. MMMM"
            );
            return $fmt->format($timestamp);
        }
        return date('l, \d\e\n d. F', $timestamp);
    };
    
    echo '<div class="free-shipping-catalog" style="display: flex; flex-direction: column; align-items: center; justify-content: center; max-width: 100%; text-align: center;">';
    echo '<p style="margin: 0; font-size: 12px; line-height: 1.4; color:#00bf63; font-weight: 700;">Gratisversand.</p>';
    
    if ( has_term('envios-urgentes', 'product_cat', $product->get_id()) && $dayN >= 1 && $dayN <= 4 && $hourG < 16 ) {
        $tomorrow_ts   = strtotime('+1 weekday', $now_ts);
        $fecha_entrega = $format_date_de_den($tomorrow_ts);
        echo '<p style="margin: 0; font-size: 11px; line-height: 1.4; color: #000; font-weight: 600;">Lieferung am ' . esc_html($fecha_entrega) . '</p>';
    } elseif ( $custom_delivery === 'yes' ) {
        echo '<p style="margin: 0; font-size: 11px; line-height: 1.4; color: #000; font-weight: 600;">Lieferung in 4–5 Tagen</p>';
    } else {
        echo '<p style="margin: 0; font-size: 11px; line-height: 1.4; color: #000; font-weight: 600;">Lieferung in 48–72 Std.</p>';
    }
    
    echo '</div>';
    
if ( $product->is_purchasable() && ! has_term('praemien', 'product_cat', $product->get_id()) ) {
    $url   = esc_url( $product->add_to_cart_url() );
    $id    = absint( $product->get_id() );
    $label = esc_html__( 'In den Warenkorb', 'woocommerce' );
    ?>
    <div class="mi-btn-add-to-cart-container-catalog" style="text-align: center; display: flex; align-items: center; justify-content: center;">
        <button type="button"
                data-quantity="1"
                data-product_id="<?php echo $id; ?>"
                data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                data-product_url="<?php echo esc_url( get_permalink( $id ) ); ?>"
                class="button mi-btn-add-to-cart-carousel"
                style="display: inline-block; background: #fe6100; color: white; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; transition: background 0.3s ease; border: none; white-space: nowrap; cursor: pointer; font-family: inherit;">
            <?php echo $label; ?>
        </button>
    </div>
    <?php
}
}
// Shortcode: [entrega_dinamica]
function shortcode_entrega_dinamica() {
    if ( ! is_product() ) return '';

    $product_id = get_the_ID();
    if ( ! $product_id ) return '';

    $custom_delivery = get_post_meta($product_id, '_custom_delivery_delay', true);

    // Ahora de WP (con zona horaria de WP)
    $now_ts = current_time('timestamp');

    // Simulación opcional
    if ( isset($_GET['simulate_monday']) && $_GET['simulate_monday'] == '1' ) {
        $now_ts = strtotime('next monday 10:00', $now_ts);
    }

    $dayN  = date('N', $now_ts); // 1..7
    $hourG = date('G', $now_ts); // 0..23

    // Formateador alemán "den"
    $format_date_de_den = function($timestamp) {
        if ( class_exists('IntlDateFormatter') ) {
            $tz = get_option('timezone_string') ?: 'Europe/Berlin';
            $fmt = new IntlDateFormatter(
                'de_DE',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE,
                $tz,
                IntlDateFormatter::GREGORIAN,
                "EEEE, 'den' d. MMMM"
            );
            return $fmt->format($timestamp);
        }
        return date('l, \d\e\n d. F', $timestamp);
    };

    // MENSAJE + ESTADO (para color)
    $msg   = '';
    $state = 'default'; // default | medium | fast

    // PRIORIDAD 1 — categoría envíos urgentes (L-J < 16:00) => rápido
    if ( has_term('envios-urgentes', 'product_cat', $product_id) && $dayN >= 1 && $dayN <= 4 && $hourG < 16 ) {
        $tomorrow_ts = strtotime('+1 weekday', $now_ts); // salta fin de semana
        $msg   = 'Lieferung am ' . $format_date_de_den($tomorrow_ts);
        $state = 'fast';

    // PRIORIDAD 2 — checkbox => 3–4 días
    } elseif ( $custom_delivery === 'yes' ) {
        $msg   = 'Lieferung in 3–4 Tagen';
        $state = 'medium';

    // PRIORIDAD 3 — por defecto
    } else {
        $msg   = 'Lieferung in 48–72 Stunden';
        $state = 'default';
    }

    // SVG camión
    $truck_svg = '<svg class="ppd-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7h11v10H3z"/>
        <path d="M14 10h5l2 3v4h-7z"/>
        <circle cx="7.5" cy="17.5" r="2"/>
        <circle cx="17.5" cy="17.5" r="2"/>
        <path d="M1 9h2m-2 4h2"/>
    </svg>';

    // CSS inline (se imprime 1 sola vez)
    static $printed_css = false;
    $css = '';
    if ( ! $printed_css ) {
        $printed_css = true;
        $css = '<style>
/* ===== Overrides compactos para el bloque de entrega ===== */
.ppd-entrega{
  border-radius:8px;
    align-items:center;   /* 👈 centra verticalmente todos los elementos */

}
.ppd-left{                         /* textos "Versand / Rückgabe" */
  font-size:14px;                /* ≈ 13px */
  gap:.4rem;
  align-items:center;   /* 👈 centra icono con el texto */
}
.ppd-left strong{ font-weight:600; }

.ppd-right{                        /* contenedor del mensaje verde */
  margin-left:auto;
  align-self:center;               /* centra verticalmente */
  color:#00bf63;
}

            .ppd-ico{width:26px;height:26px; margin-right:10px;margin-bottom:-8px;
}

.ppd-msg{                          /* "Lieferung am Freitag..." */
  font-size:.875rem;                /* ≈ 13px (pequeño) */
  font-weight:700;                 /* tipografía normal */
  line-height:1.25rem;
  white-space:normal;              /* permite salto de línea si lo necesita */
  margin-left:35px;
}

.ppd-msg.fast{color:#00bf63;}      /* verde */
.ppd-msg.medium{color:#f97316;}    /* ámbar */
.ppd-msg.default{color:#00bf63;}   /* verde */

/* En móviles sigue siendo legible y no se desborda */
@media(max-width:480px){
  .ppd-entrega{gap:.35rem;}
  .ppd-left,.ppd-msg{font-size:.8rem;}
}

        </style>';
    }

    // HTML
$html  = $css;
$html .= '<div class="ppd-entrega">';
$html .=   '<div class="ppd-left">';
$html .=       $truck_svg .
               '<span><strong>Kostenlos Versand &amp; Rückgabe</strong></span>';
$html .=   '</div>';
$html .=   '<div class="ppd-right"><span class="ppd-msg ' . esc_attr($state) . '">' . esc_html($msg) . '</span></div>';
$html .= '</div>';

    return $html;
}
add_shortcode('entrega_dinamica', 'shortcode_entrega_dinamica');

// Helper compartido (no duplica si ya existe en tu tema/plugin)
if ( ! function_exists('ppd_get_entrega_data') ) {
    function ppd_get_entrega_data( $product_id = null ) {
        if ( ! $product_id ) {
            $product_id = get_the_ID();
        }
        if ( ! $product_id || 'product' !== get_post_type($product_id) ) {
            return array('msg' => '', 'state' => '');
        }

        $custom_delivery = get_post_meta($product_id, '_custom_delivery_delay', true);
        $now_ts = current_time('timestamp');

        // Simulación opcional
        if ( isset($_GET['simulate_monday']) && $_GET['simulate_monday'] == '1' ) {
            $now_ts = strtotime('next monday 10:00', $now_ts);
        }

        $dayN  = date('N', $now_ts);
        $hourG = date('G', $now_ts);

        // Formateador alemán "den"
        $format_date_de_den = function($timestamp) {
            if ( class_exists('IntlDateFormatter') ) {
                $tz = get_option('timezone_string') ?: 'Europe/Berlin';
                $fmt = new IntlDateFormatter(
                    'de_DE',
                    IntlDateFormatter::LONG,
                    IntlDateFormatter::NONE,
                    $tz,
                    IntlDateFormatter::GREGORIAN,
                    "EEEE, 'den' d. MMMM"
                );
                return $fmt->format($timestamp);
            }
            return date('l, \d\e\n d. F', $timestamp);
        };

        $msg   = '';
        $state = 'default'; // default | medium | fast

        if ( has_term('envios-urgentes', 'product_cat', $product_id) && $dayN >= 1 && $dayN <= 4 && $hourG < 16 ) {
            $tomorrow_ts = strtotime('+1 weekday', $now_ts);
            $msg   = 'Lieferung am ' . $format_date_de_den($tomorrow_ts);
            $state = 'fast';
        } elseif ( $custom_delivery === 'yes' ) {
            $msg   = 'Lieferung in 3–4 Tagen';
            $state = 'medium';
        } else {
            $msg   = 'Lieferung in 48–72 Stunden';
            $state = 'default';
        }

        return array('msg' => $msg, 'state' => $state);
    }
}

/**
 * Shortcode aislado: [entrega_dinamica_msg]
 * - Imprime SOLO el mensaje de entrega.
 * - Usa clases únicas (prefijo .ppd2-) para no colisionar con el original.
 *
 * Atributos:
 * - product_id: (opcional) para usar fuera de la ficha del producto.
 * - tag: etiqueta (span|div|p|strong|em). Por defecto "span".
 * - class: clases adicionales (se suman a "ppd2-msg").
 * - include_css: yes|no. Inyecta (una sola vez) el CSS de este shortcode. Por defecto "yes".
 */
function shortcode_entrega_dinamica_msg( $atts = array() ) {
    // Permite uso en single product o pasando product_id explícito
    if ( ! is_product() && empty($atts['product_id']) ) {
        return '';
    }

    $atts = shortcode_atts(array(
        'product_id'  => '',
        'tag'         => 'span',
        'class'       => '',
        'include_css' => 'yes',
    ), $atts, 'entrega_dinamica_msg');

    $product_id = $atts['product_id'] ? intval($atts['product_id']) : get_the_ID();
    $data = ppd_get_entrega_data($product_id);
    if ( empty($data['msg']) ) return '';

    // Inyecta CSS propio una sola vez (aislado con prefijo .ppd2-)
    static $ppd2_css_printed = false;
    $css = '';
    if ( ! $ppd2_css_printed && strtolower($atts['include_css']) === 'yes' ) {
        $ppd2_css_printed = true;
        $css = '<style>
/* ===== Estilos AISLADOS (no tocan .ppd-* original) ===== */
.ppd2-msg{
  font-size: 16px;
  font-weight:600;
  line-height:1.25rem;
  white-space:normal;
}
.ppd2-msg.fast{color:#118000;}      /* verde */
.ppd2-msg.medium{color:#f97316;}    /* ámbar */
.ppd2-msg.default{color:#118000;}   /* verde por defecto */
</style>';
    }

    // Sanea tag
    $allowed_tags = array('span','div','p','strong','em');
    $tag = in_array(strtolower($atts['tag']), $allowed_tags, true) ? strtolower($atts['tag']) : 'span';

    // Compone clases (base + estado + extra)
    $classes = trim( 'ppd2-msg ' . $data['state'] . ( $atts['class'] ? ' ' . $atts['class'] : '' ) );

    $html  = $css;
    $html .= '<' . $tag . ' class="' . esc_attr($classes) . '">' . esc_html($data['msg']) . '</' . $tag . '>';

    return $html;
}
add_shortcode('entrega_dinamica_msg', 'shortcode_entrega_dinamica_msg');



//add_action('wp_footer', function() {
//    if (is_checkout()) {
//        echo '<script>
//            document.addEventListener("DOMContentLoaded", function() {
                // Selecciona el checkbox por su ID
//                var checkbox = document.getElementById("kl_newsletter_checkbox");
//                if (checkbox) {
//                    // Encuentra el texto asociado al checkbox
//                    var label = checkbox.closest("label").querySelector(".fc-checkbox-label-text");
//                    if (label) {
//                        label.innerText = "Melde dich an, um Updates und Neuigkeiten per E-Mail zu erhalten";
//                        console.log("Texto modificado con éxito.");
//                    } else {
//                        console.log("No se encontró el texto asociado al checkbox.");
//                    }
//                } else {
//                    console.log("No se encontró el checkbox con el ID kl_newsletter_checkbox.");
//                }
//            });
//        </script>';
//    }
//});

add_action('wp_head', function () {
    ?>
    <style>
        @media (min-width: 768px) and (max-width: 1024px) { /* Rango típico de tablet */
            div.elementor-element.elementor-element-a1776f3 {
                display: none !important;
            }
        }
    </style>
    <?php
});

function shortcode_h1() {
    $pod = pods( get_post_type(), get_the_ID() );
    return $pod->display( 'h1' );
}
add_shortcode( 'h1', 'shortcode_h1' );



// Agregar los campos de fecha como datos de producto en el carrito
add_filter('woocommerce_add_cart_item_data', 'guardar_fechas_en_carrito', 10, 3);
function guardar_fechas_en_carrito($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin'])) {
        $cart_item_data['fecha_inicio'] = sanitize_text_field($_POST['fecha_inicio']);
        $cart_item_data['fecha_fin'] = sanitize_text_field($_POST['fecha_fin']);
    }
    return $cart_item_data;
}

// Mostrar las fechas en el carrito
add_filter('woocommerce_get_item_data', 'mostrar_fechas_en_carrito', 10, 2);
function mostrar_fechas_en_carrito($item_data, $cart_item) {
    if (isset($cart_item['fecha_inicio']) && isset($cart_item['fecha_fin'])) {
        $item_data[] = array(
            'name' => 'Startdatum',
            'value' => $cart_item['fecha_inicio']
        );
        $item_data[] = array(
            'name' => 'Enddatum',
            'value' => $cart_item['fecha_fin']
        );
    }
    return $item_data;
}

// Guardar las fechas en los detalles del pedido
add_action('woocommerce_checkout_create_order_line_item', 'guardar_fechas_en_pedido', 10, 4);
function guardar_fechas_en_pedido($item, $cart_item_key, $values, $order) {
    if (isset($values['fecha_inicio']) && isset($values['fecha_fin'])) {
        $item->add_meta_data('Fecha de inicio', $values['fecha_inicio']);
        $item->add_meta_data('Fecha de finalización', $values['fecha_fin']);
    }
}

/**
 * Deshabilitar el selector de cantidad para los productos en la categoría 'palas-de-padel-de-test'
 */
add_filter( 'woocommerce_is_sold_individually', 'deshabilitar_cantidad_categoria', 10, 2 );
function deshabilitar_cantidad_categoria( $is_sold_individually, $product ) {
    // Ajusta 'palas-de-padel-de-test' al slug real de tu categoría
    if ( has_term( 'padelschlaeger-fuer-test', 'product_cat', $product->get_id() ) ) {
        return true;  // true => se vende individualmente, sin selector de cantidad
    }

    return $is_sold_individually;
}

function corregir_texto_add_to_cart($text) {
    global $product;

    if ( has_term( 'padelschlaeger-adidas-2026', 'padelschlaeger-nox-2026', 'product_cat', $product->get_id() ) ) {
        return __('Jetzt vorbestellen - Lieferung am 01.12', 'woocommerce');
    }

    return __('In den Warenkorb', 'woocommerce');
}
add_filter('woocommerce_product_single_add_to_cart_text', 'corregir_texto_add_to_cart');
add_filter('woocommerce_product_add_to_cart_text', 'corregir_texto_add_to_cart');



function obtener_segunda_palabra_categoria() {
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        $nombre = $term->name;
        
        // Convertir el nombre en un array de palabras
        $palabras = explode(' ', $nombre);
        
        // Verificar si hay al menos dos palabras
        if (count($palabras) > 1) {
            return '<h1 style="margin:0;">' . esc_html($palabras[1]) . '</h1>';
        }
    }
    return '';
}
add_shortcode('categoria_segunda_palabra', 'obtener_segunda_palabra_categoria');


function cargar_scripts_personalizados() {
    // Encolar Swiper.js y su CSS
    $swiper_ver = '11.0.0';
    wp_enqueue_style('swiper-css', get_stylesheet_directory_uri() . '/assets/plugins/swiper/swiper-bundle.min.css', array(), $swiper_ver);
    wp_enqueue_script('swiper-js', get_stylesheet_directory_uri() . '/assets/plugins/swiper/swiper-bundle.min.js', array(), $swiper_ver, true);

    // Encolar main.js y asegurarse de que Swiper.js se carga antes
}
add_action('wp_enqueue_scripts', 'cargar_scripts_personalizados');

//include_once get_template_directory() . '/redirections.php';

// Agregar campo de Contenido SEO en la edición de categorías de WooCommerce
function add_custom_seo_field($term) {
    $seo_content = get_term_meta($term->term_id, 'seo_content', true);
    ?>
    
    <!-- Campo de Contenido SEO -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="seo_content"><?php _e('Contenido SEO', 'woocommerce'); ?></label></th>
        <td>
            <?php 
            wp_editor($seo_content, 'seo_content', array(
                'textarea_name' => 'seo_content',
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true
            ));
            ?>
            <p class="description"><?php _e('Introduce contenido SEO para esta categoría. Se mostrará un extracto de 60 palabras con opción de "Leer más".', 'woocommerce'); ?></p>
        </td>
    </tr>

    <?php
}
add_action('product_cat_edit_form_fields', 'add_custom_seo_field', 10, 1);

// Guardar el campo "Contenido SEO" en la base de datos
function save_custom_seo_field($term_id) {
    if (isset($_POST['seo_content'])) {
        update_term_meta($term_id, 'seo_content', wp_kses_post($_POST['seo_content']));
    }
}
add_action('edited_product_cat', 'save_custom_seo_field', 10, 2);

function shortcode_contenido_seo_categoria() {
    if (!is_product_category()) return '';
    
    $term_id = get_queried_object_id();
    $seo_content = get_term_meta($term_id, 'seo_content', true);
    
    if (empty($seo_content)) return '';
    
    ob_start();
    ?>
    <div class="category-seo-content">
        <?php echo wp_kses_post(wpautop($seo_content)); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('seo_categoria', 'shortcode_contenido_seo_categoria');



/*esto lo que hace es eliminar los datos estructurados que tienen que ver en la landing-page*/


add_action('wp_loaded', function () {
    ob_start(function ($content) {
        return preg_replace_callback(
            '/<script type="application\/ld\+json">(.*?)<\/script>/s',
            function ($matches) {
                $json_raw = $matches[1];

                if (str_contains($json_raw, '"@graph"') && str_contains($json_raw, '"@type":"Product"')) {
                    // Convertir en JSON válido
                    $json = json_decode($json_raw, true);
                    if (isset($json['@graph']) && is_array($json['@graph'])) {
                        $todosSonProductosSinOffers = true;
                        foreach ($json['@graph'] as $item) {
                            if (
                                isset($item['@type']) && $item['@type'] === 'Product' &&
                                (isset($item['offers']) || isset($item['review']) || isset($item['aggregateRating']))
                            ) {
                                $todosSonProductosSinOffers = false;
                                break;
                            }
                        }

                        if ($todosSonProductosSinOffers) {
                            return ''; 
                        }
                    }
                }

                return $matches[0]; 
            },
            $content
        );
    });
});

add_filter( 'woocommerce_breadcrumb_defaults', 'custom_woocommerce_breadcrumbs' );
function custom_woocommerce_breadcrumbs( $defaults ) {
    $defaults['delimiter'] = ' > '; // Cambia esto por el separador que quieras
    return $defaults;
}


add_action('wp_enqueue_scripts', 'remove_klaviyo_checkout_scripts', 100);
function remove_klaviyo_checkout_scripts() {
    wp_dequeue_script('klaviyo-klaviyo-checkout-block-editor-script');
    wp_dequeue_script('klaviyo-klaviyo-checkout-block-view-script');
    wp_deregister_script('klaviyo-klaviyo-checkout-block-editor-script');
    wp_deregister_script('klaviyo-klaviyo-checkout-block-view-script');
}


add_filter('wcboost_products_compare_link_attributes', function($attrs) {
    if (isset($attrs['rel']) && $attrs['rel'] === 'nofollow') {
        unset($attrs['rel']);
    }
    return $attrs;
});



/*llamamos a el achivo con el codigo para añadir el campo de html para los subproductos */
require_once get_stylesheet_directory() . '/custom-html-field.php';

add_filter( 'woocommerce_checkout_fields', 'add_phone_to_billing_and_shipping' );

function add_phone_to_billing_and_shipping( $fields ) {
    // Agregar campo de teléfono en la sección de envío
    $fields['shipping']['shipping_phone'] = array(
        'type'        => 'text',
        'label'       => 'Kontakttelefon',
        'required'    => true, 
        'priority'    => 20,   
        'class'       => array( 'form-row-wide' ),
    );
    return $fields;
}


function agregar_campo_deposito_producto() {
    global $post;
    
    woocommerce_wp_text_input(
        array(
            'id' => '_deposito_producto',
            'label' => 'Kaution (€)',
            'desc_tip' => 'true',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        )
    );
}
add_action('woocommerce_product_options_pricing', 'agregar_campo_deposito_producto');



// Guardar el valor del depósito en la base de datos
function guardar_campo_deposito_producto($post_id) {
    $deposito = isset($_POST['_deposito_producto']) ? sanitize_text_field($_POST['_deposito_producto']) : '';
    update_post_meta($post_id, '_deposito_producto', $deposito);
}
add_action('woocommerce_process_product_meta', 'guardar_campo_deposito_producto');

// Shortcode para mostrar el depósito de seguridad en el frontend
function shortcode_mostrar_deposito() {
    global $product;

    if (!is_product()) return '';

    // Obtener el valor del depósito desde el meta del producto
    $deposito = get_post_meta($product->get_id(), '_deposito_producto', true);

    if (!empty($deposito)) {
        return '<p><strong>Kaution:</strong> ' . wc_price($deposito) . '</p>';
    }

    return ''; // No mostrar nada si el producto no tiene depósito
}
add_shortcode('mostrar_deposito', 'shortcode_mostrar_deposito');

function agregar_deposito_sin_iva_al_pedido($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $deposito_total = 0;

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $deposito = get_post_meta($product_id, '_deposito_producto', true);

        if (!empty($deposito)) {
            $deposito_total += $deposito * $cart_item['quantity'];
        }
    }

    if ($deposito_total > 0) {
        // Agregar el depósito como un cargo adicional sin IVA
        $cart->add_fee(__('Kaution', 'woocommerce'), $deposito_total, false);
    }
}
add_action('woocommerce_cart_calculate_fees', 'agregar_deposito_sin_iva_al_pedido');



function guardar_deposito_en_pedido_checkout($order_id) {
    $deposito_total = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $deposito = get_post_meta($product_id, '_deposito_producto', true);

        if (!empty($deposito)) {
            $deposito_total += $deposito;
        }
    }

    if ($deposito_total > 0) {
        update_post_meta($order_id, '_deposito_retenido', $deposito_total);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'guardar_deposito_en_pedido_checkout');




function agregar_boton_reembolso_dentro_pedido($order) {
    $order_id = $order->get_id();
    $deposito = get_post_meta($order_id, '_deposito_retenido', true);

    if (!empty($deposito)) {
        echo '<h3>' . __('Kaution', 'woocommerce') . '</h3>';
        echo '<p>' . __('Einbehaltener Betrag: ', 'woocommerce') . wc_price($deposito) . '</p>';
        echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=reembolsar_deposito&order_id=' . $order_id), 'reembolsar_deposito')) . '" 
                class="button button-primary">' . __('Anzahlung zurückerstatten', 'woocommerce') . '</a>';
    }
}
add_action('woocommerce_admin_order_data_after_order_details', 'agregar_boton_reembolso_dentro_pedido');


function procesar_reembolso_deposito() {
    if (!isset($_GET['order_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'reembolsar_deposito')) {
        defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("🔴 Error de seguridad al intentar reembolsar el depósito.");
        wp_die(__('Error de seguridad.', 'woocommerce'));
    }

    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    if (!$order) {
        defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("❌ No se pudo obtener la orden.");
        wp_die(__('No se pudo obtener la orden.', 'woocommerce'));
    }

    $deposito = get_post_meta($order_id, '_deposito_retenido', true);
    defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("🔍 Depósito retenido: " . print_r($deposito, true));

    if (!empty($deposito) && $deposito > 0) {
        // Obtener el ID de la transacción de Stripe
        $payment_id = $order->get_transaction_id();
        if (!$payment_id) {
            defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("❌ No se encontró el ID de la transacción de Stripe.");
            wp_die(__('No se encontró un pago asociado a este pedido.', 'woocommerce'));
        }

        // Crear el reembolso en WooCommerce
        $refund = wc_create_refund(array(
            'amount' => floatval($deposito),
            'reason' => __('Reembolso del depósito de seguridad', 'woocommerce'),
            'order_id' => $order_id,
            'refund_payment' => true, // Intenta reembolsar automáticamente a Stripe
        ));

        if (is_wp_error($refund)) {
            defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("❌ Error al crear el reembolso: " . $refund->get_error_message());
            wp_die(__('Error al procesar el reembolso: ', 'woocommerce') . $refund->get_error_message());
        } else {
            defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("✅ Reembolso exitoso para el pedido $order_id con monto de $deposito.");

            // Eliminar el metadato del depósito
            update_post_meta($order_id, '_deposito_retenido', 0);
            $order->add_order_note(__('Depósito de seguridad reembolsado.', 'woocommerce'));
            $order->update_status('completed'); // Cambia el estado del pedido a "Completado"
        }
    } else {
        defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && error_log("❌ No hay depósito retenido en el pedido.");
        wp_die(__('No hay depósito retenido en este pedido.', 'woocommerce'));
    }

    wp_redirect(admin_url('post.php?post=' . $order_id . '&action=edit'));
    exit;
}
add_action('admin_post_reembolsar_deposito', 'procesar_reembolso_deposito');

function obtener_fechas_ocupadas($product_id) {
    $fechas_ocupadas = array();
    $ordenes = wc_get_orders(array(
        'status' => array('processing', 'completed'),
        'limit' => -1
    ));

    foreach ($ordenes as $orden) {
        foreach ($orden->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                // Obtener fecha de inicio y fin de la reserva
                $fecha_inicio = wc_get_order_item_meta($item->get_id(), 'Fecha de inicio', true);
                $fecha_fin = wc_get_order_item_meta($item->get_id(), 'Fecha de finalización', true);

                if ($fecha_inicio && $fecha_fin) {
                    $inicio = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
                    $fin = DateTime::createFromFormat('Y-m-d', $fecha_fin);

                    if ($inicio && $fin) {
                        // Bloqueamos los días ocupados de la reserva
                        while ($inicio <= $fin) {
                            $fechas_ocupadas[] = $inicio->format('d.m.Y'); // Ahora ya es formato Flatpickr alemán
                            $inicio->modify('+1 day');
                        }

                        // Bloquear los **dos días posteriores** a la reserva
                        for ($i = 1; $i <= 2; $i++) {
                            $dia_extra = clone $fin;
                            $dia_extra->modify("+$i day");
                            $fechas_ocupadas[] = $dia_extra->format('d.m.Y'); // También en d.m.Y
                        }
                    }
                }
            }
        }
    }

    return $fechas_ocupadas;
}

function shortcode_selector_semanal_alquiler() {
    ob_start();
    global $product;

    if (!is_product()) return '';

    // Obtener fechas ocupadas
    $product_id = $product->get_id();
    $fechas_ocupadas = obtener_fechas_ocupadas($product_id);

    ?>

    <div class="alquiler-palas-container">
        <div class="alquiler-palas-fechas">
            <label for="fecha_inicio">📅 Startdatum:</label>
            <input type="text" id="fecha_inicio" name="fecha_inicio" class="flatpickr-input" required placeholder="Wählen Sie das Datum aus">

            <label for="fecha_fin">📆 Enddatum:</label>
            <input type="text" id="fecha_fin" name="fecha_fin" class="flatpickr-input" required readonly>
        </div>
    </div>
        <div>
        <p id="mensaje_no_disponible" style="display: none; color: red;">⛔ derzeit nicht verfügbar</p>
    
    </div>

    <!-- Incluir Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script> <!-- Idioma alemán -->

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let fechaInicioInput = document.getElementById("fecha_inicio");
            let fechaFinInput = document.getElementById("fecha_fin");
            let mensajeNoDisponible = document.getElementById("mensaje_no_disponible");
            let addToCartForm = document.querySelector("form.cart");
            let fechasOcupadas = <?php echo json_encode($fechas_ocupadas); ?>;

            function fechaEstaOcupada(fecha) {
                return fechasOcupadas.includes(fecha);
            }

            let calendario = flatpickr(fechaInicioInput, {
                dateFormat: "d.m.Y", // Formato de fecha alemán (DD.MM.YYYY)
                minDate: "today",
                disable: fechasOcupadas, // Bloquear fechas ocupadas + 2 días posteriores
                locale: "de",
                position: "below",
                onChange: function (selectedDates, dateStr) {
                    if (!dateStr) return;

                    // Convertir la fecha seleccionada de "d.m.Y" a un objeto Date
                    let partesFecha = dateStr.split('.');
                    let fechaInicio = new Date(partesFecha[2], partesFecha[1] - 1, partesFecha[0]); // Y, M, D

                    // Calcular la fecha de finalización (+6 días)
                    let fechaFin = new Date(fechaInicio);
                    fechaFin.setDate(fechaFin.getDate() + 6);

                    // Verificar si alguna fecha dentro del rango está ocupada
                    let tempFecha = new Date(fechaInicio);
                    let ocupado = false;

                    for (let i = 0; i <= 6; i++) {
                        let fechaStr = ("0" + tempFecha.getDate()).slice(-2) + "." + ("0" + (tempFecha.getMonth() + 1)).slice(-2) + "." + tempFecha.getFullYear();
                        if (fechaEstaOcupada(fechaStr)) {
                            ocupado = true;
                            break;
                        }
                        tempFecha.setDate(tempFecha.getDate() + 1);
                    }

                    if (ocupado) {
                        mensajeNoDisponible.style.display = "block";
                        fechaInicioInput.value = "";
                        fechaFinInput.value = "";
                    } else {
                        mensajeNoDisponible.style.display = "none";

                        // Convertir la fecha final a formato "d.m.Y"
                        let fechaFinFormatted = ("0" + fechaFin.getDate()).slice(-2) + "." + ("0" + (fechaFin.getMonth() + 1)).slice(-2) + "." + fechaFin.getFullYear();

                        // Asignar la fecha final calculada al campo
                        fechaFinInput.value = fechaFinFormatted;
                    }
                }
            });

            if (addToCartForm) {
                addToCartForm.addEventListener("submit", function (event) {
                    if (!fechaInicioInput.value || !fechaFinInput.value) {
                        event.preventDefault();
                        alert("Bitte wählen Sie ein Startdatum aus, bevor Sie es in den Warenkorb legen.");
                        return false;
                    }

                    let inputFechaInicio = document.createElement("input");
                    inputFechaInicio.type = "hidden";
                    inputFechaInicio.name = "fecha_inicio";
                    inputFechaInicio.value = fechaInicioInput.value;

                    let inputFechaFin = document.createElement("input");
                    inputFechaFin.type = "hidden";
                    inputFechaFin.name = "fecha_fin";
                    inputFechaFin.value = fechaFinInput.value;

                    this.appendChild(inputFechaInicio);
                    this.appendChild(inputFechaFin);
                });
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('selector_alquiler', 'shortcode_selector_semanal_alquiler');



add_action('wp_footer', function() {
    if (is_checkout()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Encuentra el contenedor principal
                var contactFields = document.querySelector(".fc-contact-fields.fc-clearfix");
                if (contactFields) {
                    // Encuentra el siguiente nivel en la jerarquía
                    var wrapper = contactFields.querySelector(".fc-contact-fields__wrapper");
                    if (wrapper) {
                        var formRow = wrapper.querySelector(".form-row.kl_newsletter_checkbox_field.fc-checkbox-field.fc-no-validation-icon");
                        if (formRow) {
                            var inputWrapper = formRow.querySelector(".woocommerce-input-wrapper");
                            if (inputWrapper) {
                                // Encuentra y modifica el texto en fc-checkbox-label-text
                                var label = inputWrapper.querySelector(".fc-checkbox-label-text");
                                if (label) {
                                    label.innerText = "Melde dich an, um Updates und Neuigkeiten per E-Mail zu erhalten";

                                } else {

                                }
                            } else {

                            }
                        } else {

                        }
                    } else {

                    }
                } else {

                }
            });
        </script>';
    }
});

// Añadir campo personalizado H2 a las categorías de productos
// Agrega este código al archivo functions.php de tu tema o a un plugin personalizado

// 1. Añadir el campo al crear una nueva categoría
add_action('product_cat_add_form_fields', 'agregar_campo_h2_categoria', 10, 2);
function agregar_campo_h2_categoria() {
    ?>
    <div class="form-field">
        <label for="h2_categoria">H2 para Categoría</label>
        <input type="text" name="h2_categoria" id="h2_categoria" value="">
        <p class="description">Texto H2 personalizado para esta categoría</p>
    </div>
    <?php
}

// 2. Añadir el campo al editar una categoría existente
add_action('product_cat_edit_form_fields', 'editar_campo_h2_categoria', 10, 2);
function editar_campo_h2_categoria($term) {
    $h2_valor = get_term_meta($term->term_id, 'h2_categoria', true);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="h2_categoria">H2 para Categoría</label>
        </th>
        <td>
            <input type="text" name="h2_categoria" id="h2_categoria" value="<?php echo esc_attr($h2_valor); ?>" style="width: 100%;">
            <p class="description">Texto H2 personalizado para esta categoría</p>
        </td>
    </tr>
    <?php
}

// 3. Guardar el campo personalizado
add_action('created_product_cat', 'guardar_campo_h2_categoria');
add_action('edited_product_cat', 'guardar_campo_h2_categoria');
function guardar_campo_h2_categoria($term_id) {
    if (isset($_POST['h2_categoria'])) {
        update_term_meta($term_id, 'h2_categoria', sanitize_text_field($_POST['h2_categoria']));
    }
}

// 4. Crear el shortcode para mostrar el H2
add_shortcode('categoria_h2', 'mostrar_h2_categoria_shortcode');
function mostrar_h2_categoria_shortcode($atts) {
    // Si estamos en una página de categoría de producto
    if (is_product_category()) {
        $categoria_actual = get_queried_object();
        $h2_texto = get_term_meta($categoria_actual->term_id, 'h2_categoria', true);
        
        // Solo mostrar si el campo tiene contenido
        if (!empty($h2_texto)) {
            return '<h2 class="h2-categoria-personalizado">' . esc_html($h2_texto) . '</h2>';
        }
    }
    
    return ''; // No muestra nada si el campo está vacío o no estamos en una categoría
}
// Solución que debería funcionar
add_filter('do_shortcode_tag', function($output, $tag, $attr) {
    if ($tag === 'carousel_slide' && isset($attr['id'])) {
        global $custom_carousel_slider_id;
        $custom_carousel_slider_id = intval($attr['id']);
    }
    return $output;
}, 10, 3);

// Tu shortcode personalizado
add_shortcode('custom_carousel_slider', function ($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    if (!$atts['id']) return '';
    
    return do_shortcode('[carousel_slide id="' . intval($atts['id']) . '"]');
});

// Mostrar etiqueta "Agotado" en productos sin stock (en loop de tienda y archivo)
add_action('woocommerce_before_shop_loop_item_title', 'etiqueta_agotado_automatica', 10);
add_action('woocommerce_single_product_summary', 'etiqueta_agotado_automatica', 5);

function etiqueta_agotado_automatica() {
    global $product;

    if (!$product->is_in_stock()) {
        echo '<span class="etiqueta-agotado-mini">Ausverkauft</span>';
    }
}

function use_opensans_woff2() {
    ?>
    <style>
    @font-face {
        font-family: 'Open Sans';
        src: url('<?php echo get_stylesheet_directory_uri(); ?>/fonts/OpenSans-VariableFont_wdthwght.woff2') format('woff2');
        font-weight: 100 900; /* Variable font: todos los pesos */
        font-stretch: 75% 100%;
        font-display: swap;
        font-style: normal;
    }
    </style>
    <?php
}
add_action('wp_head', 'use_opensans_woff2', 1);

// PRELOAD OPEN SANS WOFF2
function preload_opensans_woff2() {
    echo '<link rel="preload" href="' . get_stylesheet_directory_uri() . '/fonts/OpenSans-VariableFont_wdthwght.woff2" as="font" type="font/woff2" crossorigin>';
}
add_action('wp_head', 'preload_opensans_woff2', 1);

// DESACTIVAR FUENTE ANTIGUA (TTF)
function remove_old_opensans_ttf() {
    wp_dequeue_style( 'opensans-ttf' );
    wp_deregister_style( 'opensans-ttf' );
}
add_action( 'wp_enqueue_scripts', 'remove_old_opensans_ttf', 100 );



add_action('wp_head', 'schema_itemlist_catalogo', 5);
function schema_itemlist_catalogo() {

    // ── Solo actuar en páginas de catálogo WooCommerce ────────────────────────
    $es_tienda    = function_exists('is_shop') && is_shop();
    $es_categoria = function_exists('is_product_category') && is_product_category();
    $es_tag       = function_exists('is_product_tag') && is_product_tag();

    if (!$es_tienda && !$es_categoria && !$es_tag) {
        return;
    }

    // ── Configurar la query igual que WooCommerce / Elementor ─────────────────
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 23,   // Ajusta al máximo de productos que muestra tu catálogo
        'fields'         => 'ids', // Solo necesitamos los IDs para ser eficientes
    );

    // Filtrar por categoría o tag si estamos en su archivo
    if ($es_categoria) {
        $categoria = get_queried_object();
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $categoria->term_id,
            ),
        );
    } elseif ($es_tag) {
        $tag = get_queried_object();
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $tag->term_id,
            ),
        );
    }

    $productos = get_posts($args);

    if (empty($productos)) {
        return;
    }

    // ── Nombre y URL de la lista ──────────────────────────────────────────────
    if ($es_tienda) {
        $nombre_lista = get_option('blogname') . ' — Tienda';
        $url_lista    = get_permalink(wc_get_page_id('shop'));
    } else {
        $objeto       = get_queried_object();
        $nombre_lista = $objeto->name;
        $url_lista    = get_term_link($objeto);
    }

    // ── Construir los ListItem ────────────────────────────────────────────────
    $items = array();
    $posicion = 1;

    foreach ($productos as $product_id) {
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_visible()) {
            continue;
        }

        // Precio: usar precio normal o de oferta si existe
        $precio = $product->get_price();

        // Imagen principal
        $imagen_id  = $product->get_image_id();
        $imagen_url = $imagen_id
            ? wp_get_attachment_image_url($imagen_id, 'woocommerce_single')
            : wc_placeholder_img_src('woocommerce_single');

        // Disponibilidad
        $disponible = $product->is_in_stock()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $item = array(
            '@type'    => 'ListItem',
            'position' => $posicion,
            'item'     => array(
                '@type'       => 'Product',
                '@id'         => get_permalink($product_id),
                'name'        => $product->get_name(),
                'url'         => get_permalink($product_id),
                'description' => wp_strip_all_tags($product->get_short_description()
                    ?: $product->get_description()),
                'image'       => $imagen_url,
                'sku'         => $product->get_sku(),
                'offers'      => array(
                    '@type'         => 'Offer',
                    'url'           => get_permalink($product_id),
                    'priceCurrency' => get_woocommerce_currency(),
                    'price'         => $precio,
                    'availability'  => $disponible,
                    'seller'        => array(
                        '@type' => 'Organization',
                        'name'  => get_bloginfo('name'),
                    ),
                ),
            ),
        );

        // Añadir rating si el producto tiene reseñas
        $rating_count = $product->get_rating_count();
        if ($rating_count > 0) {
            $item['item']['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => round($product->get_average_rating(), 1),
                'reviewCount' => $rating_count,
                'bestRating'  => '5',
                'worstRating' => '1',
            );
        }

        // Añadir marca si el producto tiene el atributo "marca" o "brand"
// Añadir marca si el producto tiene el atributo "marca" o "brand"
$marca = '';
$product_id = is_object($product_id) ? $product_id->ID : (int) $product_id;
$atributos_marca = array('pa_marca', 'pa_brand', 'pa_fabricante');
foreach ($atributos_marca as $attr) {
    $terminos = wc_get_product_terms($product_id, $attr, array('fields' => 'names'));
    if (!empty($terminos)) {
        $marca = $terminos[0];
        break;
    }
}
if (!empty($marca)) {
    $item['item']['brand'] = array(
        '@type' => 'Brand',
        'name'  => $marca,
    );
}
        $items[]   = $item;
        $posicion++;
    }

    if (empty($items)) {
        return;
    }

    // ── Schema final ─────────────────────────────────────────────────────────
    $schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $nombre_lista,
        'url'             => is_string($url_lista) ? $url_lista : '',
        'numberOfItems'   => count($items),
        'itemListElement' => $items,
    );

    echo '<script type="application/ld+json">'
        . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>' . "\n";
}


// =============================================================================
// CAMPOS PERSONALIZADOS Y SHORTCODES PARA PRODUCTOS
// =============================================================================

// -----------------------------------------------------------------------------
// 1) [product_video] — Video de YouTube con título y bullet points
// -----------------------------------------------------------------------------
// [product_warum] — Título H2 editable "Warum [producto]"
// -----------------------------------------------------------------------------
add_action('woocommerce_product_options_general_product_data', 'product_warum_admin_field');
function product_warum_admin_field() {
    echo '<div class="options_group">';
    echo '<p style="padding:10px 12px;font-weight:bold;background:#f8f8f8;margin:0;">Warum-Titel (H2)</p>';
    woocommerce_wp_text_input(array(
        'id'          => '_product_warum_title',
        'label'       => 'Warum-Titel',
        'desc_tip'    => true,
        'description' => 'Si se deja vacío se genera automáticamente: "Warum [nombre del producto]"',
        'placeholder' => 'Ej: Warum Adidas Metalbone Team 2026',
    ));
    woocommerce_wp_text_input(array('id' => '_product_warum_bullet1', 'label' => 'Punto clave 1'));
    woocommerce_wp_text_input(array('id' => '_product_warum_bullet2', 'label' => 'Punto clave 2'));
    woocommerce_wp_text_input(array('id' => '_product_warum_bullet3', 'label' => 'Punto clave 3'));
    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'product_warum_save_field');
function product_warum_save_field($post_id) {
    $fields = array('_product_warum_title', '_product_warum_bullet1', '_product_warum_bullet2', '_product_warum_bullet3');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

add_shortcode('product_warum', 'shortcode_product_warum');
function shortcode_product_warum() {
    global $product;
    if (!$product) {
        $product = wc_get_product(get_queried_object_id());
    }
    if (!$product) return '';

    $pid = $product->get_id();
    $custom_title = get_post_meta($pid, '_product_warum_title', true);
    $bullet1 = get_post_meta($pid, '_product_warum_bullet1', true);
    $bullet2 = get_post_meta($pid, '_product_warum_bullet2', true);
    $bullet3 = get_post_meta($pid, '_product_warum_bullet3', true);

    if (empty($custom_title)) {
        $custom_title = 'Warum ' . $product->get_name();
    }

    $bullets = array_filter(array($bullet1, $bullet2, $bullet3));
    if (empty($bullets)) return '';

    $out = '<div style="text-align:left;margin:20px 0;">';
    $out .= '<style>@media(max-width:767px){.pp-warum-h2{font-size:24px !important}}</style>';
    $out .= '<h2 class="pp-warum-h2" style="font-size:2em;font-weight:700;color:#333;margin-bottom:15px;text-align:left;">' . esc_html($custom_title) . '</h2>';
    $out .= '<div style="display:inline-block;text-align:left;">';
    foreach ($bullets as $b) {
        $out .= '<p style="margin:0 0 10px;font-size:1rem;line-height:1.5;color:#333;text-align:left;"><span style="font-weight:bold;font-size:1.1em;margin-right:6px;">&#10004;</span>' . esc_html($b) . '</p>';
    }
    $out .= '</div>';
    $out .= '</div>';

    return $out;
}

// Campos en el admin del producto
add_action('woocommerce_product_options_general_product_data', 'product_video_admin_fields');
function product_video_admin_fields() {
    echo '<div class="options_group">';
    echo '<p style="padding:10px 12px;font-weight:bold;background:#f8f8f8;margin:0;">Video del Producto</p>';
    woocommerce_wp_text_input(array(
        'id'          => '_product_video_youtube',
        'label'       => 'URL de YouTube',
        'description' => 'Pega aquí el enlace del video de YouTube',
        'desc_tip'    => true,
    ));
    woocommerce_wp_text_input(array(
        'id'          => '_product_video_titulo',
        'label'       => 'Título del video (H2)',
        'desc_tip'    => true,
        'description' => 'Título que aparecerá encima del video',
    ));
    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'product_video_save_fields');
function product_video_save_fields($post_id) {
    $fields = array('_product_video_youtube', '_product_video_titulo');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

add_shortcode('product_video', 'shortcode_product_video');
function shortcode_product_video($atts) {
    $atts = shortcode_atts(array('id' => ''), $atts);

    if (!empty($atts['id'])) {
        $product_id = intval($atts['id']);
    } else {
        global $product;
        $product_id = !empty($product) ? $product->get_id() : get_queried_object_id();
    }
    if (!$product_id) return '';

    $video   = get_post_meta($product_id, '_product_video_youtube', true);
    $titulo  = get_post_meta($product_id, '_product_video_titulo', true);
    if (empty($video)) return '';

    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $video, $m)) {
        $video_id = $m[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $video, $m)) {
        $video_id = $m[1];
    }

    $output = '';

    if ($video_id) {
        $thumbnail   = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        $upload_date = get_the_date('Y-m-d\TH:i:sP', $product_id);
        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'VideoObject',
            'name'        => !empty($titulo) ? $titulo : get_the_title($product_id),
            'description' => !empty($titulo) ? $titulo : get_the_title($product_id),
            'thumbnailUrl'=> $thumbnail,
            'uploadDate'  => $upload_date,
            'contentUrl'  => 'https://www.youtube.com/watch?v=' . $video_id,
            'embedUrl'    => 'https://www.youtube.com/embed/' . $video_id,
            'publisher'   => array('@type' => 'Organization', 'name' => get_bloginfo('name'), 'url' => get_site_url()),
        );
        $output .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    $output .= '<div class="category-video-wrapper">';
    if (!empty($titulo)) {
        $output .= '<h2 class="video-titulo">' . esc_html($titulo) . '</h2>';
    }
    $output .= '<div class="category-video" style="position:relative;width:100%;aspect-ratio:16/9;">';
    if ($video_id) {
        $output .= '<iframe style="position:absolute;top:0;left:0;width:100%;height:100%;" src="https://www.youtube.com/embed/' . $video_id . '" title="' . esc_attr($titulo) . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
    } else {
        $output .= $video;
    }
    $output .= '</div>';

    $output .= '</div>';
    return $output;
}


// -----------------------------------------------------------------------------
// 2) [product_faqs] — Preguntas frecuentes del producto
// -----------------------------------------------------------------------------

// Campos en el admin del producto
add_action('woocommerce_product_options_general_product_data', 'product_faqs_admin_fields');
function product_faqs_admin_fields() {
    global $post;
    $header = get_post_meta($post->ID, '_product_faqs_header', true);
    $faqs   = get_post_meta($post->ID, '_product_faqs', true);
    if (!is_array($faqs)) $faqs = array();
    ?>
    <style>
        #product-faqs-wrapper .pf-item { border:1px solid #ddd; border-radius:4px; padding:12px; margin-bottom:10px; background:#fafafa; }
        #product-faqs-wrapper .pf-item span.pf-label { display:block; font-weight:600; font-size:12px; color:#555; margin-bottom:4px; float:none; width:auto; }
        #product-faqs-wrapper .pf-item input[type=text],
        #product-faqs-wrapper .pf-item textarea { width:100%; margin-bottom:8px; box-sizing:border-box; }
    </style>
    <div class="options_group">
        <p style="padding:10px 12px;font-weight:bold;background:#f8f8f8;margin:0;">FAQs del Producto</p>
        <p class="form-field">
            <label for="_product_faqs_header">Título sección FAQs (H2)</label>
            <input type="text" name="_product_faqs_header" id="_product_faqs_header" value="<?php echo esc_attr($header); ?>" style="width:100%;" />
        </p>
        <p style="padding:4px 12px 4px;font-weight:600;color:#555;">Preguntas frecuentes</p>
        <div id="product-faqs-wrapper" style="padding:0 12px;">
            <?php foreach ($faqs as $i => $faq) : ?>
            <div class="pf-item">
                <span class="pf-label">Pregunta:</span>
                <input type="text" name="_product_faqs[<?php echo $i; ?>][question]" value="<?php echo esc_attr($faq['question']); ?>" />
                <span class="pf-label">Respuesta:</span>
                <textarea name="_product_faqs[<?php echo $i; ?>][answer]" rows="3"><?php echo esc_textarea($faq['answer']); ?></textarea>
                <a href="#" class="remove-product-faq button" style="color:red;">Eliminar</a>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="padding:8px 12px 10px;">
            <a href="#" id="add-product-faq" class="button">+ Añadir Pregunta</a>
        </p>
        <script>
        jQuery(document).ready(function($){
            var idx = <?php echo count($faqs); ?>;
            $('#add-product-faq').on('click', function(e){
                e.preventDefault();
                var html = '<div class="pf-item">';
                html += '<span class="pf-label">Pregunta:</span>';
                html += '<input type="text" name="_product_faqs['+idx+'][question]" value="" />';
                html += '<span class="pf-label">Respuesta:</span>';
                html += '<textarea name="_product_faqs['+idx+'][answer]" rows="3"></textarea>';
                html += '<a href="#" class="remove-product-faq button" style="color:red;">Eliminar</a>';
                html += '</div>';
                $('#product-faqs-wrapper').append(html);
                idx++;
            });
            $(document).on('click', '.remove-product-faq', function(e){
                e.preventDefault();
                $(this).closest('.pf-item').remove();
            });
        });
        </script>
    </div>
    <?php
}

add_action('woocommerce_process_product_meta', 'product_faqs_save_fields');
function product_faqs_save_fields($post_id) {
    if (isset($_POST['_product_faqs_header'])) {
        update_post_meta($post_id, '_product_faqs_header', sanitize_text_field($_POST['_product_faqs_header']));
    }
    if (isset($_POST['_product_faqs']) && is_array($_POST['_product_faqs'])) {
        $faqs = array_values(array_map(function($faq) {
            return array(
                'question' => sanitize_text_field($faq['question']),
                'answer'   => sanitize_textarea_field($faq['answer']),
            );
        }, $_POST['_product_faqs']));
        update_post_meta($post_id, '_product_faqs', $faqs);
    } else {
        delete_post_meta($post_id, '_product_faqs');
    }
}

add_shortcode('product_faqs', 'shortcode_product_faqs');
function shortcode_product_faqs($atts) {
    $atts = shortcode_atts(array('id' => ''), $atts);
    $product_id = !empty($atts['id']) ? intval($atts['id']) : get_the_ID();
    if (!$product_id) return '';

    $header = get_post_meta($product_id, '_product_faqs_header', true);
    $faqs   = get_post_meta($product_id, '_product_faqs', true);

    if (empty($faqs) || !is_array($faqs)) return '';

    ob_start();

    if (!empty($header)) {
        echo '<h2 style="font-size:22px;text-align:center;">' . esc_html($header) . '</h2>';
    }

    echo '<div class="faq-accordion">';
    foreach ($faqs as $faq) {
        echo '<div class="faq-item">';
        echo '<h3 class="faq-question">' . esc_html($faq['question']) . ' <span class="faq-icon">+</span></h3>';
        echo '<div class="faq-answer">' . nl2br(esc_html($faq['answer'])) . '</div>';
        echo '</div>';
    }
    echo '</div>';

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array(),
    );
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = array(
            '@type'          => 'Question',
            'name'           => $faq['question'],
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $faq['answer']),
        );
    }
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';

    return ob_get_clean();
}


// [padel_featured_products] está gestionado por el plugin woocommerce-featured-padel-updated

// -----------------------------------------------------------------------------
// [accordion title="..."]contenido[/accordion]
// -----------------------------------------------------------------------------
// Almacén de tabs para renderizar juntos al final
global $pp_tabs_store;
$pp_tabs_store = array();

add_shortcode('accordion', 'shortcode_accordion');
function shortcode_accordion($atts, $content = '') {
    global $pp_tabs_store;
    $atts = shortcode_atts(array('title' => ''), $atts);

    static $group = 0;
    // Primer tab del grupo → nuevo grupo
    if (empty($pp_tabs_store)) $group++;

    $pp_tabs_store[] = array(
        'title'   => $atts['title'],
        'content' => do_shortcode(wp_kses_post($content)),
        'group'   => $group,
    );

    // Devolvemos placeholder — se reemplaza en shutdown del shortcode
    return '<!-- pp-tab-placeholder-' . (count($pp_tabs_store) - 1) . ' -->';
}

add_shortcode('tabs_end', 'shortcode_tabs_end');
function shortcode_tabs_end($atts) {
    global $pp_tabs_store;
    if (empty($pp_tabs_store)) return '';

    ob_start();
    echo '<div class="pp-tabs">';
    echo '<div class="pp-tabs-nav">';
    foreach ($pp_tabs_store as $i => $tab) {
        $active = $i === 0 ? ' active' : '';
        echo '<button class="pp-tab-btn' . $active . '">' . esc_html($tab['title']) . '</button>';
    }
    echo '</div>';
    foreach ($pp_tabs_store as $i => $tab) {
        $active = $i === 0 ? ' active' : '';
        echo '<div class="pp-tab-panel' . $active . '">' . $tab['content'] . '</div>';
    }
    echo '</div>';

    $pp_tabs_store = array();
    return ob_get_clean();
}

// Estilos y JS (solo en producto)
add_action('wp_head', 'padelprofi_accordion_assets');
function padelprofi_accordion_assets() {
    if (!is_singular('product')) return;
    ?>
    <style>
    .pp-tabs { margin: 20px 0; }
    .pp-tabs-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 0;
    }
    .pp-tab-btn {
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        color: #555;
        transition: color 0.2s, border-color 0.2s;
    }
    .pp-tab-btn:hover { color: #111; }
    .pp-tab-btn.active { color: #fe6100; border-bottom-color: #fe6100; }
    .pp-tab-panel {
        display: none;
        padding: 20px 4px;
        font-size: 14px;
        animation: ppFadeIn 0.2s ease;
    }
    .pp-tab-panel.active { display: block; }
    .pp-tab-panel ul { padding-left: 18px; }
    .pp-tab-panel li { margin-bottom: 6px; }
    @keyframes ppFadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.pp-tabs').forEach(function(tabs) {
            var btns   = tabs.querySelectorAll('.pp-tab-btn');
            var panels = tabs.querySelectorAll('.pp-tab-panel');
            btns.forEach(function(btn, i) {
                btn.addEventListener('click', function() {
                    btns.forEach(function(b) { b.classList.remove('active'); });
                    panels.forEach(function(p) { p.classList.remove('active'); });
                    btn.classList.add('active');
                    panels[i].classList.add('active');
                });
            });
        });
    });
    </script>
    <?php
}


// -----------------------------------------------------------------------------
// [product_shipping] — Bloque de envío gratis para ficha de producto
// -----------------------------------------------------------------------------
add_shortcode('product_shipping', 'shortcode_product_shipping');
function shortcode_product_shipping() {
    global $product;

    if (!$product) {
        $product = wc_get_product(get_queried_object_id());
    }
    if (!$product) return '';

    $custom_delivery = get_post_meta($product->get_id(), '_custom_delivery_delay', true);
    $isotype = 'https://padelprofideutschland.de/wp-content/uploads/2024/12/Padel-Profi-Favicon.png';

    $now_ts = current_time('timestamp');
    $dayN   = date('N', $now_ts);
    $hourG  = date('G', $now_ts);

    if (has_term('envios-urgentes', 'product_cat', $product->get_id()) && $dayN >= 1 && $dayN <= 4 && $hourG < 16) {
        if (class_exists('IntlDateFormatter')) {
            $tz  = get_option('timezone_string') ?: 'Europe/Berlin';
            $fmt = new IntlDateFormatter('de_DE', IntlDateFormatter::LONG, IntlDateFormatter::NONE, $tz, IntlDateFormatter::GREGORIAN, "EEEE, 'den' d. MMMM");
            $fecha_entrega = $fmt->format(strtotime('+1 weekday', $now_ts));
        } else {
            $fecha_entrega = date('l, \d\e\n d. F', strtotime('+1 weekday', $now_ts));
        }
        $lieferung = 'Lieferung am ' . esc_html($fecha_entrega);
    } elseif ($custom_delivery === 'yes') {
        $lieferung = 'Lieferung in 4–5 Tagen';
    } else {
        $lieferung = 'Lieferung in 48–72 Std.';
    }

    ob_start();
    ?>
    <div class="free-shipping-catalog pp-shipping-mobile" style="margin:8px 0;text-align:center;">
        <p style="margin:0; font-size:14px; line-height:1.4;">
            <span style="color:#00bf63; font-weight:600;">Gratisversand.</span>
            <span style="color:#000;"><?php echo $lieferung; ?></span>
        </p>
    </div>
    <?php
    return ob_get_clean();
}

// -----------------------------------------------------------------------------
// [whatsapp_expert] — Botón de WhatsApp para ficha de producto
// -----------------------------------------------------------------------------
add_shortcode('whatsapp_expert', 'shortcode_whatsapp_expert');
function shortcode_whatsapp_expert() {
    global $product;
    $product_name = '';
    if ($product) {
        $product_name = $product->get_name();
    } elseif (is_singular('product')) {
        $p = wc_get_product(get_queried_object_id());
        if ($p) $product_name = $p->get_name();
    }

    $phone = '4915560898765';
    $text = rawurlencode('Hallo! Ich interessiere mich für: ' . $product_name);
    $url = 'https://wa.me/' . $phone . '?text=' . $text;

    return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" class="pp-whatsapp-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fff" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Sprich mit einem Padel-Experten
    </a>
    <style>
    .pp-whatsapp-btn{display:flex;align-items:center;justify-content:center;gap:10px;padding:12px 20px;background:#25D366;color:#fff !important;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;transition:background .3s ease;margin-top:10px}
    .pp-whatsapp-btn:hover{background:#1da851;color:#fff !important}
    </style>';
}

// Fix: Owl Carousel sets inline height on .owl-stage-outer via JS, clipping the button on some phones (especially iOS Safari).
// CSS height:auto !important works on Chrome but not always on Safari. This JS fix clears the inline height after Owl initializes.
add_action('wp_footer', function() {
    ?>
    <script>
    (function() {
        function fixOwlStageHeight() {
            document.querySelectorAll('#loopy-1 .owl-stage-outer, #loopy-2 .owl-stage-outer, #loopy-3 .owl-stage-outer, #loopy-4 .owl-stage-outer, #loopy-5 .owl-stage-outer, #loopy-6 .owl-stage-outer').forEach(function(el) {
                el.style.removeProperty('height');
            });
        }

        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('initialized.owl.carousel', function() {
                fixOwlStageHeight();
            });
            jQuery(window).on('load', function() {
                setTimeout(fixOwlStageHeight, 300);
            });
        } else {
            window.addEventListener('load', function() {
                setTimeout(fixOwlStageHeight, 500);
            });
        }
    })();
    </script>
    <?php
});// ============================================
// CROSS-SELL PACK — Shortcode [pack_crossell]
// ============================================

function get_complementary_product($product_id) {
    $manual_id = get_post_meta($product_id, '_pack_complementario_id', true);
    if ($manual_id && wc_get_product(intval($manual_id))) {
        return wc_get_product(intval($manual_id));
    }
    return null;
}

function shortcode_pack_crossell() {
    if (!is_product()) return '';

    global $product;
    $product_id    = $product->get_id();
    $complementary = get_complementary_product($product_id);

    if (!$complementary) return '';

    $comp_id = $complementary->get_id();

    $p1_name  = $product->get_name();
    $p1_img   = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
    $p1_price = $product->is_on_sale() ? (float)$product->get_sale_price() : (float)$product->get_regular_price();

    $p2_name  = $complementary->get_name();
    $p2_img   = wp_get_attachment_image_url($complementary->get_image_id(), 'thumbnail');
    $p2_price = $complementary->is_on_sale() ? (float)$complementary->get_sale_price() : (float)$complementary->get_regular_price();

    $total = wc_price($p1_price + $p2_price);

    ob_start();
    ?>
    <div class="pck-wrap">

        <!-- TÍTULO -->
        <div class="pck-title">Häufig zusammen gekauft</div>

        <!-- PRODUCTO 1 — marcado como activo -->
        <div class="pck-card pck-card--active">
            <div class="pck-active-badge">
                <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
                    <polyline points="1.5,6 4.5,9.5 10.5,2.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Ausgewählt
            </div>
            <div class="pck-img">
                <img src="<?php echo esc_url($p1_img); ?>" alt="<?php echo esc_attr($p1_name); ?>">
            </div>
            <div class="pck-info">
                <span class="pck-name"><?php echo esc_html($p1_name); ?></span>
                <span class="pck-price"><?php echo wc_price($p1_price); ?></span>
            </div>
        </div>

        <!-- SEPARADOR -->
        <div class="pck-plus">+</div>

        <!-- PRODUCTO 2 — clicable -->
        <a class="pck-card pck-card--link" href="<?php echo esc_url(get_permalink($comp_id)); ?>">
            <div class="pck-img">
                <img src="<?php echo esc_url($p2_img); ?>" alt="<?php echo esc_attr($p2_name); ?>">
            </div>
            <div class="pck-info">
                <span class="pck-name"><?php echo esc_html($p2_name); ?></span>
                <span class="pck-price"><?php echo wc_price($p2_price); ?></span>
            </div>
        </a>

        <!-- FOOTER: total + botón -->
        <div class="pck-footer">
            <span class="pck-total"><?php echo $total; ?></span>
            <button class="pck-btn"
                data-product1="<?php echo esc_attr($product_id); ?>"
                data-product2="<?php echo esc_attr($comp_id); ?>">
                Beide hinzufügen
            </button>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pack_crossell', 'shortcode_pack_crossell');

// ============================================
// CSS
// ============================================
function estilos_pack_crossell() {
    if (!is_product()) return;
    ?>
    <style>
    .pck-wrap {
        display: flex;
        flex-direction: column;
        gap: 0;
        margin: 20px 10px;
        padding-right:15px;
        font-family: inherit;
        max-width: 100%;
    }

    /* TÍTULO */
    .pck-title {
        font-size: 15px;
        font-weight: 800;
        color: #111;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        text-align: center;
        padding: 4px 0 10px;
    }

    /* TARJETA BASE */
    .pck-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1.5px solid #e8e8e8;
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }

    /* TARJETA ACTIVA — borde naranja */
    .pck-card--active {
        border: 2px solid #fe6100;
        box-shadow: 0 2px 12px rgba(254,97,0,0.13);
        padding-top: 28px;
    }

    /* BADGE "Ausgewählt" */
    .pck-active-badge {
        position: absolute;
        top: -1px;
        left: 14px;
        background: #fe6100;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
        padding: 3px 8px 3px 6px;
        border-radius: 0 0 7px 7px;
        display: flex;
        align-items: center;
        gap: 4px;
        line-height: 1.4;
    }

    /* IMAGEN */
    .pck-img {
        flex-shrink: 0;
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pck-img img {
        width: 72px;
        height: 72px;
        object-fit: contain;
        display: block;
    }

    /* INFO */
    .pck-info {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }
    .pck-name {
        font-size: 13px;
        font-weight: 600;
        color: #222;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pck-price {
        font-size: 15px;
        font-weight: 700;
        color: #fe6100;
    }
    .pck-price .woocommerce-Price-currencySymbol {
        font-size: 15px;
    }

    /* SEPARADOR + */
    .pck-plus {
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        color: #111;
        padding: 8px 0;
        line-height: 1;
    }

    /* FOOTER */
    .pck-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        flex-wrap: wrap;
    }
    .pck-total {
        font-size: 22px;
        font-weight: 800;
        color: #111;
        line-height: 1;
    }
    .pck-total .woocommerce-Price-currencySymbol {
        font-size: 22px;
    }

    /* BOTÓN */
    .pck-btn {
        background-color: #fe6100;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .pck-btn:hover {
        background-color: #d95200;
    }
    .pck-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* TARJETA CLICABLE */
    .pck-card--link {
        text-decoration: none;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    }
    .pck-card--link:hover {
        border-color: #fe6100;
        box-shadow: 0 4px 16px rgba(254,97,0,0.18);
        transform: translateY(-2px);
    }
    .pck-card--link .pck-name {
        color: #222;
    }
    .pck-card--link .pck-price {
        color: #fe6100;
    }

    /* MÓVIL */
    @media (max-width: 600px) {
        .pck-footer {
            flex-direction: column;
            align-items: flex-start;
        }
        .pck-btn {
            width: 100%;
            text-align: center;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'estilos_pack_crossell');

// ============================================
// SCRIPT AJAX
// ============================================
function script_pack_crossell() {
    if (!is_product()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).on('click', '.pck-btn', function(e) {
            e.preventDefault();
            var $btn     = $(this);
            var product1 = $btn.data('product1');
            var product2 = $btn.data('product2');

            $btn.prop('disabled', true).text('...');

            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: {
                    action: 'add_pack_to_cart',
                    product1: product1,
                    product2: product2,
                    nonce: '<?php echo wp_create_nonce('add_pack_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $btn.text('✓ Hinzugefügt');
                        $(document.body).trigger('wc_fragment_refresh');
                        setTimeout(function() {
                            $btn.prop('disabled', false).text('Zum Pack hinzufügen');
                        }, 2500);
                    } else {
                        $btn.prop('disabled', false).text('Fehler – erneut versuchen');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Verbindungsfehler');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'script_pack_crossell', 999);

// ============================================
// AJAX HANDLER
// ============================================
function ajax_add_pack_to_cart() {
    check_ajax_referer('add_pack_nonce', 'nonce');

    $p1 = isset($_POST['product1']) ? intval($_POST['product1']) : 0;
    $p2 = isset($_POST['product2']) ? intval($_POST['product2']) : 0;

    if (!$p1 || !$p2) {
        wp_send_json_error(array('message' => 'IDs no válidos'));
        return;
    }

    $added1 = WC()->cart->add_to_cart($p1, 1);
    $added2 = WC()->cart->add_to_cart($p2, 1);

    if ($added1 && $added2) {
        WC()->cart->calculate_totals();
        wp_send_json_success(array(
            'message'    => 'Pack añadido',
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ));
    } else {
        wp_send_json_error(array('message' => 'Error al añadir al carrito'));
    }
}
add_action('wp_ajax_add_pack_to_cart',        'ajax_add_pack_to_cart');
add_action('wp_ajax_nopriv_add_pack_to_cart', 'ajax_add_pack_to_cart');

// ============================================
// META BOX ADMIN — buscador de producto
// ============================================
function pack_crossell_meta_box() {
    add_meta_box(
        'pack_crossell_manual',
        ' Pack Cross-sell — Producto complementario',
        'pack_crossell_meta_box_html',
        'product',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'pack_crossell_meta_box');



function pack_crossell_meta_box_html($post) {
    $saved_id      = get_post_meta($post->ID, '_pack_complementario_id', true);
    $saved_product = $saved_id ? wc_get_product(intval($saved_id)) : null;
    wp_nonce_field('pack_crossell_save', 'pack_crossell_nonce');
    ?>
    <p style="font-size:12px;color:#666;margin-bottom:10px;">
        Busca y selecciona el producto complementario del pack.
    </p>

    <select
        id="pack_complementario_id"
        name="pack_complementario_id"
        class="wc-product-search"
        style="width:100%;"
        data-placeholder="Buscar producto..."
        data-action="woocommerce_json_search_products_and_variations"
        data-allow_clear="true"
    >
        <?php if ($saved_id && $saved_product) : ?>
            <option value="<?php echo esc_attr($saved_id); ?>" selected="selected">
                <?php echo esc_html($saved_product->get_name() . ' (#' . $saved_id . ')'); ?>
            </option>
        <?php endif; ?>
    </select>

    <?php if ($saved_product) :
        $img   = wp_get_attachment_image_url($saved_product->get_image_id(), 'thumbnail');
        $price = $saved_product->is_on_sale()
            ? wc_price($saved_product->get_sale_price())
            : wc_price($saved_product->get_regular_price());
    ?>
    <div style="margin-top:12px;padding:10px;background:#f9f9f9;border-radius:6px;display:flex;align-items:center;gap:10px;">
        <?php if ($img) : ?>
            <img src="<?php echo esc_url($img); ?>" style="width:48px;height:48px;object-fit:contain;border:1px solid #eee;border-radius:4px;">
        <?php endif; ?>
        <div>
            <strong style="font-size:12px;"><?php echo esc_html($saved_product->get_name()); ?></strong><br>
            <span style="font-size:12px;color:#fe6100;"><?php echo $price; ?></span>
        </div>
    </div>
    <?php endif; ?>
    <?php
}

// ============================================
// ENCOLAR SELECT2 EN ADMIN
// ============================================
function pack_crossell_admin_scripts($hook) {
    global $post;
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    if (!$post || $post->post_type !== 'product') return;

    wp_enqueue_script('select2');
    wp_enqueue_style('select2');
    wp_enqueue_script('woocommerce_admin');
    wp_enqueue_script('wc-enhanced-select');
    wp_enqueue_style('woocommerce_admin_styles');
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#pack_complementario_id').filter(':not(.select2-hidden-accessible)').each(function() {
            $(this).selectWoo({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            action: 'woocommerce_json_search_products_and_variations',
                            security: '<?php echo wp_create_nonce('search-products'); ?>'
                        };
                    },
                    processResults: function(data) {
                        var terms = [];
                        if (data) {
                            $.each(data, function(id, text) {
                                terms.push({ id: id, text: text });
                            });
                        }
                        return { results: terms };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                allowClear: true,
                placeholder: 'Buscar producto...'
            });
        });
    });
    </script>
    <?php
}
add_action('admin_enqueue_scripts', 'pack_crossell_admin_scripts');

// ============================================
// GUARDAR META
// ============================================
function pack_crossell_save_meta($post_id) {
    if (!isset($_POST['pack_crossell_nonce'])) return;
    if (!wp_verify_nonce($_POST['pack_crossell_nonce'], 'pack_crossell_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $value = isset($_POST['pack_complementario_id']) ? intval($_POST['pack_complementario_id']) : '';
    update_post_meta($post_id, '_pack_complementario_id', $value);
}
add_action('save_post', 'pack_crossell_save_meta');

// Uso: [foto_producto size="200"]
add_shortcode('foto_producto', function ($atts) {
    if (!is_product()) return '';

    global $product;
    $atts    = shortcode_atts(['size' => '120'], $atts);
    $size    = intval($atts['size']) . 'px';
    $img_url = wp_get_attachment_image_url($product->get_image_id(), 'full');

    if (!$img_url) return '';

    return '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($product->get_name()) . '" style="width:' . $size . ';height:' . $size . ';object-fit:contain;">';
});


/**
 * ============================================================
 * SEO FIX: Canonical + Noindex + Redirects para URLs con parámetros
 * (orderby, filtros de precio WPF, búsqueda interna)
 * ============================================================
 */

// 1. Forzar canonical a la URL limpia cuando hay parámetros de orderby/filtros
if ( ! function_exists( 'pp_fix_canonical_parametros' ) ) {
    function pp_fix_canonical_parametros( $canonical ) {
        if ( ! empty( $_GET['orderby'] ) || ! empty( $_GET['wpf_min_price'] ) || ! empty( $_GET['wpf_max_price'] ) || ! empty( $_GET['wpf_fbv'] ) ) {
            $clean_url = strtok( $_SERVER['REQUEST_URI'], '?' );
            return home_url( $clean_url );
        }
        return $canonical;
    }
    add_filter( 'rank_math/frontend/canonical', 'pp_fix_canonical_parametros' );
}

// 2. Redirigir búsquedas vacías (/?s=) a home, y limpiar el "?" colgante sin parámetros
if ( ! function_exists( 'pp_redirect_busqueda_vacia_y_query_vacio' ) ) {
    function pp_redirect_busqueda_vacia_y_query_vacio() {

        // Búsqueda vacía: /?s=
        if ( is_search() && empty( trim( get_search_query() ) ) ) {
            wp_redirect( home_url( '/' ), 301 );
            exit;
        }

        // "?" colgando sin parámetros reales (ej: /categoria/page/2/?)
        if ( isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING'] === '' && strpos( $_SERVER['REQUEST_URI'], '?' ) !== false ) {
            $clean_url = strtok( $_SERVER['REQUEST_URI'], '?' );
            wp_redirect( home_url( $clean_url ), 301 );
            exit;
        }
    }
    add_action( 'template_redirect', 'pp_redirect_busqueda_vacia_y_query_vacio' );
}


/**
 * Desactivar completamente el Schema automático de Rank Math en la Home
 * para permitir la carga exclusiva de nuestro fragmento JSON-LD personalizado.
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    if ( is_front_page() || is_home() ) {
        return array(); // Vacía por completo el marcado automático de Rank Math en la Home
    }
    return $data;
}, 99, 2 );

// Traducir mensajes de stock de WooCommerce que aparecen en inglés
add_filter( 'gettext', function( $translated, $original, $domain ) {
    if ( 'woocommerce' !== $domain ) return $translated;
    $map = [
        'You cannot add that amount to the cart — we have %1$s in stock and you already have %2$s in your cart.'
            => 'Sie können diese Menge nicht in den Warenkorb legen – wir haben %1$s auf Lager und Sie haben bereits %2$s in Ihrem Warenkorb.',
        'You cannot add that amount to the cart — we have %1$s in stock and you already have %2$s of this item in your cart.'
            => 'Sie können diese Menge nicht in den Warenkorb legen – wir haben %1$s auf Lager und Sie haben bereits %2$s dieses Artikels in Ihrem Warenkorb.',
    ];
    return $map[ $original ] ?? $translated;
}, 10, 3 );




