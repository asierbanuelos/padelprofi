<?php
/**
 * Template Name: 410 Gone
 * Template Post Type: page
 *
 * Vorlage für dauerhaft gelöschte Seiten.
 * Sendet HTTP 410 Gone statt des Standard-404.
 *
 * ANLEITUNG:
 * 1. Diese Datei in /wp-content/themes/DEIN-THEME/ hochladen
 * 2. Der Ultimate 410 Plugin verwendet sie automatisch
 */

// HTTP 410 Header senden
status_header( 410 );

// Optional: Bots daran hindern, diese Seite zu indexieren
header( 'X-Robots-Tag: noindex, nofollow' );

get_header();
?>

<style>
  .page-410-wrapper {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
  }

  .page-410-inner {
    max-width: 600px;
  }

  .page-410-code {
    font-size: 96px;
    font-weight: 700;
    line-height: 1;
    color: #c0392b;
    margin: 0 0 16px;
    letter-spacing: -4px;
  }

  .page-410-title {
    font-size: 28px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 16px;
  }

  .page-410-desc {
    font-size: 17px;
    color: #7f8c8d;
    line-height: 1.7;
    margin: 0 0 36px;
  }

  .page-410-btn {
    display: inline-block;
    padding: 14px 32px;
    background: #2c3e50;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-size: 15px;
    font-family: sans-serif;
    transition: background 0.2s;
  }

  .page-410-btn:hover {
    background: #1a252f;
    color: #fff;
  }
</style>

<div class="page-410-wrapper">
  <div class="page-410-inner">
    <div class="page-410-code">410</div>
    <h1 class="page-410-title">Diese Seite existiert nicht mehr</h1>
    <p class="page-410-desc">
      Der gesuchte Inhalt wurde dauerhaft entfernt
      und ist unter dieser Adresse nicht mehr verfügbar.
    </p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-410-btn">
      Zur Startseite
    </a>
  </div>
</div>

<?php get_footer(); ?>