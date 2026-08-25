/* The five picture slots on the course screen.
   One wp.media frame per slot, kept between openings so it reopens on the
   library the person was last looking at rather than back at Upload. */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.cas-photo-choose', function () {
		var slot = $( this ).closest( '.cas-photo-slot' );
		var frame = slot.data( 'casFrame' );

		if ( ! frame ) {
			frame = wp.media( {
				title: 'Choose ' + slot.find( 'strong' ).text().toLowerCase(),
				library: { type: 'image' },
				button: { text: 'Use this picture' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				/* The medium size is what the preview box is drawn at; a library item
				   small enough never to have been resized only has the original. */
				var src = ( att.sizes && att.sizes.medium ) ? att.sizes.medium.url : att.url;

				slot.find( 'input[type=hidden]' ).val( att.id );
				slot.find( '.cas-photo-preview' ).html(
					$( '<img>', { src: src, alt: '' } ).css( {
						maxWidth: '100%',
						height: 'auto',
						display: 'block',
						borderRadius: '4px',
					} )
				);
				slot.find( '.cas-photo-choose' ).text( 'Replace' );
				slot.find( '.cas-photo-clear' ).show();
			} );

			slot.data( 'casFrame', frame );
		}

		frame.open();
	} );

	$( document ).on( 'click', '.cas-photo-clear', function () {
		var slot = $( this ).closest( '.cas-photo-slot' );

		slot.find( 'input[type=hidden]' ).val( '' );
		slot.find( '.cas-photo-preview' ).empty();
		slot.find( '.cas-photo-choose' ).text( 'Choose picture' );
		$( this ).hide();
	} );
} )( jQuery );
