<?php
/**
 * 404 Not Found — PadelProfi child theme
 * Overrides parent so get_header/get_footer always fire (menu + chatbot).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<style>
.pp-404-wrapper {
	min-height: 60vh;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 60px 20px;
	text-align: center;
}
.pp-404-inner { max-width: 600px; }
.pp-404-code {
	font-size: 96px;
	font-weight: 700;
	line-height: 1;
	color: #FE6100;
	margin: 0 0 16px;
	letter-spacing: -4px;
}
.pp-404-title {
	font-size: 28px;
	font-weight: 600;
	color: #2c3e50;
	margin: 0 0 16px;
}
.pp-404-desc {
	font-size: 17px;
	color: #7f8c8d;
	line-height: 1.7;
	margin: 0 0 36px;
}
.pp-404-btn {
	display: inline-block;
	padding: 14px 32px;
	background: #FE6100;
	color: #fff;
	text-decoration: none;
	border-radius: 4px;
	font-size: 15px;
	transition: background .2s;
}
.pp-404-btn:hover { background: #d95200; color: #fff; }
</style>

<div class="pp-404-wrapper">
	<div class="pp-404-inner">
		<div class="pp-404-code">404</div>
		<h1 class="pp-404-title">Seite nicht gefunden</h1>
		<p class="pp-404-desc">
			Die gesuchte Seite existiert nicht oder wurde verschoben.<br>
			Geh zurück zur Startseite und entdecke unser Angebot.
		</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="pp-404-btn">
			Zur Startseite
		</a>
	</div>
</div>

<?php get_footer(); ?>
