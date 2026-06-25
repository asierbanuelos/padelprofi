<?php
/**
 * The template for displaying the header
 *
 * This is the template that displays all of the <head> section, opens the <body> tag and adds the site's header.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$viewport_content = apply_filters( 'hello_elementor_viewport_content', 'width=device-width, initial-scale=1' );
$enable_skip_link = apply_filters( 'hello_elementor_enable_skip_link', true );
$skip_link_url = apply_filters( 'hello_elementor_skip_link_url', '#content' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="<?php echo esc_attr( $viewport_content ); ?>">
<?php if (is_search() || !empty($_GET['wpf_min_price']) || !empty($_GET['wpf_max_price']) || !empty($_GET['wpf_fbv']) || !empty($_GET['orderby'])) { ?>
	    <meta name="robots" content="noindex, follow">
	<?php } ?>
	<link rel="profile" href="https://gmpg.org/xfn/11">
<?php $favicon_version = date('YmdHis'); ?>
<link rel="icon" type="image/x-icon" href="<?php echo get_site_url(); ?>/favicon.ico?v=<?php echo $favicon_version; ?>">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo get_site_url(); ?>/favicon.ico?v=<?php echo $favicon_version; ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_site_url(); ?>/favicon.ico?v=<?php echo $favicon_version; ?>">
	<meta name="google-site-verification" content="0glyFy4PTnaswgfSl4_GcSZVsjJPilrqxDJft_PfnZM" />
	
	
	
	
	<?php wp_head(); ?>
	
	<!-- Hotjar Tracking Code for Padel Profi -->
	<!-- Cargado con defer para no bloquear renderizado -->
	<script defer>
	    (function(h,o,t,j,a,r){
	        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
	        h._hjSettings={hjid:5065347,hjsv:6};
	        a=o.getElementsByTagName('head')[0];
	        r=o.createElement('script');r.async=1;
	        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
	        a.appendChild(r);
	    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
	</script>
	
	<!-- Font Awesome cargado con carga diferida -->
	<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/plugins/font-awesome-6/css/all.min.css" media="print" onload="this.media='all'">
	<noscript><link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/plugins/font-awesome-6/css/all.min.css"></noscript>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-TJ3M299F');</script>
	<!-- End Google Tag Manager -->
</head>

<body <?php body_class(); ?>>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TJ3M299F"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<?php wp_body_open(); ?>

<?php if ( $enable_skip_link ) { ?>
<a class="skip-link screen-reader-text" href="<?php echo esc_url( $skip_link_url ); ?>"><?php echo esc_html__( 'Skip to content', 'hello-elementor' ); ?></a>
<?php } ?>

<div id="side-navbar__background"></div>
<div class="side-navbar" id="side-navbar">
	<div class="side-navbar__container">
		<span id="close-navbar"><i class="fa-solid fa-xmark"></i></span>
		<span id="back-navbar"><i class="fa-solid fa-chevron-left"></i></span>
 <span id="side-navbar__title" class="side-navbar__title"><!--<a>Neuheiten 2026</a><i class="fa-solid fa-chevron-right"></i>--></span> 
		<?php
			// Obtener todas las categorías de productos que sean "padre" y no estén dentro de ninguna otra
			// $top_level_product_categories = get_terms(array(
			// 	'taxonomy' => 'product_cat', // Taxonomía de categorías de productos de WooCommerce
			// 	'parent'   => 0, // Solo las categorías sin padres (es decir, las categorías "top-level")
			// 	'hide_empty' => false, // Mostrar incluso las que no tienen productos
			// ));

			// // Verificar si hay categorías
			// if ( !empty($top_level_product_categories) && !is_wp_error($top_level_product_categories) ) {
			// 	echo '<ul id="product-category-menu" class="product-category-menu">';
			// 	// Recorrer las categorías y mostrar cada una en una lista
			// 	foreach ( $top_level_product_categories as $category ) {
			// 		echo '<li><a href="' . get_term_link( $category->term_id, 'product_cat' ) . '">' . $category->name . '</a></li>';
			// 	}
			// 	echo '</ul>';
			// }
		?>
		<ul id="mainproducts-menu" class="mainproducts-menu">
			<li id="mainproducts-item-padelschlager" class="mainproducts-item"><a><i class="fa-solid fa-table-tennis-paddle-ball mainproducts-icon"></i>Padelschläger</a><i class="fa-solid fa-chevron-right"></i></li>
			<li id="mainproducts-item-padelballe" class="mainproducts-item"><a><i class="fa-solid fa-baseball mainproducts-icon"></i>Padelbälle</a><i class="fa-solid fa-chevron-right"></i></li>
			<li id="mainproducts-item-padeltaschen" class="mainproducts-item"><a><i class="fa-solid fa-suitcase mainproducts-icon"></i>Padeltaschen</a><i class="fa-solid fa-chevron-right"></i></li>
			<li id="mainproducts-item-padelzubehor" class="mainproducts-item"><a><i class="fa-solid fa-shirt mainproducts-icon"></i>Padelzubehör</a><i class="fa-solid fa-chevron-right"></i></li>
			<li id="mainproducts-item-padelschuhe" class="mainproducts-item"><a><i class="fa-solid fa-shoe-prints mainproducts-icon"></i>Padelschuhe</a><i class="fa-solid fa-chevron-right"></i></li>
			<li id="mainproducts-item-padel-set" class="mainproducts-item mainproducts-item--direct"><a href="https://padelprofideutschland.de/padel-set/"><i class="fa-solid fa-circle-dot mainproducts-icon"></i>Padel-Set</a></li>
<li id="mainproducts-item-pickleball" class="mainproducts-item mainproducts-item--direct"><a href="https://padelprofideutschland.de/pickleball/"><i class="fa-solid fa-circle-dot mainproducts-icon"></i>Pickleballschläger</a></li>		</ul>
		<ul id="secproducts-menu" class="secproducts-menu">
			<li class="secproducts-item"><a href="https://padelprofideutschland.de/beste-padel-angebote/">TOP-Angebot<span class="secproducts-menu__offer">Aktion</span></a></li>
		<!--	<li class="secproducts-item"><a href="/black-friday/">Black Friday<span class="secproducts-menu__offer">Angebote</span></a></li> -->
		</ul>
	</div>
</div>
<div class="side-navbar-extended" id="side-navbar-extended">
	<div class="mainproducts-extended-container">
		<!-- Categoria padelschlager -->
		<div id="mainproducts-extended-padelschlager" class="mainproducts-extended-content">
			<span class="mainproducts-main__title">Padelschläger</span>
<a href="/padelschlaeger" class="mainproducts-extended__link">Alle Padelschläger</a>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Marken<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/adidas/">Adidas</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/nox/">Nox</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/babolat/">Babolat</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/head/">Head</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/bullpadel/">Bullpadel</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/set/">Set</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/lok/">Lok</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/black-crown/">Black Crown</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/siux/">Siux</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/starvie/">Starvie</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/wilson/">Wilson</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Geschlecht<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/damen/">Damen</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/herren/">Herren</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/kinder/">Kinder</a></li>
			</ul>
			</div>

			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Spielniveau<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/anfaenger/">Anfänger</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/fortgeschrittene/">Fortgeschrittene</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/professionell/">Professionell</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Form<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/rund/">Rund</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/diamant/">Diamant</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschlaeger/tropfen/">Tropfen</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq">
				<a href="/padelschlaeger-test/" class="mainproducts-extended__title">
					Padelschläger Mieten
					<i class="fa-solid fa-chevron-right"></i>
				</a>
			</div>
		</div>
		
		<!-- Categoria padelschuhe -->
		<div id="mainproducts-extended-padelschuhe" class="mainproducts-extended-content">
			<span class="mainproducts-main__title">Padelschuhe</span>
<a href="/padelschuhe" class="mainproducts-extended__link">Alle Padelschuhe</a>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Marken<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschuhe-adidas/">Adidas</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-babolat/">Babolat</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-bullpadel/">Bullpadel</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-head/">Head</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-munich/">Munich</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-nox/">Nox</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-wilson/">Wilson</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Geschlecht<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelschuhe-damen/">Damen</a></li>
				<li class="mainproducts-extended-item"><a href="/padelschuhe-herren/">Herren</a></li>
			</ul>
			</div>
		</div>
		
		<!-- Categoria padelballe -->
		<div id="mainproducts-extended-padelballe" class="mainproducts-extended-content">
			<span class="mainproducts-main__title">Padelbälle</span>
<a href="/padelballe" class="mainproducts-extended__link">Alle Padelbälle</a>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Marken<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padelbaelle/adidas/">Adidas</a></li>
				<li class="mainproducts-extended-item"><a href="/padelballe/padelbaelle-nox/">NOX</a></li>
				<li class="mainproducts-extended-item"><a href="/padelballe/padelbaelle-padox/">Padox</a></li>
				<li class="mainproducts-extended-item"><a href="/padelbaelle/babolat/">Babolat</a></li>
				<li class="mainproducts-extended-item"><a href="/padelbaelle/bullpadel/">Bullpadel</a></li>
				<li class="mainproducts-extended-item"><a href="/padelbaelle/head/">HEAD</a></li>
				<li class="mainproducts-extended-item"><a href="/padelbaelle/black-crown/">Black Crown</a></li>
			</ul>
			</div>
		</div>
		
		<!-- Categoria padeltaschen -->
		<div id="mainproducts-extended-padeltaschen" class="mainproducts-extended-content">
			<span class="mainproducts-main__title">Padeltaschen</span>
<a href="/padeltaschen" class="mainproducts-extended__link">Alle Padeltaschen</a>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Marken<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padeltaschen/adidas/">Adidas</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/babolat/">Babolat</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/bullpadel/">Bullpadel</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/drop-shot/">Drop Shot</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/head/">HEAD</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/nox/">NOX</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/starvie/">Starvie</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/wilson/">Wilson</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Produkttyp<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padeltaschen/funda/">Funda</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/kleine-tasche/">Kleine Tasche</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/padeltasche/">Padeltasche</a></li>
				<li class="mainproducts-extended-item"><a href="/padeltaschen/rucksack/">Rucksack</a></li>
			</ul>
			</div>
		</div>
		
		<!-- Categoria padelzubehor -->
		<div id="mainproducts-extended-padelzubehor" class="mainproducts-extended-content">
			<span class="mainproducts-main__title">Padelzubehör</span>
<a href="/padel-zubehoer" class="mainproducts-extended__link">Alles Padelzubehör</a>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Marken<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-adidas/">Adidas</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Bullpadel/">Bullpadel</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-head/">HEAD</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-nox/">NOX</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Starvie/">Starvie</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Wilson/">Wilson</a></li>
			</ul>
			</div>
			<!-- Seccion -->
			<div class="mainproducts-extended__uniq"><span class="mainproducts-extended__title">Produktart<i class="fa-solid fa-chevron-right"></i></span>
			<ul class="mainproducts-extended-menu">
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-armbaender/">Armbänder</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Ballkorb/">Ballkorb</a></li>
				<li class="mainproducts-extended-item"><a href="/padelzubehoer-Druckbehalter/">Druckbehalter</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Grip/">Grip</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Handschuhe/">Handschuhe</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Kappe/">Kappe</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Kulturbeutel/">Kulturbeutel</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Socken/">Socken</a></li>
				<li class="mainproducts-extended-item"><a href="/padel-zubehoer-Wasserflasche/">Wasserflasche</a></li>
			</ul>
			</div>
		</div>
		
	</div>
</div>

<?php
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) {
	if ( hello_elementor_display_header_footer() ) {
		if ( did_action( 'elementor/loaded' ) && hello_header_footer_experiment_active() ) {
			get_template_part( 'template-parts/dynamic-header' );
		} else {
			get_template_part( 'template-parts/header' );
		}
	}
	
	
	
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var sideNavbar = document.getElementById('side-navbar');
	var sideNavbarExtended = document.getElementById('side-navbar-extended');
	var background = document.getElementById('side-navbar__background');
	var closeBtn = document.getElementById('close-navbar');
	var backBtn = document.getElementById('back-navbar');
	var titleEl = document.getElementById('side-navbar__title');
	var mainMenu = document.getElementById('mainproducts-menu');
	var secMenu = document.getElementById('secproducts-menu');

	if (!sideNavbar || !sideNavbarExtended || !closeBtn || !backBtn) return;

	var menuItems = document.querySelectorAll('.mainproducts-item:not(.mainproducts-item--direct)');
	var extendedPanels = document.querySelectorAll('.mainproducts-extended-content');
	// Accordion: toggle secciones dentro del menú extendido (Marken, Geschlecht, etc.)
	function initAccordion() {
		var titles = sideNavbarExtended.querySelectorAll('.mainproducts-extended__title');
		for (var i = 0; i < titles.length; i++) {
			(function(title) {
				// Saltar enlaces directos (como Padelschläger Mieten)
				if (title.tagName === 'A') return;
				// Usar onclick para sobreescribir cualquier handler previo
				title.onclick = function(e) {
					e.preventDefault();
					e.stopPropagation();
					this.classList.toggle('active');
					var menu = this.parentElement.querySelector('.mainproducts-extended-menu');
					if (menu) menu.classList.toggle('active');
					return false;
				};
			})(titles[i]);
		}
	}
	// Ejecutar inmediatamente y también con delay por si otros scripts cargan después
	initAccordion();
	setTimeout(initAccordion, 1000);

	function closeExtendedMenu() {
		// Quitar extended del sidebar principal
		sideNavbar.classList.remove('extended');
		// Ocultar panel extendido
		sideNavbarExtended.classList.remove('active');
		// Resetear todos los paneles de contenido
		extendedPanels.forEach(function(panel) {
			panel.classList.remove('active');
		});
		// Resetear accordions
		sideNavbarExtended.querySelectorAll('.mainproducts-extended__title.active').forEach(function(title) {
			title.classList.remove('active');
		});
		sideNavbarExtended.querySelectorAll('.mainproducts-extended-menu.active').forEach(function(menu) {
			menu.classList.remove('active');
		});
	}

	// Click en item del menú principal -> abrir menú extendido
	menuItems.forEach(function(item) {
		item.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var categoryId = item.id.replace('mainproducts-item-', '');
			var targetPanel = document.getElementById('mainproducts-extended-' + categoryId);

			if (!targetPanel) return;

			// Ocultar todos los paneles extendidos primero
			extendedPanels.forEach(function(panel) {
				panel.classList.remove('active');
			});

			// Mostrar el panel correspondiente
			targetPanel.classList.add('active');

			// Activar el panel extendido (CSS: left:350px en desktop, left:90px en mobile)
			sideNavbarExtended.classList.add('active');

			// Añadir .extended al sidebar (CSS: quita shadow en desktop, mueve a left:-260px en mobile)
			sideNavbar.classList.add('extended');
		});
	});

	// Botón volver (solo visible en mobile gracias al CSS existente)
	backBtn.addEventListener('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		closeExtendedMenu();
	});

	// Botón cerrar: cierra todo
	closeBtn.addEventListener('click', function(e) {
		e.preventDefault();
		closeExtendedMenu();
		sideNavbar.classList.remove('active');
		background.classList.remove('active');
	});

	// Click en fondo oscuro: cierra todo
	background.addEventListener('click', function() {
		closeExtendedMenu();
		sideNavbar.classList.remove('active');
		background.classList.remove('active');
	});
});
</script>

