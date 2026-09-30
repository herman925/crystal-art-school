<?php
// Test only: send all mail to the Mailpit container, as the SMTP hook in cas-audit-log.php does on the server.
add_action( 'phpmailer_init', function ( $m ) { $m->isSMTP(); $m->Host = 'mailpit'; $m->Port = 1025; $m->SMTPAuth = false; $m->SMTPAutoTLS = false; }, 10 );
add_filter( 'wp_mail_from', function () { return 'hello@crystalau.test'; }, PHP_INT_MAX );
add_filter( 'wp_mail_from_name', function () { return 'Crystal Art School'; }, PHP_INT_MAX );
if ( ! function_exists( 'cas_audit_log' ) ) {
	function cas_audit_log( $e, $d = '' ) { file_put_contents( '/tmp/audit.log', $e . ' | ' . $d . "\n", FILE_APPEND ); }
}
