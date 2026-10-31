<?php
/** Framing travels with the image URL, keeping replacement and guarded form saves atomic. */
defined( 'ABSPATH' ) || exit;

function cas_image_frame( $url ) {
	$fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );
	if ( ! is_string( $fragment ) || ! str_starts_with( $fragment, 'cas-crop=' ) ) { return null; }
	if ( ! preg_match( '/^cas-crop=([0-9]+(?:\.[0-9]{1,2})?),([0-9]+(?:\.[0-9]{1,2})?),([0-9]+(?:\.[0-9]{1,2})?)$/D', $fragment, $match ) ) { return false; }
	$values = array_map( 'floatval', array_slice( $match, 1 ) );
	return $values[0] <= 100 && $values[1] <= 100 && $values[2] >= 1 && $values[2] <= 3 ? $values : false;
}
function cas_image_frame_url( $url ) {
	return is_array( cas_image_frame( $url ) ) ? preg_replace( '/#.*$/', '', $url ) : $url;
}
function cas_image_frame_style( $url ) {
	$frame = cas_image_frame( $url );
	return is_array( $frame ) ? sprintf( 'object-position:%s%% %s%%;scale:%s;transform-origin:%s%% %s%%;', $frame[0], $frame[1], $frame[2], $frame[0], $frame[1] ) : '';
}
function cas_image_frame_html( $html, $url ) {
	$style = cas_image_frame_style( $url );
	if ( ! $style || ! $html ) { return $html; }
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag( 'IMG' ) ) { $tags->set_attribute( 'style', (string) $tags->get_attribute( 'style' ) . ';' . $style ); }
	return $tags->get_updated_html();
}
