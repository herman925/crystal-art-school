/* Exercise the actual sidebar controls against WordPress-shaped editor state. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

let state = {
  type: 'post', excerpt: 'Existing card summary',
  meta: { _cas_blog_devnote: 'Awaiting approval', _elementor_edit_mode: 'builder' }
};
let render;
const changes = [];
const removed = [];
const wp = {
  element: { createElement: (component, props, ...children) => ({ component, props, children }) },
  editor: { PluginDocumentSettingPanel: 'panel' },
  components: { TextareaControl: 'textarea' },
  plugins: { registerPlugin: (_name, plugin) => { render = plugin.render; } },
  domReady: callback => callback(),
  data: {
    useSelect: callback => callback(() => ({
      getCurrentPostType: () => state.type,
      getEditedPostAttribute: name => state[name]
    })),
    useDispatch: () => ({ editPost: change => changes.push(change) }),
    dispatch: () => ({ removeEditorPanel: name => removed.push(name) })
  }
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,
  '../wp-content/themes/hello-elementor-child/js/cas-blog-editor.js'), 'utf8'), { window: { wp } });

assert.deepEqual(removed, ['post-excerpt']);
let [abstract, note] = render().children;
assert.equal(abstract.props.label, 'Blog Abstract');
assert.equal(note.props.label, 'Developer’s Note');
assert.equal(abstract.props.value, state.excerpt);
assert.equal(note.props.value, state.meta._cas_blog_devnote);
assert.match(note.props.help, /Publicly visible/);

abstract.props.onChange('New card summary');
assert.equal(changes.at(-1).excerpt, 'New card summary');
assert.equal(changes.at(-1).meta, undefined);
note.props.onChange('New public notice');
assert.equal(changes.at(-1).meta._cas_blog_devnote, 'New public notice');
assert.equal(changes.at(-1).meta._elementor_edit_mode, 'builder');
assert.equal(changes.at(-1).excerpt, undefined);
note.props.onChange('');
assert.equal(changes.at(-1).meta._cas_blog_devnote, '');
abstract.props.onChange('');
assert.equal(changes.at(-1).excerpt, '');

state = { type: 'post', excerpt: '', meta: undefined };
[abstract, note] = render().children;
assert.equal(abstract.props.value, '');
assert.equal(note.props.value, '');
state.type = 'page';
assert.equal(render(), null);
console.log('Blog sidebar behavior passed: preserved values, independent edits/clears, ordering and post-only scope.');
