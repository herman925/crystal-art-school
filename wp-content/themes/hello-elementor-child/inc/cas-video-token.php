<?php
/**
 * Signed video addresses.
 *
 * The films are protected by referer alone. A foreign page cannot embed the stream and a
 * stream address pasted into a browser is refused - but the video host allows its own
 * player origin, so anyone holding a video identifier can put the player on a page of
 * their own and it plays. Today that is contained only by where the identifiers appear:
 * on lesson pages, behind enrolment. An enrolled student can still read one out of the
 * page and pass it on.
 *
 * A signed address closes that. The signature covers one video and one moment; it stops
 * working when it expires, and an address with no signature is refused outright.
 *
 * The key is a server secret and lives in wp-config.php as CAS_BUNNY_TOKEN_KEY. It is
 * never printed, never written to the repository and never reaches the browser - only
 * the signature it produces does.
 *
 * Signing runs for signed-out visitors too, because the free preview lessons must keep
 * working for someone who has not bought anything.
 */

defined( 'ABSPATH' ) || exit;

/**
 * How long a signed address stays valid.
 *
 * Long enough to open a lesson, read the page and press play on a slow connection;
 * short enough that a copied address is worthless by the time it is passed on. The
 * player keeps playing once it has started - the signature is checked when the player
 * is requested, not for every second of video.
 */
const CAS_VIDEO_TOKEN_TTL = 3600;

/**
 * The signing key, or an empty string when none is configured.
 *
 * An empty key means every address is left exactly as it was found. That is the safe
 * failure: the site keeps working on referer protection alone rather than serving
 * signatures that cannot be valid.
 *
 * @return string
 */
function cas_video_token_key() {
	return defined( 'CAS_BUNNY_TOKEN_KEY' ) ? (string) CAS_BUNNY_TOKEN_KEY : '';
}

/**
 * Sign one video address.
 *
 * The signature is a SHA-256 of the key, the video identifier and the expiry, in that
 * order, written as hexadecimal - the form the video host specifies. An address that
 * already carries a signature has it replaced, not kept: a signature made with an older
 * key, or one that is about to expire, must not survive, and signing twice just gives the
 * address a fresh expiry.
 *
 * @param string $url A player address on the video host.
 * @return string The same address with a signature, or unchanged when it cannot be signed.
 */
function cas_video_sign_url( $url ) {
	$key = cas_video_token_key();

	if ( '' === $key || ! is_string( $url ) || '' === $url ) {
		return $url;
	}

	if ( ! preg_match( '~^https://(?:iframe|video)\.mediadelivery\.net/(?:embed|play)/([0-9]+)/([0-9a-f-]{36})~i', $url, $m ) ) {
		return $url;
	}

	/* Whatever signature the address arrived with is dropped first; the other parameters
	   (autoplay, preload) are kept exactly as they were. */
	$url = remove_query_arg( array( 'token', 'expires' ), $url );

	$guid    = strtolower( $m[2] );
	$expires = time() + CAS_VIDEO_TOKEN_TTL;
	$token   = hash( 'sha256', $key . $guid . $expires );

	return add_query_arg(
		array(
			'token'   => $token,
			'expires' => $expires,
		),
		$url
	);
}

/**
 * Sign every video address inside a block of markup.
 *
 * Written against the address rather than the surrounding tag, so it holds whether the
 * player arrives as an iframe, a link or a data attribute.
 *
 * @param string $html
 * @return string
 */
function cas_video_sign_markup( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'mediadelivery.net' ) ) {
		return $html;
	}

	return (string) preg_replace_callback(
		'~https://(?:iframe|video)\.mediadelivery\.net/(?:embed|play)/[0-9]+/[0-9a-f-]{36}[^"\'\s<>]*~i',
		function ( $m ) {
			return cas_video_sign_url( html_entity_decode( $m[0], ENT_QUOTES, 'UTF-8' ) );
		},
		$html
	);
}

/**
 * Sign the player the course plugin stores against a lesson.
 *
 * A lesson keeps its player as a block of embed markup in its own `_video` record, and
 * the plugin prints that record in several places - the lesson page, the preview on the
 * course page, the curriculum list. Signing where the record is read covers all of them
 * at once, and covers any future place that reads it.
 *
 * The guard stops the read inside this filter from calling the filter again.
 */
add_filter(
	'get_post_metadata',
	function ( $value, $object_id, $meta_key, $single ) {
		static $busy = false;

		if ( $busy || '_video' !== $meta_key || '' === cas_video_token_key() ) {
			return $value;
		}

		if ( null !== $value ) {
			return $value;
		}

		$busy = true;
		$raw  = get_post_meta( $object_id, '_video', true );
		$busy = false;

		if ( ! is_array( $raw ) || empty( $raw['source_embedded'] ) ) {
			return $value;
		}

		$raw['source_embedded'] = cas_video_sign_markup( $raw['source_embedded'] );

		/* get_metadata_raw() takes element zero of whatever this filter returns when
		   $single is true, and returns it whole when $single is false. So a list is
		   the right answer either way, and returning the record itself produces an
		   "Undefined array key 0" warning and the wrong value. */
		return array( $raw );
	},
	10,
	4
);
