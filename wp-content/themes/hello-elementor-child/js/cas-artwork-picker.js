(() => {
  'use strict';
  const root = document.getElementById('home-gallery-image');
  if (!root) return;
  const dialog = root.querySelector('dialog');
  const open = root.querySelector('[data-gallery-open]');
  const save = root.querySelector('[data-gallery-save]');
  const cancel = root.querySelector('[data-gallery-cancel]');
  const error = root.querySelector('[data-gallery-error]');
  let busy = false;
  let blocked = false;
  const reset = () => root.querySelectorAll('input[type=radio]').forEach(radio => { radio.checked = radio.value === root.dataset.chosen; });
  open.addEventListener('click', () => { reset(); error.textContent = ''; dialog.showModal(); });
  cancel.addEventListener('click', () => { if (!busy) { reset(); dialog.close(); } });
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); else reset(); });
  save.addEventListener('click', async () => {
    const selected = root.querySelector('input:checked');
    if (busy || blocked || !selected) return;
    busy = true; save.disabled = true; cancel.disabled = true;
    error.textContent = 'Saving…';
    try {
      const response = await fetch(root.dataset.endpoint, {
        method: 'POST', credentials: 'same-origin',
        body: new URLSearchParams({ action: 'cas_home_gallery_save', _wpnonce: root.dataset.nonce, snapshot: root.dataset.snapshot, artwork: selected.value })
      });
      const result = await response.json();
      if (!result.success) {
        blocked = result.data?.reload !== false;
        error.textContent = result.data?.message || 'Saving could not be confirmed. Keep unsaved text and reload before trying again.';
        return;
      }
      root.dataset.chosen = String(result.data.id);
      root.dataset.snapshot = result.data.snapshot;
      root.dataset.nonce = result.data.nonce;
      const image = root.querySelector('.cas-gallery-current img');
      image.src = result.data.image; image.hidden = false;
      root.querySelector('[data-current-title]').textContent = result.data.title;
      root.querySelector('[data-gallery-status]').textContent = 'Gallery Image Saved. Cached Home pages may take time to refresh.';
      dialog.close();
    } catch (_) {
      blocked = true;
      error.textContent = 'Saving could not be confirmed. Keep any unsaved text and reload before trying again.';
    } finally {
      busy = false; save.disabled = blocked; cancel.disabled = false;
      if (blocked) open.disabled = true;
    }
  });
  if (location.hash === '#home-gallery-image' && !open.disabled) open.click();
})();
