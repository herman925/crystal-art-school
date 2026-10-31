'use strict';
// Synthetic DOM/media model only; not native WordPress, uploads, permissions or browser proof.
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const handlers = {};
const frames = [];
const uploadHandlers = [];
const document = {};
const config = { allowed: true, nonce: 'synthetic-only', privateQuery: 'cas_private_file' };
function slot() {
	return { value: '', preview: [], hidden: true, text: 'Choose image', aria: 'Choose image for Synthetic image field', data: { casLabel: 'Synthetic image field' } };
}
function wrapped( s ) {
	return {
		data( name, value ) { if ( arguments.length === 2 ) { s.data[ name ] = value; } return s.data[ name ]; },
		find( selector ) {
			const control = {
				val( value ) { s.value = value; return control; },
				empty() { s.preview = []; return control; },
				append( value ) { s.preview.push( value ); return control; },
				text( value ) { s.text = value; return control; },
				attr( name, value ) { assert.equal( selector, '.cas-hero-image-choose' ); assert.equal( name, 'aria-label' ); s.aria = value; return control; },
				prop( name, value ) { assert.equal( selector, '.cas-hero-media-error' ); assert.equal( name, 'hidden' ); s.hidden = value; return control; }
			};
			return control;
		}
	};
}
function jquery( object, attrs ) {
	if ( object === document ) {
		return { on( event, selector, callback ) { assert.equal( event, 'click' ); handlers[ selector ] = callback; } };
	}
	if ( object === '<img>' ) {
		return { attrs, css( value ) { this.style = value; return this; } };
	}
	return { closest( selector ) { assert.equal( selector, '.cas-hero-image-slot' ); return wrapped( object ); } };
}
const wp = { media( options ) {
	const frame = {
		options, events: {}, selected: null, opens: 0, visible: true, approvedUpload: null, refreshes: 0,
		$el: { is() { return frame.visible; } },
		on( event, callback ) { this.events[ event ] = callback; },
		open() { this.opens++; },
		state() { return { get: name => {
			if ( name === 'library' ) { return {
				props: { set( key, value ) { assert.equal( key, 'cas_hero_refresh' ); assert.equal( typeof value, 'number' ); frame.refreshes++; } },
				more() { return { done( callback ) { callback(); return { fail() {} }; } }; },
				get( id ) { return frame.approvedUpload && frame.approvedUpload.id === id ? frame.approvedUpload : undefined; }
			}; }
			assert.equal( name, 'selection' );
			return { first: () => this.selected && { toJSON: () => this.selected }, reset: values => { frame.selected = values[0] || null; } };
		} }; }
	};
	frames.push( frame );
	return frame;
}, Uploader: { queue: { on( event, callback ) { assert.equal( event, 'change:uploading' ); uploadHandlers.push( callback ); } } } };
const source = fs.readFileSync( path.join( __dirname, '../wp-content/themes/hello-elementor-child/js/cas-content-hero.js' ), 'utf8' );
vm.runInNewContext( source, { jQuery: jquery, document, wp, casHeroMedia: config, URL, window: { wp, location: { href: 'https://example.invalid/admin.php' } } } );
const s = slot();
const choose = handlers[ '.cas-hero-image-choose' ];
const clear = handlers[ '.cas-hero-image-clear' ];
choose.call( s );
const frame = frames[ 0 ];
assert.equal( frame.options.library.type, 'image' );
assert.equal( frame.options.library.cas_hero_media, config.nonce );
assert.equal( frame.options.multiple, false );
frame.selected = { id: 101, type: 'image', url: 'https://example.invalid/public.jpg' };
frame.events.select();
assert.equal( s.value, frame.selected.url );
assert.notEqual( s.value, frame.selected.id );
assert.equal( s.preview[ 0 ].attrs.src, frame.selected.url );
assert.equal( s.text, 'Replace image' );
assert.equal( s.aria, 'Replace image for ' + s.data.casLabel );
frame.selected = { id: 102, type: 'image', url: 'https://example.invalid/replacement.jpg' };
frame.events.select();
assert.equal( s.value, frame.selected.url );
choose.call( s );
assert.equal( frames.length, 1 );
assert.equal( frame.opens, 2 );
const before = s.value;
frame.selected = { type: 'image', url: 'https://example.invalid/?cas_private_file=77' };
frame.events.select();
assert.equal( s.value, before );
assert.equal( s.hidden, false );
for ( const value of [ { type: 'application', url: 'https://example.invalid/file.pdf' }, { type: 'image', url: '' }, { type: 'image', url: 'javascript:alert(1)' } ] ) {
	frame.selected = value;
	frame.events.select();
	assert.equal( s.value, before );
}
config.allowed = false;
choose.call( slot() );
assert.equal( frames.length, 1 );
clear.call( s );
assert.equal( s.value, '' );
assert.equal( s.preview.length, 0 );
assert.equal( s.hidden, true );
assert.equal( s.text, 'Choose image' );
assert.equal( s.aria, 'Choose image for ' + s.data.casLabel );
const restored = slot();
restored.value = 'https://example.invalid/replacement.jpg';
restored.data.casFallback = 'https://example.invalid/shipped.jpg';
clear.call( restored );
assert.equal( restored.value, '' );
assert.equal( restored.preview[ 0 ].attrs.src, restored.data.casFallback );
assert.equal( restored.text, 'Choose image' );
assert.equal( restored.aria, 'Choose image for ' + restored.data.casLabel );
config.allowed = true;
const alternate = slot();
alternate.data.casLabel = 'Alternate image context';
choose.call( alternate );
const alternateFrame = frames[ frames.length - 1 ];
alternateFrame.selected = { type: 'image', url: 'https://example.invalid/alternate.jpg' };
alternateFrame.events.select();
assert.equal( alternate.text, 'Replace image' );
assert.equal( alternate.aria, 'Replace image for Alternate image context' );
clear.call( alternate );
assert.equal( alternate.text, 'Choose image' );
assert.equal( alternate.aria, 'Choose image for Alternate image context' );
const uploaded = { id: 301, get: key => ( { uploading: false, type: 'image' } )[ key ] };
frame.visible = false;
uploadHandlers[0]( uploaded );
assert.equal( frame.refreshes, 0 );
frame.visible = true;
uploadHandlers[0]( { id: 302, get: key => ( { uploading: true, type: 'image' } )[ key ] } );
assert.equal( frame.refreshes, 0 );
frame.approvedUpload = { id: 301, type: 'image', url: 'https://example.invalid/new-public-image.png' };
uploadHandlers[0]( uploaded );
assert.equal( frame.refreshes, 1 );
assert.equal( frame.selected, frame.approvedUpload );
assert.equal( s.value, '' ); // Approval selects the model; only Use this image fills the field.
frame.events.select();
assert.equal( s.value, frame.approvedUpload.url );
frame.approvedUpload = null;
uploadHandlers[0]( uploaded );
assert.equal( frame.selected, null );
assert.equal( s.hidden, false );
assert.equal( s.value, 'https://example.invalid/new-public-image.png' );
console.log( JSON.stringify( { synthetic_media_cases: 14, native_runtime_proof: false } ) );
