( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.cas-hero-image-choose', function () {
		var slot = $( this ).closest( '.cas-hero-image-slot' );
		var frame = slot.data( 'casHeroFrame' );
		if ( ! casHeroMedia.allowed || ! window.wp || ! wp.media ) {
			return;
		}
		if ( ! frame ) {
			frame = wp.media( {
				title: 'Choose a public site image',
				library: { type: 'image', cas_hero_media: casHeroMedia.nonce },
				button: { text: 'Use this image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var selected = frame.state().get( 'selection' ).first();
				var image = selected && selected.toJSON();
				var valid = image && image.type === 'image' && typeof image.url === 'string' && image.url.trim() !== '';
				if ( valid ) {
					try {
						var url = new URL( image.url, window.location.href );
						valid = ( url.protocol === 'https:' || url.protocol === 'http:' ) && ! url.searchParams.has( casHeroMedia.privateQuery );
					} catch ( error ) {
						valid = false;
					}
				}
				if ( ! valid ) {
					slot.find( '.cas-hero-media-error' ).prop( 'hidden', false );
					return;
				}
				slot.find( 'input[type=hidden]' ).val( image.url );
				slot.find( '.cas-hero-image-preview' ).empty().append(
					$( '<img>', { src: image.url, alt: '' } ).css( { maxWidth: '240px', height: 'auto', display: 'block' } )
				);
				slot.find( '.cas-hero-image-choose' ).text( 'Replace image' ).attr( 'aria-label', 'Replace image for ' + slot.data( 'casLabel' ) );
				slot.find( '.cas-hero-media-error' ).prop( 'hidden', true );
			} );
			slot.data( 'casHeroFrame', frame );
		}
		frame.open();
	} );

	$( document ).on( 'click', '.cas-hero-image-clear', function () {
		var slot = $( this ).closest( '.cas-hero-image-slot' );
		slot.find( 'input[type=hidden]' ).val( '' );
		var fallback = slot.data( 'casFallback' ) || '';
		slot.find( '.cas-hero-image-preview' ).empty();
		if ( fallback ) {
			slot.find( '.cas-hero-image-preview' ).append(
				$( '<img>', { src: fallback, alt: '' } ).css( { maxWidth: '240px', height: 'auto', display: 'block' } )
			);
		}
		slot.find( '.cas-hero-image-choose' ).text( 'Choose image' ).attr( 'aria-label', 'Choose image for ' + slot.data( 'casLabel' ) );
		slot.find( '.cas-hero-media-error' ).prop( 'hidden', true );
	} );
} )( jQuery );
