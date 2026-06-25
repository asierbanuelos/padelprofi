<?php
/**
 * Single Product Rating — diseño igual que loop/rating.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$rating       = $product->get_average_rating();
$review_count = $product->get_review_count();
$review_link  = esc_url( $product->get_permalink() ) . '#reviews';

echo '<div class="rating-container-catalog" style="font-size:18px; display:flex; justify-content:center;">';

if ( $rating > 0 ) {
	echo '<a href="' . $review_link . '" class="rating-link-catalog" style="display:flex; align-items:center; gap:4px; text-decoration:none;">';
	echo '<span class="rating-value-catalog" style="color:#000; font-weight:600; font-size:18px;">' . esc_html( number_format( $rating, 1, ',', '.' ) ) . '</span>';

	echo '<span class="stars-container-catalog" style="color:#fe6100; display:inline-flex; gap:1px; font-size:18px;">';
	for ( $i = 1; $i <= 5; $i++ ) {
		echo ( $i <= round( $rating ) ) ? '★' : '☆';
	}
	echo '</span>';

	echo '<span class="reviews-arrow-catalog" style="color:black; display:flex; align-items:center;">';
	echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
	echo '<path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
	echo '</svg>';
	echo '</span>';

	echo '<span class="review-count-catalog" style="color:#0071e3; font-size:18px;">(' . esc_html( $review_count ) . ')</span>';
	echo '</a>';
}

echo '</div>';
?>
