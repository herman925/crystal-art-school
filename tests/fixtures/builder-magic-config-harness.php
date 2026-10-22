<?php
/** Pure path-boundary fixture only: no WP/Tutor bootstrap, filesystem probe, DB or runtime acceptance. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'CAS_CONTENT_CAP', 'manage_cas_content' );
define( 'TUTOR_VERSION', '4.1.3' );
class MagicPathConfig {
	public $reads = 0;
	public function __construct( private $value ) {}
	public function __get( $key ) {
		if ( 'path' === $key ) { ++$this->reads; return $this->value; }
		return null;
	}
	// Deliberately NO __isset(): matches the certified TUTOR\Config failure.
}
$values = array( 'valid' => '/unit/tutor/', 'valid_no_slash' => '/unit/tutor', 'empty' => '', 'null' => null,
	'integer' => 7, 'boolean' => false, 'array' => array( '/unit/tutor/' ), 'object' => (object) array( 'path' => '/unit/tutor/' ) );
$mode = $argv[2];
$config = new MagicPathConfig( $values[ $mode ] );
$old_isset = isset( $config->path );
$old_empty = empty( $config->path );
$GLOBALS['header_calls'] = 0;
$GLOBALS['header_file'] = null;
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function tutor() { return $GLOBALS['config']; }
function tutor_utils() { return null; }
function is_admin() { return true; }
function trailingslashit( $path ) { return rtrim( $path, '/' ) . '/'; }
function get_file_data( $file, $headers ) {
	++$GLOBALS['header_calls'];
	$GLOBALS['header_file'] = $file;
	// Stop at the UNCHANGED next guard: this is not a fake full-compatibility pass.
	return array( 'Version' => 'deliberately-unsupported-unit-header' );
}
$_SERVER['REQUEST_METHOD'] = 'POST';
require $argv[1];
$result = cas_builder_sort_compatible( 2, true );
echo json_encode( array( 'isset' => $old_isset, 'empty' => $old_empty, 'has_get' => method_exists( $config, '__get' ),
	'has_isset' => method_exists( $config, '__isset' ), 'reads' => $config->reads, 'header_calls' => $GLOBALS['header_calls'],
	'header_file' => $GLOBALS['header_file'], 'result' => $result ) );
