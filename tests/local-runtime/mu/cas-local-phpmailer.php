<?php
/**
 * cas-local only: route every wp_mail() to the compose-internal Mailpit, unauthenticated, no TLS.
 * Not part of the theme and never deployed.
 */
add_action( 'phpmailer_init', function ( $phpmailer ) {
	$phpmailer->isSMTP();
	$phpmailer->Host        = 'mailpit';
	$phpmailer->Port        = 1025;
	$phpmailer->SMTPAuth    = false;
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->SMTPSecure  = '';
} );
