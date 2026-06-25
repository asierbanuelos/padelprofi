<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.0.2' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);

			/*
			 * Editor Style.
			 */
			add_editor_style( 'classic-editor.css' );

			/*
			 * Gutenberg wide images.
			 */
			add_theme_support( 'align-wide' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Admin notice
if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

add_action('elementor/frontend/after_register_styles',function() {
foreach( [ 'solid', 'regular', 'brands' ] as $style ) {
wp_deregister_style( 'elementor-icons-fa-' . $style );
}
}, 20 );

add_action( 'wp_enqueue_scripts', 'disable_eicons', 11 );
function disable_eicons() {
wp_dequeue_style( 'elementor-icons' );
wp_deregister_style( 'elementor-icons' );
}




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
    $timestamp = time();

    wp_enqueue_style( 'custom-carousel-styles', get_stylesheet_directory_uri() . '/assets/css/carousel-product.css', array(), $timestamp );
    wp_enqueue_style( 'custom-review-styles', get_stylesheet_directory_uri() . '/assets/css/custom-styles.css', array(), $timestamp );
    wp_enqueue_style( 'custom-product-detail', get_stylesheet_directory_uri() . '/assets/css/product-detail.css', array(), $timestamp );
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_carousel_styles', 100 );


/**
 * Enqueue Swiper.js scripts and styles for the subcategories slider
 */
function enqueue_subcategories_slider_assets() {
    $timestamp = time();
    // Registrar y encolar el CSS de Swiper
    wp_enqueue_style( 'subcat-swiper-css', 'https://unpkg.com/swiper@8/swiper-bundle.min.css', array(), '8.4.4' );

    // Registrar y encolar el JS de Swiper
    wp_enqueue_script( 'subcat-swiper-js', 'https://unpkg.com/swiper@8/swiper-bundle.min.js', array(), '8.4.4', true );

    // Encolar el script de inicialización personalizado
    wp_enqueue_script( 'subcat-slider-init', get_stylesheet_directory_uri() . '/assets/js/subcat-slider-init.js', array( 'subcat-swiper-js' ), $timestamp, true );
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

    if ( $rating > 0 ) {
        $html  = '<div class="rating-container">';
        $html .= '<span class="rating-value"><strong>' . esc_html( $rating ) . '/5</strong></span>'; // Puntuación
        $html .= '<span class="star-icon"><i class="fa-solid fa-star"></i></span>'; // Estrella
        if ( $count > 0 ) {
            $html .= '<span class="review-count">' . esc_html( $count ) . ' reviews</span>'; // Número de opiniones
        }
        $html .= '</div>';
    }
    return $html;
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

// Modify the shortcode function
function render_product_subcategories_slider() {
    $category_id = get_queried_object_id();

    // Check if custom subcategories are enabled
    $custom_subcategories_enabled = get_term_meta($category_id, 'custom_subcategories_enabled', true);
    $custom_subcategories = get_term_meta($category_id, 'custom_subcategories', true);

    if ($custom_subcategories_enabled && !empty($custom_subcategories)) {
        // Use the custom subcategories
        $subcategories = $custom_subcategories;
    } else {
        // Fetch the default subcategories
        $args = array(
            'taxonomy'   => 'product_cat',
            'parent'     => $category_id,
            'hide_empty' => false,
        );
        $terms = get_terms($args);

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        // Build subcategories array in the same format as custom ones
        $subcategories = array();
        foreach ($terms as $term) {
            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
            $subcategory_link = get_term_link($term->term_id);
            $subcategories[] = array(
                'title' => $term->name,
                'image' => $image_url,
                'url'   => $subcategory_link,
            );
        }
    }

    // Generate the HTML output
    ob_start();
    ?>
    <div class="subcat-slider-wrapper">
        <div class="subcat-slider-container swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($subcategories as $subcategory) : ?>
                <div class="swiper-slide subcat-slide-item">
                    <a href="<?php echo esc_url($subcategory['url']); ?>" class="subcat-slide-link">
                        <div class="subcat-slide-image">
                            <img src="<?php echo esc_url($subcategory['image']); ?>" alt="<?php echo esc_attr($subcategory['title']); ?>" class="subcat-slide-img" >
                        </div>
                        <h3 class="subcat-slide-title"><?php echo esc_html($subcategory['title']); ?></h3>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Navigation controls -->
            <div class="subcat-slider-button-prev subcat-slider-button-prev2 swiper-button-prev">
                <svg class="carousel-slider-nav-icon" viewBox="0 0 20 20"><path d="M14 5l-5 5 5 5-1 2-7-7 7-7z"></path></svg>
            </div>
            <div class="subcat-slider-button-next subcat-slider-button-next2 swiper-button-next">
                <svg class="carousel-slider-nav-icon" viewBox="0 0 20 20"><path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path></svg>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Registro del shortcode en WordPress
add_shortcode('render_subcategories_slider', 'render_product_subcategories_slider');





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
                ),
            );
            wp_editor( $content, $editor_id, $settings );
        ?>
        <p class="description"><?php esc_html_e( 'Add an additional description with rich text formatting.', 'wc-product-category' ); ?>.<br/>Using shortcode [wcpc_additional_description]</p>
    </div>
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
                <div class="description-content-2">
                    <?php echo wp_kses_post( $additional_description ); ?>
                </div>
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




// Shortcode para breacrumbs en pagina de categorias
function wc_categoria_breadcrumbs_shortcode() {
    // Obtener la instancia de WooCommerce
    global $woocommerce;

    // Obtener la categoría actual
    $queried_object = get_queried_object();

    // Si es una categoría de producto
    if ( is_product_category() ) {
        // Array para almacenar los elementos del breadcrumb
        $breadcrumbs = array();

        // Agregar el enlace a la tienda
        $breadcrumbs[] = '<a href="' . esc_url( home_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';

        // Obtener los ancestros de la categoría actual
        $ancestors = get_ancestors( $queried_object->term_id, 'product_cat' );

        // Invertir el array de ancestros para mostrarlos en el orden correcto
        $ancestors = array_reverse( $ancestors );

        // Agregar los enlaces a los ancestros
        foreach ( $ancestors as $ancestor ) {
            $ancestor = get_term( $ancestor, 'product_cat' );
            $breadcrumbs[] = '<a href="' . esc_url( get_term_link( $ancestor ) ) . '">' . esc_html( $ancestor->name ) . '</a>';
        }

        // Agregar el enlace a la categoría actual
        $breadcrumbs[] = $queried_object->name;

        // Unir los elementos del breadcrumb con el separador deseado
        return "<div class='cont-breadcrumbs'>".implode( ' > ', $breadcrumbs )."</div>";
    } else {
        return '';
    }
}
add_shortcode( 'wc_categoria_breadcrumbs', 'wc_categoria_breadcrumbs_shortcode' );