/* Keep artwork sale details and its full image visible when opening the editor. */
wp.domReady( function () {
	var editor = wp.data.dispatch( 'core/edit-post' );
	if ( editor && editor.openGeneralSidebar ) { editor.openGeneralSidebar( 'edit-post/document' ); }
} );
