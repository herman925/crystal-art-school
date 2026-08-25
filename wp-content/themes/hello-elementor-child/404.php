<?php
/**
 * The themed 404 — the house pattern from cas-archives.php:86-90: a template file
 * whose whole body is header, the shortcode, footer. The archives module declines
 * `is_404()` deliberately, so an empty date archive keeps its true 404 status;
 * this template is what actually answers a nonexistent URL.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main cas-404-page">
	<?php echo do_shortcode( '[cas_404]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();
