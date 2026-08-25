<?php
/**
 * Course catalogue.
 *
 * Tutor registers its course post type with the slug `courses`, so /courses/ is a post
 * type archive and a WordPress page of the same slug is unreachable behind it. Rather
 * than move the catalogue to a different URL — the nav, the footer and the direction
 * document all say /courses/ — the archive itself is templated here.
 *
 * Tutor's own single-course template is untouched; this replaces the archive only.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main cas-archive">
	<?php echo do_shortcode( '[cas_courses]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</main>
<?php
get_footer();
