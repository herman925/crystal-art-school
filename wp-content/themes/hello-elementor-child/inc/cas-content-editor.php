<?php
/**
 * Loader for the cas_content role modules (issue #150, Route A).
 *
 * Each module is optional: a file that does not exist yet is skipped, so later pieces plug
 * in here without a change to functions.php. Load order is the list order.
 */

function cas_content_editor_load() {
	$modules = array(
		'cas-content-role',
		'cas-customizer-allowlist',
		'cas-content-about',
		'cas-content-blog',
		'cas-content-free-videos',
		'cas-content-prose',
		'cas-price-gate',
		'cas-content-builder',
		'cas-sales',
		'cas-coupons',
		'cas-enrol',
	);
	foreach ( $modules as $module ) {
		$path = get_stylesheet_directory() . '/inc/' . $module . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
cas_content_editor_load();
