<?php
/**
 * Add Custom HTML Field to Product Categories
 */

/**
 * Add Custom HTML Field to Add New Product Category Form
 *
 * @param string $taxonomy The taxonomy slug.
 */
function custom_html_add_category_fields( $taxonomy ) {
    ?>
    <div class="form-field term-group">
        <label for="custom_html_content"><?php esc_html_e( 'Custom HTML Content', 'wc-product-category' ); ?></label>
        <?php
            $html_content   = '';
            $html_editor_id = 'custom_html_content';
            $html_settings  = array(
                'textarea_name' => 'custom_html_content',
                'textarea_rows' => 10,
                'media_buttons' => true,
                'tinymce'       => array(
                    'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,code',
                    'toolbar2' => '',
                ),
            );
            wp_editor( $html_content, $html_editor_id, $html_settings );
        ?>
        <p class="description"><?php esc_html_e( 'Add custom HTML content for this category.', 'wc-product-category' ); ?>.<br/>Using shortcode [custom_html_content]</p>
    </div>
    <?php
}
add_action( 'product_cat_add_form_fields', 'custom_html_add_category_fields', 10, 1 );

/**
 * Add Custom HTML Field to Edit Product Category Form
 *
 * @param WP_Term $term     The term object.
 * @param string  $taxonomy The taxonomy slug.
 */
function custom_html_edit_category_fields( $term, $taxonomy ) {
    // Retrieve the existing value for the HTML content.
    $html_content = get_term_meta( $term->term_id, 'custom_html_content', true );
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="custom_html_content"><?php esc_html_e( 'Custom HTML Content', 'wc-product-category' ); ?></label></th>
        <td>
            <?php
                $html_editor_id = 'custom_html_content';
                $html_settings  = array(
                    'textarea_name' => 'custom_html_content',
                    'textarea_rows' => 10,
                    'media_buttons' => true,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,code',
                        'toolbar2' => '',
                    ),
                );
                wp_editor( wp_kses_post( $html_content ), $html_editor_id, $html_settings );
            ?>
            <p class="description"><?php esc_html_e( 'Add custom HTML content for this category.', 'wc-product-category' ); ?><br/>Using shortcode [custom_html_content]</p>
        </td>
    </tr>
    <?php
}
add_action( 'product_cat_edit_form_fields', 'custom_html_edit_category_fields', 10, 2 );

/**
 * Save Custom HTML Content for Product Category
 *
 * @param int $term_id The term ID.
 * @param int $tt_id   The taxonomy term ID.
 */
function custom_html_save_category_meta( $term_id, $tt_id ) {
    // Save HTML content.
    if ( isset( $_POST['custom_html_content'] ) ) {
        $sanitized_html = wp_kses_post( wp_unslash( $_POST['custom_html_content'] ) );
        update_term_meta( $term_id, 'custom_html_content', $sanitized_html );
    }
}
add_action( 'created_product_cat', 'custom_html_save_category_meta', 10, 2 );
add_action( 'edited_product_cat', 'custom_html_save_category_meta', 10, 2 );

/**
 * Shortcode to Display Custom HTML Content for Product Categories
 *
 * @return string The HTML content of the custom field.
 */
function custom_html_content_shortcode() {
    // Check if we're on a product category page.
    if ( is_product_category() ) {
        // Get the current queried term.
        $term = get_queried_object();

        // Retrieve the HTML content from term meta.
        $html_content = get_term_meta( $term->term_id, 'custom_html_content', true );

        if ( ! empty( $html_content ) ) {
            ob_start();
            ?>
            <div class="custom-html-content">
                <?php echo wp_kses_post( $html_content ); ?>
            </div>
            <?php
            return ob_get_clean();
        }
    }

    return '';
}
add_shortcode( 'custom_html_content', 'custom_html_content_shortcode' );
