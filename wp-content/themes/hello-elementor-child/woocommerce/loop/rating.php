<?php
/**
 * Loop Rating
 *
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$rating = $product->get_average_rating();
$review_count = $product->get_review_count() ?: 0;
$review_link = esc_url( $product->get_permalink() ) . '#reviews';

// Altura fija según diseño, por ejemplo 24px
$rating_container_style = 'font-size: 12px; display: flex; justify-content: center;';

echo '<div class="rating-container-catalog" style="' . $rating_container_style . '">';
if ( $rating > 0 ) {
    echo '<a href="' . $review_link . '" class="rating-link-catalog" style="display: flex; align-items: center; gap: 4px; text-decoration: none;">';
    echo '<span class="rating-value-catalog" style="color: #000; font-weight: 400; font-size: 12px;">' . esc_html( number_format( $rating, 1 ) ) . '</span>';
    
    // 5 estrellas
    echo '<span class="stars-container-catalog" style="color: #fe6100; display: inline-flex; gap: 1px; font-size: 12px;">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            echo '★';
        } elseif ($i - $rating < 1 && $i - $rating > 0) {
            echo '★';
        } else {
            echo '☆';
        }
    }
    echo '</span>';
    
    // FLECHA SVG
    echo '<span class="reviews-arrow-catalog" style="width: 10px; height: 10px; color: black; margin-top: -3px;">';
    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</span>';
    
    echo '<span class="review-count-catalog" style="color: #0071e3; font-size: 12px;">(' . esc_html( $review_count ) . ')</span>';
    echo '</a>';
} else {
    // Mantener espacio aunque no haya rating
    echo '<span style="visibility: hidden;">★★★★★ (0)</span>';
}
echo '</div>';
?>
