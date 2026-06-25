<?php
/**
 * Plugin Name:       Babolat Schläger Empfehlung
 * Plugin URI:        https://padelprofideutschland.de
 * Description:       Interaktiver Babolat Padelschläger-Berater 2026. Shortcode: [babolat_recommender]
 * Version:           4.0.0
 * Author:            Padel Profi Deutschland
 * License:           GPL v2 or later
 * Text Domain:       babolat-recommender
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BABOLAT_REC_VERSION', '4.0.0' );
define( 'BABOLAT_REC_URL', plugin_dir_url( __FILE__ ) );

function babolat_rec_enqueue() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'babolat_recommender' ) ) {
        wp_enqueue_style(  'babolat-rec-css', BABOLAT_REC_URL . 'assets/recommender.css', [], BABOLAT_REC_VERSION );
        wp_enqueue_script( 'babolat-rec-js',  BABOLAT_REC_URL . 'assets/recommender.js',  [], BABOLAT_REC_VERSION, true );
        wp_localize_script( 'babolat-rec-js', 'BabolatRecConfig', [
            'shopUrl' => home_url(),
        ]);
    }
}
add_action( 'wp_enqueue_scripts', 'babolat_rec_enqueue' );

function babolat_rec_shortcode() {
    ob_start(); ?>
    <div id="babolat-recommender-root" aria-label="Babolat Schläger Empfehlung">
        <noscript><p>Bitte aktiviere JavaScript, um den Schläger-Berater zu nutzen.</p></noscript>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'babolat_recommender', 'babolat_rec_shortcode' );

add_action( 'admin_menu', function () {
    add_options_page( 'Babolat Empfehlung', 'Babolat Empfehlung', 'manage_options', 'babolat-recommender', function () {
        echo '<div class="wrap"><h1>🏓 Babolat Schläger Empfehlung v4</h1>';
        echo '<p>Shortcode für Seiten / Beiträge:</p>';
        echo '<code style="font-size:16px;padding:10px 16px;background:#f0f0f0;display:inline-block;border-radius:4px">[babolat_recommender]</code>';
        echo '</div>';
    });
});
