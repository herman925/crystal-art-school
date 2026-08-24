<?php
/**
 * The template for displaying 404 pages.
 */

get_header();
?>

<header class="page-hero">
	<div class="container">
		<div class="eyebrow">
			<span class="star" aria-hidden="true">&#10038;</span>
			<?php esc_html_e( '404 · Off the canvas', 'crystal-art-school' ); ?>
		</div>
		<h1 class="marker-title font-a" id="cas-404-title">
			<?php esc_html_e( 'This page has wandered off.', 'crystal-art-school' ); ?>
		</h1>
	</div>
</header>

<main id="content" class="container" tabindex="-1">
	<section class="section cas-404" aria-labelledby="cas-404-message">
		<div class="band-forest">
			<div>
				<h2 class="marker-title" id="cas-404-message">
					<?php esc_html_e( 'Let’s get you back to the studio.', 'crystal-art-school' ); ?>
				</h2>
				<p>
					<?php esc_html_e( 'We couldn’t find the page you were looking for.', 'crystal-art-school' ); ?>
				</p>
			</div>
			<a class="btn-big" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Back to the studio', 'crystal-art-school' ); ?>
				<span aria-hidden="true">→</span>
			</a>
		</div>
	</section>
</main>

<?php
get_footer();
