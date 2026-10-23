(() => {
  const form = document.getElementById('cas-builder-add-course');
  if (!form) return;
  const message = form.querySelector('.cas-builder-feedback');
  const button = form.querySelector('button');
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    button.disabled = true;
    message.textContent = 'Creating your course draft…';
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok || result.status_code !== 201 || typeof result.data !== 'string') {
        throw new Error(result.message || result.data?.message || 'The course draft could not be created. Please contact Herman.');
      }
      const destination = new URL(result.data, window.location.href);
      if (destination.origin !== window.location.origin || destination.searchParams.get('page') !== 'create-course'
        || !/^\d+$/.test(destination.searchParams.get('course_id') || '')) {
        throw new Error('The builder returned an unexpected destination. Please contact Herman.');
      }
      window.location.assign(destination.href);
    } catch (error) {
      message.textContent = error.message;
      button.disabled = false;
    }
  });
})();
