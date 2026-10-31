<?php
/** Standalone unit checks: legacy preservation, native rename/clear, and comma parsing. */
define( 'ABSPATH', __DIR__ );
function add_action( ...$args ) { $GLOBALS['actions'][ $args[0] ] = $args[1]; }
function wp_get_object_terms( ...$args ) { return $GLOBALS['terms']; }
function metadata_exists( ...$args ) { return $GLOBALS['migrated']; }
function get_post_meta( ...$args ) { return 'Legacy medium'; }
function is_wp_error( $value ) { return false; }
require __DIR__ . '/../wp-content/themes/hello-elementor-child/inc/cas-content-management.php';
function check( $actual, $expected ) {
	if ( $actual !== $expected ) { throw new RuntimeException( json_encode( array( $actual, $expected ) ) ); }
}
$GLOBALS['terms'] = array(); $GLOBALS['migrated'] = false;
check( cas_content_term_text( 1, 'medium', 'legacy' ), 'Legacy medium' );
$GLOBALS['terms'] = array( 'Renamed medium', 'Ink' );
check( cas_content_term_text( 1, 'medium', 'legacy' ), 'Renamed medium, Ink' );
$GLOBALS['terms'] = array(); $GLOBALS['migrated'] = true;
check( cas_content_term_text( 1, 'medium', 'legacy' ), '' );
check( cas_content_term_parts( ' Watercolour, Ink, Watercolour, ,0 ' ), array( 'Watercolour', 'Ink', '0' ) );
check( cas_content_term_parts( '' ), array() );
echo "Content term unit checks passed.\n";
