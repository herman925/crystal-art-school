<?php
/**
 * Single course. Tutor's own template is replaced because its layout is not the agreed
 * design; the swap happens in inc/cas-course-single.php via template_include.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main cas-single-course" tabindex="-1">
	<?php
	while ( have_posts() ) {
		the_post();
		echo do_shortcode( '[cas_course]' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>
</main>
<?php
get_footer();
