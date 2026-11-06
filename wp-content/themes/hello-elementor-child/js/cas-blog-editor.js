/* Blog-only sidebar fields; WordPress owns saving, autosaves and revisions. */
(function (wp) {
  'use strict';

  const el = wp.element.createElement;
  const noteKey = '_cas_blog_devnote';

  function BlogDetails() {
    const post = wp.data.useSelect(function (select) {
      const editor = select('core/editor');
      return {
        type: editor.getCurrentPostType(),
        excerpt: editor.getEditedPostAttribute('excerpt'),
        meta: editor.getEditedPostAttribute('meta') || {}
      };
    }, []);
    const editPost = wp.data.useDispatch('core/editor').editPost;

    if (post.type !== 'post') return null;

    return el(wp.editor.PluginDocumentSettingPanel, {
      name: 'blog-details',
      title: 'Blog Abstract & Developer’s Note'
    },
    el(wp.components.TextareaControl, {
      label: 'Blog Abstract',
      help: 'Short summary shown below the title on Blog cards. Leave blank to use text from the article.',
      value: post.excerpt || '',
      onChange: function (value) { editPost({ excerpt: value }); }
    }),
    el(wp.components.TextareaControl, {
      label: 'Developer’s Note',
      help: 'Publicly visible in an amber box at the top of the article. Use for corrections awaiting the author’s approval; clear it after approval.',
      value: post.meta[noteKey] || '',
      onChange: function (value) {
        editPost({ meta: Object.assign({}, post.meta, { [noteKey]: value }) });
      }
    }));
  }

  wp.plugins.registerPlugin('cas-blog-details', { render: BlogDetails });
  wp.domReady(function () {
    wp.data.dispatch('core/editor').removeEditorPanel('post-excerpt');
  });
})(window.wp);
