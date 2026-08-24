<?php
/**
 * Courses archive sections.
 */

if ( ! function_exists( 'cas_courses_archive_hero' ) ) {
	/**
	 * Render the Courses archive hero.
	 */
	function cas_courses_archive_hero() {
		$logo_url = get_theme_file_uri( 'img/logo2.png' );
		?>
		<header class="page-hero" aria-labelledby="cas-courses-hero-title">
			<div class="wash" aria-hidden="true"></div>
			<div class="container">
				<div class="eyebrow reveal-one">
					<span class="star" aria-hidden="true">&#10038;</span>
					<?php esc_html_e( 'Course catalog', 'crystal-art-school' ); ?>
				</div>
				<h1 class="marker-title font-a reveal-one" id="cas-courses-hero-title" style="font-size:clamp(44px,6vw,80px);">
					<?php esc_html_e( 'Pick up a brush.', 'crystal-art-school' ); ?>
				</h1>
				<div class="logo2">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Crystal Lau Art', 'crystal-art-school' ); ?>" decoding="async">
				</div>
			</div>
		</header>
		<?php
	}
}

if ( ! function_exists( 'cas_courses_free_trial_band' ) ) {
	/**
	 * Render the FREE TRIAL band at the end of the Courses archive.
	 */
	function cas_courses_free_trial_band() {
		$image_url = get_theme_file_uri( 'img/free-trial.jpg' );
		$lesson_url = home_url( '/lesson.dc.html' );
		?>
		<section class="section" aria-labelledby="cas-courses-trial-title">
			<div class="band-forest is-trial reveal-one">
				<div style="flex:0 0 180px;border-radius:var(--radius-card);overflow:hidden;aspect-ratio:1/1;position:relative;z-index:1;">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'Free lesson preview', 'crystal-art-school' ); ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy" decoding="async">
				</div>
				<div style="flex:1;">
					<div class="eyebrow">
						<span class="star" aria-hidden="true">&#10038;</span>
						<?php esc_html_e( 'Free trial', 'crystal-art-school' ); ?>
					</div>
					<h2 class="marker-title font-a" id="cas-courses-trial-title" style="font-size:clamp(31px,3.1vw,44px);">
						<?php esc_html_e( 'Not sure where to start?', 'crystal-art-school' ); ?>
					</h2>
					<p style="margin:0;max-width:54ch;">
						<?php esc_html_e( 'Watch', 'crystal-art-school' ); ?>
						<em><?php esc_html_e( 'Materials & setting up', 'crystal-art-school' ); ?></em>
						<?php esc_html_e( 'free — all 6 units, notes and downloads included — then pick the course that matches where you are.', 'crystal-art-school' ); ?>
					</p>
				</div>
				<a class="btn-big btn-marine" href="<?php echo esc_url( $lesson_url ); ?>" style="flex:none;">
					<?php esc_html_e( 'Watch free lesson', 'crystal-art-school' ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</div>
		</section>
		<?php
	}
}
