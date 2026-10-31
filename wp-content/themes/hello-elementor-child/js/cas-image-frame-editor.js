(() => {
  'use strict';
  const tabs = [...document.querySelectorAll('.cas-slide-tabs [role=tab]')];
  function activate(tab, focus = false) {
    for (const item of tabs) {
      const selected = item === tab;
      item.setAttribute('aria-selected', String(selected)); item.tabIndex = selected ? 0 : -1;
      document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
    }
    if (focus) tab.focus();
  }
  for (const [index, tab] of tabs.entries()) {
    tab.addEventListener('click', () => activate(tab));
    tab.addEventListener('keydown', event => {
      const next = {ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1}[event.key];
      if (next !== undefined) { event.preventDefault(); activate(tabs[next], true); }
    });
  }
  if (tabs.length) activate(tabs[0]);

  const parse = (url, fallbackY = 50) => {
    const match = url.match(/#cas-crop=([\d.]+),([\d.]+),([\d.]+)$/);
    return match ? {x: +match[1], y: +match[2], zoom: +match[3]} : {x: 50, y: fallbackY, zoom: 1};
  };
  const apply = (image, frame) => {
    image.style.objectPosition = `${frame.x}% ${frame.y}%`;
    image.style.scale = frame.zoom; image.style.transformOrigin = `${frame.x}% ${frame.y}%`;
  };
  const effectiveURL = slot => slot.querySelector('input[type=hidden]').value || slot.dataset.casFallback || '';
  window.casRefreshImageFrame = slot => {
    const image = slot.querySelector('.cas-hero-image-preview img');
    const button = slot.querySelector('.cas-image-reposition');
    if (button) button.disabled = !image;
    if (image) apply(image, parse(effectiveURL(slot), (effectiveURL(slot).split('#')[0] === slot.dataset.casFallback ? +slot.dataset.frameY || 50 : 50)));
  };
  document.querySelectorAll('.cas-hero-image-slot').forEach(window.casRefreshImageFrame);

  let dialog, slot, state, loaded = false;
  function createDialog() {
    dialog = document.createElement('dialog'); dialog.className = 'cas-crop-dialog';
    dialog.setAttribute('aria-labelledby', 'cas-crop-title');
    dialog.innerHTML = `<h2 id="cas-crop-title">Reposition Image</h2><p>Drag the picture or adjust the controls. The shape matches its Home slot. Changes are applied when you save this editing form.</p><div class="cas-crop-mat"><div class="cas-crop-viewport"><img alt="" draggable="false"></div></div><div class="cas-crop-controls"><label for="cas-crop-x">Horizontal</label><input id="cas-crop-x" type="range" min="0" max="100" step="0.1"><output for="cas-crop-x"></output><label for="cas-crop-y">Vertical</label><input id="cas-crop-y" type="range" min="0" max="100" step="0.1"><output for="cas-crop-y"></output><label for="cas-crop-zoom">Zoom</label><input id="cas-crop-zoom" type="range" min="1" max="3" step="0.01"><output for="cas-crop-zoom"></output></div><p role="status" data-crop-status></p><div class="cas-crop-footer"><button type="button" class="button" data-crop-cancel>Cancel</button><button type="button" class="button button-primary" data-crop-use>Use This Crop</button></div>`;
    document.body.append(dialog);
    const image = dialog.querySelector('img'), viewport = dialog.querySelector('.cas-crop-viewport');
    const status = dialog.querySelector('[data-crop-status]'), use = dialog.querySelector('[data-crop-use]');
    const update = () => {
      apply(image, state);
      for (const key of ['x','y','zoom']) {
        dialog.querySelector(`#cas-crop-${key}`).value = state[key];
        dialog.querySelector(`output[for=cas-crop-${key}]`).textContent = key === 'zoom' ? state[key].toFixed(2) + '×' : Math.round(state[key]) + '%';
      }
    };
    for (const key of ['x','y','zoom']) dialog.querySelector(`#cas-crop-${key}`).addEventListener('input', event => { state[key] = +event.target.value; update(); });
    image.addEventListener('load', () => { loaded = true; status.textContent = ''; use.disabled = false; update(); });
    image.addEventListener('error', () => { loaded = false; status.textContent = 'The image could not be loaded. Cancel and choose another image.'; use.disabled = true; });
    let drag;
    viewport.addEventListener('pointerdown', event => {
      if (!loaded || event.button !== 0) return;
      drag = {pointer: event.pointerId, x: event.clientX, y: event.clientY, frame: {...state}};
      viewport.setPointerCapture(event.pointerId);
    });
    viewport.addEventListener('pointermove', event => {
      if (!drag || drag.pointer !== event.pointerId) return;
      const w = viewport.clientWidth, h = viewport.clientHeight;
      const cover = Math.max(w / image.naturalWidth, h / image.naturalHeight) * state.zoom;
      const dx = image.naturalWidth * cover - w, dy = image.naturalHeight * cover - h;
      state.x = dx > 0.01 ? Math.max(0,Math.min(100,drag.frame.x - (event.clientX - drag.x) / dx * 100)) : drag.frame.x;
      state.y = dy > 0.01 ? Math.max(0,Math.min(100,drag.frame.y - (event.clientY - drag.y) / dy * 100)) : drag.frame.y;
      update();
    });
    const stop = () => { drag = null; };
    viewport.addEventListener('pointerup', stop); viewport.addEventListener('pointercancel', stop); viewport.addEventListener('lostpointercapture', stop);
    dialog.addEventListener('close', stop);
    dialog.querySelector('[data-crop-cancel]').addEventListener('click', () => dialog.close());
    use.addEventListener('click', () => {
      if (!loaded) return;
      const url = effectiveURL(slot).split('#')[0];
      slot.querySelector('input[type=hidden]').value = `${url}#cas-crop=${state.x.toFixed(2)},${state.y.toFixed(2)},${state.zoom.toFixed(2)}`;
      window.casRefreshImageFrame(slot);
      dialog.close();
    });
    dialog.updateCrop = update;
  }
  document.addEventListener('click', event => {
    const button = event.target.closest('.cas-image-reposition');
    if (!button) return;
    slot = button.closest('.cas-hero-image-slot');
    if (!dialog) createDialog();
    const url = effectiveURL(slot);
    state = parse(url, (effectiveURL(slot).split('#')[0] === slot.dataset.casFallback ? +slot.dataset.frameY || 50 : 50)); loaded = false;
    dialog.querySelector('[data-crop-use]').disabled = true;
    dialog.querySelector('[data-crop-status]').textContent = 'Loading image…';
    dialog.querySelector('.cas-crop-viewport').dataset.shape = slot.dataset.frameShape;
    dialog.querySelector('img').src = url.split('#')[0];
    dialog.updateCrop(); dialog.showModal();
    if (dialog.querySelector('img').complete && dialog.querySelector('img').naturalWidth) {
      loaded = true; dialog.querySelector('[data-crop-use]').disabled = false; dialog.querySelector('[data-crop-status]').textContent = '';
    }
  });
})();
