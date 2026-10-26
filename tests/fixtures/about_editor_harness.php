<?php
// In-memory only: no native WordPress/KSES, database, persistent cache, HTTP or editor proof.
if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) {
	throw new RuntimeException( 'Standalone synthetic CLI fixture only; never load inside WordPress.' );
} else {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'ARRAY_A', 'ARRAY_A' );
	define( 'CAS_CONTENT_CAP', 'manage_cas_content' );
	define( 'CAS_CONTENT_HUB_SLUG', 'cas-content-hub' );

	class WP_Error {
		public function __construct( public $code, public $message ) {}
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
	}
	function is_wp_error( $value ) { return $value instanceof WP_Error; }
	function add_action( ...$args ) {}
	function add_filter( ...$args ) {}
	function do_action( ...$args ) {}
	function current_user_can( $cap ) { return $GLOBALS['about_cap'] && 'manage_cas_content' === $cap; }
	function get_current_user_id() { return $GLOBALS['about_actor']; }
	function wp_verify_nonce( $nonce, $action ) { return $GLOBALS['about_nonce'] && 'fixture-only' === $nonce && $GLOBALS['about_action'] === $action; }
	function wp_unslash( $value ) { return stripslashes( $value ); }
	function wp_kses_post( $value ) { return $value; }
	function wp_hash( $value ) { return hash_hmac( 'sha256', $value, 'synthetic-fixture-only' ); }
	function get_option( $name ) {
		if ( 'stylesheet' === $name ) { return 'hello-elementor-child'; }
		return $GLOBALS['about_cached'] ?? unserialize( $GLOBALS['wpdb']->row['option_value'], array( 'allowed_classes' => false ) );
	}
	function wp_cache_delete( $key, $group ) { $GLOBALS['about_evictions'][] = array( $key, $group ); return true; }
	function cas_blog_intro_default() { return 'Blog shipped wording'; }
	function cas_fv_intro_default( $key ) { return 'Free Videos shipped ' . $key; }
	function cas_about_fields() {
		$fields = array();
		foreach ( array( 'cas_about_intro', 'cas_about_quote', 'cas_about_journey_body', 'cas_about_community_body', 'cas_about_instructor_body' ) as $key ) {
			$fields[ $key ] = array( 'type' => in_array( $key, array( 'cas_about_intro', 'cas_about_quote' ), true ) ? 'inline' : 'block', 'default' => 'Shipped wording' );
		}
		return $fields;
	}

	class AboutMemoryDB {
		public $options = 'synthetic_options';
		public $last_error = '';
		public $row;
		public $before_write = null;
		public $after_write = null;
		public $fail_write = false;
		public $writes = 0;
		public function prepare( $sql, ...$args ) { return array( $sql, $args ); }
		public function get_row( $query, $format ) { return $this->row; }
		public function query( $query ) {
			++$this->writes;
			list( $sql, $args ) = $query;
			about_expect( str_contains( $sql, 'BINARY option_value = BINARY %s' ) && str_contains( $sql, 'BINARY autoload = BINARY %s' ) && str_contains( $sql, 'option_id = %d' ), 'Missing SQL CAS predicates' );
			about_expect( ! str_contains( $sql, 'SET autoload' ), 'Autoload must not be assigned' );
			if ( $this->before_write ) { ( $this->before_write )( $this ); }
			if ( $this->fail_write ) { return false; }
			if ( ! $this->row || (int) $args[1] !== (int) $this->row['option_id'] || 'theme_mods_hello-elementor-child' !== $args[2] || $args[3] !== $this->row['option_value'] || $args[4] !== $this->row['autoload'] ) { return 0; }
			$this->row['option_value'] = $args[0];
			if ( $this->after_write ) { ( $this->after_write )( $this ); }
			return 1;
		}
	}
	class AboutWakeupProbe {
		public function __wakeup() { $GLOBALS['about_wakeup'] = true; }
	}
	function about_expect( $condition, $message ) {
		if ( ! $condition ) { throw new RuntimeException( $message ); }
	}
	function about_reset() {
		$GLOBALS['about_cap'] = true;
		$GLOBALS['about_actor'] = 12;
		$GLOBALS['about_nonce'] = true;
		$GLOBALS['about_action'] = 'cas_content_about_save';
		$GLOBALS['about_cached'] = null;
		$GLOBALS['about_evictions'] = array();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = array( '_wpnonce' => 'fixture-only' );
		$mods = array_fill_keys( array_keys( cas_about_fields() ), 'Original' );
		$mods['unrelated'] = array( 'nav_menu_locations' => array( 'primary' => 21 ), 'marker' => 'Preserve' );
		$GLOBALS['wpdb'] = new AboutMemoryDB();
		$GLOBALS['wpdb']->row = array( 'option_id' => '7', 'autoload' => 'auto-on', 'option_value' => serialize( $mods ) );
		return $mods;
	}
	function about_submit( $before, $proposed ) {
		$input = array_map( 'addslashes', array_intersect_key( $proposed, cas_about_fields() ) );
		return cas_content_prose_save( 'about', 'theme_mods_hello-elementor-child', cas_content_prose_snapshot( 'about', 'theme_mods_hello-elementor-child', $before ), $input );
	}
	function about_error( $result, $code ) {
		about_expect( is_wp_error( $result ) && $code === $result->get_error_code(), 'Expected ' . $code );
	}

	require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-content-about.php';
	require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-content-blog.php';
	require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-content-free-videos.php';
	require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-content-prose.php';
	$cases = 0;
	$before = about_reset();
	$proposed = $before;
	$proposed['cas_about_intro'] = 'Artist’s <em>own</em> words';
	$proposed['cas_about_journey_body'] = '<h3 class="bio-sub">Heading</h3><p>Painter’s <strong>story</strong>.</p>';
	about_expect( 'saved' === about_submit( $before, $proposed ), 'Valid save failed' );
	about_expect( unserialize( $wpdb->row['option_value'] ) === $proposed && 'auto-on' === $wpdb->row['autoload'], 'Unrelated values or autoload changed' );
	about_expect( $GLOBALS['about_evictions'] === array( array( 'theme_mods_hello-elementor-child', 'options' ), array( 'alloptions', 'options' ), array( 'notoptions', 'options' ) ), 'Wrong cache scope' );
	++$cases;

	$before = about_reset(); $proposed = $before; $proposed['cas_about_quote'] = '';
	$current = $before; $current['unrelated']['marker'] = 'New concurrent value';
	$wpdb->row['option_value'] = serialize( $current );
	about_expect( 'saved' === about_submit( $before, $proposed ), 'Unrelated pre-read change blocked' );
	$current['cas_about_quote'] = '';
	about_expect( unserialize( $wpdb->row['option_value'] ) === $current, 'Current unrelated value lost or empty value changed' );
	++$cases;

	$before = about_reset(); $current = $before; $current['cas_about_quote'] = 'New writer'; $wpdb->row['option_value'] = serialize( $current );
	about_error( about_submit( $before, $before ), 'about_stale' );
	about_expect( 0 === $wpdb->writes && unserialize( $wpdb->row['option_value'] ) === $current, 'Stale form wrote' ); ++$cases;

	foreach ( array( 'unrelated', 'autoload', 'identity', 'binary' ) as $race ) {
		$before = about_reset(); $proposed = $before; $proposed['cas_about_intro'] = 'New';
		$wpdb->before_write = static function ( $db ) use ( $race ) {
			if ( 'autoload' === $race ) { $db->row['autoload'] = 'off'; }
			elseif ( 'identity' === $race ) { $db->row['option_id'] = '8'; }
			else { $mods = unserialize( $db->row['option_value'] ); $mods['unrelated']['marker'] = 'binary' === $race ? 'preserve' : 'Concurrent'; $db->row['option_value'] = serialize( $mods ); }
		};
		about_error( about_submit( $before, $proposed ), 'about_stale' );
		about_expect( 1 === $wpdb->writes && 'Original' === unserialize( $wpdb->row['option_value'] )['cas_about_intro'], 'CAS race overwrote or retried' ); ++$cases;
	}

	$before = about_reset(); about_expect( 'unchanged' === about_submit( $before, $before ) && 0 === $wpdb->writes, 'No-op wrote' ); ++$cases;
	foreach ( array( 'get', 'cap', 'nonce', 'nonce-array', 'actor' ) as $denial ) {
		$before = about_reset(); $proposed = $before; $proposed['cas_about_intro'] = 'New';
		$snapshot = cas_content_prose_snapshot( 'about', 'theme_mods_hello-elementor-child', $before );
		if ( 'get' === $denial ) { $_SERVER['REQUEST_METHOD'] = 'GET'; }
		if ( 'cap' === $denial ) { $GLOBALS['about_cap'] = false; }
		if ( 'nonce' === $denial ) { $GLOBALS['about_nonce'] = false; }
		if ( 'nonce-array' === $denial ) { $_POST['_wpnonce'] = array(); }
		if ( 'actor' === $denial ) { $GLOBALS['about_actor'] = 13; }
		about_error( cas_content_prose_save( 'about', 'theme_mods_hello-elementor-child', $snapshot, array_intersect_key( $proposed, cas_about_fields() ) ), 'actor' === $denial ? 'about_stale' : 'about_denied' );
		about_expect( 0 === $wpdb->writes, 'Denied request wrote' ); ++$cases;
	}

	foreach ( array( 'extra', 'missing', 'array', 'inline-block' ) as $bad ) {
		$before = about_reset(); $input = array_intersect_key( $before, cas_about_fields() );
		if ( 'extra' === $bad ) { $input['cas_about_portrait'] = 'forged.jpg'; }
		if ( 'missing' === $bad ) { unset( $input['cas_about_quote'] ); }
		if ( 'array' === $bad ) { $input['cas_about_quote'] = array(); }
		if ( 'inline-block' === $bad ) { $input['cas_about_intro'] = '<p>Nested block</p>'; }
		about_error( cas_content_prose_save( 'about', 'theme_mods_hello-elementor-child', cas_content_prose_snapshot( 'about', 'theme_mods_hello-elementor-child', $before ), $input ), 'about_input' );
		about_expect( 0 === $wpdb->writes, 'Invalid input wrote' ); ++$cases;
	}

	$before = about_reset(); $proposed = $before; $proposed['cas_about_intro'] = 'New'; $wpdb->fail_write = true;
	about_error( about_submit( $before, $proposed ), 'about_storage' );
	about_expect( unserialize( $wpdb->row['option_value'] ) === $before && 1 === $wpdb->writes, 'Failed write changed data or retried' ); ++$cases;

	$before = about_reset(); $proposed = $before; $proposed['cas_about_intro'] = 'New';
	$wpdb->after_write = static function ( $db ) { $mods = unserialize( $db->row['option_value'] ); $mods['unrelated'] = 'Later writer'; $db->row['option_value'] = serialize( $mods ); };
	about_error( about_submit( $before, $proposed ), 'about_storage' );
	about_expect( 1 === $wpdb->writes && 'Later writer' === unserialize( $wpdb->row['option_value'] )['unrelated'], 'Readback uncertainty overwrote later writer' ); ++$cases;

	$before = about_reset(); $proposed = $before; $proposed['cas_about_intro'] = 'New'; $GLOBALS['about_cached'] = $before;
	about_error( about_submit( $before, $proposed ), 'about_storage' );
	about_expect( 1 === $wpdb->writes && 'New' === unserialize( $wpdb->row['option_value'] )['cas_about_intro'], 'Cache uncertainty rolled back or retried' ); ++$cases;

	foreach ( array( 'object', 'oversize', 'missing', 'wrong-type' ) as $bad_row ) {
		$before = about_reset();
		if ( 'object' === $bad_row ) { $GLOBALS['about_wakeup'] = false; $wpdb->row['option_value'] = serialize( array( 'probe' => new AboutWakeupProbe() ) ); }
		if ( 'oversize' === $bad_row ) { $wpdb->row['option_value'] = str_repeat( 'x', 1048577 ); }
		if ( 'missing' === $bad_row ) { $wpdb->row = null; }
		if ( 'wrong-type' === $bad_row ) { $before['cas_about_quote'] = 42; $wpdb->row['option_value'] = serialize( $before ); }
		about_error( about_submit( $before, $before ), 'about_storage' );
		about_expect( 0 === $wpdb->writes && empty( $GLOBALS['about_wakeup'] ), 'Malformed option wrote or instantiated object' ); ++$cases;
	}
	function prose_fixture( $group ) {
		$mods = about_reset();
		$config = cas_content_prose_config( $group );
		$fields = call_user_func( $config['fields'] );
		foreach ( $fields as $key => $field ) { $mods[ $key ] = 'Original'; }
		$GLOBALS['wpdb']->row['option_value'] = serialize( $mods );
		$GLOBALS['about_action'] = $config['nonce'];
		return array( $mods, $fields, $config );
	}
	function prose_submit( $group, $before, $proposed, $fields ) {
		return cas_content_prose_save( $group, 'theme_mods_hello-elementor-child', cas_content_prose_snapshot( $group, 'theme_mods_hello-elementor-child', $before ), array_map( 'addslashes', array_intersect_key( $proposed, $fields ) ) );
	}
	foreach ( array( 'blog', 'free-videos' ) as $group ) {
		list( $before, $fields, $config ) = prose_fixture( $group );
		$key = array_key_first( $fields ); $proposed = $before; $proposed[ $key ] = '<strong>New text</strong>';
		about_expect( 'saved' === prose_submit( $group, $before, $proposed, $fields ) && unserialize( $wpdb->row['option_value'] ) === $proposed, 'Selected group save changed other values' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		$current = $before; $current['cas_about_quote'] = 'Other page edit'; $wpdb->row['option_value'] = serialize( $current );
		$proposed = $before; $proposed[ $key ] = '';
		about_expect( 'saved' === prose_submit( $group, $before, $proposed, $fields ), 'Other page pre-read change blocked' );
		$current[ $key ] = ''; about_expect( unserialize( $wpdb->row['option_value'] ) === $current, 'Empty value restored default or lost other page edit' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		$current = $before; $current[ $key ] = 'Later editor'; $wpdb->row['option_value'] = serialize( $current );
		about_error( prose_submit( $group, $before, $before, $fields ), $config['error'] . '_stale' );
		about_expect( 0 === $wpdb->writes, 'Stale group wrote' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		$proposed = $before; $proposed[ $key ] = 'New';
		$wpdb->before_write = static function ( $db ) { $m = unserialize( $db->row['option_value'] ); $m['unrelated']['marker'] = 'Concurrent'; $db->row['option_value'] = serialize( $m ); };
		about_error( prose_submit( $group, $before, $proposed, $fields ), $config['error'] . '_stale' );
		about_expect( 1 === $wpdb->writes && 'Original' === unserialize( $wpdb->row['option_value'] )[ $key ], 'Group CAS retried or overwrote' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		$input = array_intersect_key( $before, $fields ); $input['cas_about_quote'] = 'Forged field';
		about_error( cas_content_prose_save( $group, 'theme_mods_hello-elementor-child', cas_content_prose_snapshot( $group, 'theme_mods_hello-elementor-child', $before ), $input ), $config['input_error'] );
		about_expect( 0 === $wpdb->writes, 'Foreign group key wrote' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		$GLOBALS['about_action'] = 'cas_content_about_save';
		about_error( prose_submit( $group, $before, $before, $fields ), $config['error'] . '_denied' );
		about_expect( 0 === $wpdb->writes, 'Cross-page nonce accepted' ); ++$cases;

		list( $before, $fields, $config ) = prose_fixture( $group );
		about_error( cas_content_prose_save( $group, 'theme_mods_hello-elementor-child', cas_content_prose_snapshot( 'about', 'theme_mods_hello-elementor-child', $before ), array_intersect_key( $before, $fields ) ), $config['error'] . '_stale' );
		about_expect( 0 === $wpdb->writes, 'Cross-page snapshot accepted' ); ++$cases;
	}
	about_reset();
	about_error( cas_content_prose_save( 'unapproved', 'theme_mods_hello-elementor-child', '', array() ), 'prose_denied' );
	about_expect( 0 === $wpdb->writes, 'Unapproved group wrote' ); ++$cases;
	fwrite( STDOUT, json_encode( array( 'synthetic_cases' => $cases, 'native_runtime_proof' => false ), JSON_THROW_ON_ERROR ) . PHP_EOL );
}
