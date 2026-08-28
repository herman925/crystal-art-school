<?php
/**
 * Loads the prototype's design system on top of Hello Elementor.
 *
 * industrial.css is a three-line aggregator that @imports the other three files.
 * Serial @import blocks rendering, so the three are enqueued directly instead and
 * the aggregator is left unused — same cascade, one round trip per file.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'hello-elementor-parent', get_template_directory_uri() . '/style.css', [], null );

		wp_enqueue_style(
			'cas-fonts',
			'https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,500;6..96,600;6..96,700&family=Oswald:wght@300;400;500;600&family=Archivo:wght@400;500;600;700&family=Saira+Stencil+One&family=Arizonia&display=swap',
			[],
			null
		);

		$base = get_stylesheet_directory_uri() . '/css/';
		$dir  = get_stylesheet_directory() . '/css/';

		foreach ( [ 'industrial-1', 'industrial-2', 'industrial-3' ] as $i => $handle ) {
			wp_enqueue_style(
				'cas-' . $handle,
				$base . $handle . '.css',
				0 === $i ? [ 'cas-fonts' ] : [ 'cas-industrial-' . $i ],
				filemtime( $dir . $handle . '.css' )
			);
		}
	},
	20
);

/**
 * The prototype scopes its entire design system to `.cas-root[data-theme="A"|"D"]`
 * on a wrapper div, not to <html>. Around forty selectors depend on it, so the
 * palette silently fails without it. Rather than rewrite those selectors, the same
 * hooks are put on <body>.
 *
 * The class goes on server-side. The attribute cannot — WordPress offers no filter
 * for body attributes — so it is set by an inline script on wp_body_open, which runs
 * before any markup is painted. Default is D (dark), matching the prototype, and the
 * stored preference key is the prototype's own `cas-theme`.
 */

add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'cas-root';

		return $classes;
	}
);

add_action(
	'wp_body_open',
	function () {
		?>
		<script>
			try {
				document.body.setAttribute( 'data-theme', localStorage.getItem( 'cas-theme' ) || 'D' );
			} catch ( e ) {
				document.body.setAttribute( 'data-theme', 'D' );
			}
		</script>
		<?php
	},
	1
);

/**
 * Elementor's kit defaults name Roboto and Roboto Slab, so it enqueues both from
 * Google — 36 weights the design never uses, on every page. The
 * `elementor_google_fonts` option did not stop it, so the handles are dropped
 * directly. The five real faces come from cas-fonts above.
 */

add_action(
	'wp_print_styles',
	function () {
		wp_dequeue_style( 'elementor-gf-roboto' );
		wp_dequeue_style( 'elementor-gf-robotoslab' );
	},
	0
);

/**
 * The prototype's nav is a hardcoded LINKS array in js/site-nav.js. In WordPress the
 * same eight items become a real menu so their labels and order are manageable from
 * Appearance > Menus without touching code.
 */

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			[
				'cas-primary' => 'Primary navigation',
			]
		);
	}
);

require_once get_stylesheet_directory() . '/inc/cas-header.php';

/**
 * Site-wide behaviour ported from the prototype's js/site-extras.js: the one polite
 * live region behind window.CasAnnounce, and the scroll driver that adds `.in-view`.
 *
 * Unconditional and site-wide on purpose. Three shipped modules already call
 * window.CasAnnounce behind an `if ( window.CasAnnounce )` guard and it was defined
 * nowhere, so every screen-reader announcement on the site was silently dropped.
 * Priority 20 registers the handle before every module enqueue at 21 and later, so a
 * module can declare it as a dependency; the announcer is only ever called from event
 * handlers, so load order is a convenience rather than a requirement.
 *
 * The live region is created by the script and appended to <body>, and it carries its
 * own visually-hidden rule, so there is no markup for a template to print.
 */

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_script( 'cas-site-extras', $uri . '/js/cas-site-extras.js', [], filemtime( $dir . '/js/cas-site-extras.js' ), true );
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_style( 'cas-nav', $uri . '/css/cas-nav.css', [ 'cas-industrial-3' ], filemtime( $dir . '/css/cas-nav.css' ) );
		wp_enqueue_script( 'cas-header', $uri . '/js/cas-header.js', [], filemtime( $dir . '/js/cas-header.js' ), true );
	},
	21
);

require_once get_stylesheet_directory() . '/inc/cas-footer.php';

/**
 * Hello Elementor ships its own layout rules for `.site-header` and `.site-footer` —
 * the same class names this design uses. Its `.site-footer { max-width: 1140px }` was
 * boxing the footer into the content column. The header and footer here are supplied
 * in full by the prototype's stylesheet, so the parent's versions are dropped rather
 * than out-specified.
 */

add_action(
	'wp_print_styles',
	function () {
		wp_dequeue_style( 'hello-elementor-header-footer' );
	},
	0
);

require_once get_stylesheet_directory() . '/inc/cas-brand.php';

require_once get_stylesheet_directory() . '/inc/cas-hero.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_script( 'cas-hero', $uri . '/js/cas-hero.js', [], filemtime( $dir . '/js/cas-hero.js' ), true );
	},
	22
);

/**
 * GSAP is vendored rather than pulled from a CDN: one fewer third party in the
 * critical path of a site that takes payments, and no external request to explain to
 * a UK visitor. The shared motion pass is the prototype's existing GSAP choreography;
 * it is loaded site-wide so the same reveals and pointer-only tilt apply to every
 * card-bearing page. The homepage's hero still has its own dedicated pass, while the
 * shared file detects that hero and leaves its remaining choreography alone.
 */

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_script( 'gsap', $uri . '/js/vendor/gsap.min.js', [], '3.12.5', true );
		wp_enqueue_script( 'cas-scrolltrigger', $uri . '/js/vendor/ScrollTrigger.min.js', [ 'gsap' ], '3.12.5', true );

		if ( is_front_page() ) {
			wp_enqueue_script( 'cas-hero-motion', $uri . '/js/cas-hero-motion.js', [ 'gsap', 'cas-hero' ], filemtime( $dir . '/js/cas-hero-motion.js' ), true );
		}

		wp_enqueue_script( 'cas-motion', $uri . '/js/cas-motion.js', [ 'gsap', 'cas-scrolltrigger', 'cas-site-extras' ], filemtime( $dir . '/js/cas-motion.js' ), true );
	},
	23
);

require_once get_stylesheet_directory() . '/inc/cas-courses.php';

require_once get_stylesheet_directory() . '/inc/cas-course-single.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
			return;
		}

		$dir = get_stylesheet_directory();

		wp_enqueue_style( 'cas-home', get_stylesheet_directory_uri() . '/css/cas-home.css', [ 'cas-nav' ], filemtime( $dir . '/css/cas-home.css' ) );
	},
	26
);

require_once get_stylesheet_directory() . '/inc/cas-home.php';

require_once get_stylesheet_directory() . '/inc/cas-pages.php';

require_once get_stylesheet_directory() . '/inc/cas-editable.php';

require_once get_stylesheet_directory() . '/inc/cas-about.php';

require_once get_stylesheet_directory() . '/inc/cas-gallery.php';
require_once get_stylesheet_directory() . '/inc/cas-blog.php';

/*
 * The last three page modules. Each registers its own post type, Customizer section and
 * assets from inside its own file, so the require is all that belongs here. They load
 * after cas-pages.php and cas-brand.php because all three call cas_page_hero() and
 * cas_brand_image() when they render.
 */

require_once get_stylesheet_directory() . '/inc/cas-blog-templates.php';
require_once get_stylesheet_directory() . '/inc/cas-showcase.php';
require_once get_stylesheet_directory() . '/inc/cas-free-videos.php';

/*
 * The homepage sections below the hero, the account and archive shells. Same rule as
 * the group above: each file registers its own shortcode, Customizer section, post type
 * and assets, so the require is all that belongs here.
 *
 * cas-home-blog.php must load after cas-blog-templates.php — it reuses that file's
 * cas_blog_posts(), cas_blog_topic() and cas_blog_image(). cas-archives.php reuses its
 * cas_blog_styles(). cas-home-release.php is the row wrapper the Free videos card and
 * the About Crystal column share, and calls both by shortcode at render time.
 */

require_once get_stylesheet_directory() . '/inc/cas-home-free-videos.php';
require_once get_stylesheet_directory() . '/inc/cas-home-about.php';
require_once get_stylesheet_directory() . '/inc/cas-home-release.php';
require_once get_stylesheet_directory() . '/inc/cas-home-releases.php';
require_once get_stylesheet_directory() . '/inc/cas-home-courses.php';
require_once get_stylesheet_directory() . '/inc/cas-home-course-overview.php';
require_once get_stylesheet_directory() . '/inc/cas-home-gallery.php';
require_once get_stylesheet_directory() . '/inc/cas-home-blog.php';
require_once get_stylesheet_directory() . '/inc/cas-account.php';
require_once get_stylesheet_directory() . '/inc/cas-archives.php';
require_once get_stylesheet_directory() . '/inc/cas-404.php';

/*
 * §3.6's Art Gallery card is the second column of §3.5's `.shop-layout` grid, not a
 * section of its own, so it is hooked into that row rather than placed on the page.
 * cas-home-gallery.php returns a bare <a class="gallery-tile">; dropping it into the
 * page's own content would leave a naked anchor in the page flow at full width.
 */

add_filter(
	'cas_home_courses_aside',
	function ( $html ) {
		return $html . do_shortcode( '[cas_home_gallery]' );
	}
);

require_once get_stylesheet_directory() . '/inc/cas-customizer-map.php';
