/* Homepage New release carousel. */
(function () {
  'use strict';

  var INTERVAL = 6000;

  function whenReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function reducedMotion() {
    var media = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var automated = typeof navigator !== 'undefined' &&
      (navigator.webdriver === true || /Headless/i.test(navigator.userAgent || ''));
    return !!media || automated;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        '\'': '&#39;'
      }[character];
    });
  }

  function safeHref(value) {
    var href = String(value == null ? '' : value).trim();
    return /^(?:javascript|data|vbscript):/i.test(href) ? '#' : (href || '#');
  }

  function courseItems() {
    var source = Array.isArray(window.CAS_COURSES) ? window.CAS_COURSES.slice() : [];
    return source.map(function (course) {
      var lessons = Array.isArray(course.lessons) ? course.lessons.length : (course.lessons || course.lessonCount || 0);
      var level = String(course.level || course.difficulty || '').replace(/-/g, ' ');
      level = level ? level.charAt(0).toUpperCase() + level.slice(1) : '';
      return {
        title: String(course.title || ''),
        topic: String(course.topic || course.title || ''),
        tag: String(course.tag || course.media || ''),
        image: String(course.img || course.cover || course.image || ''),
        srcset: String((course.imgMeta && course.imgMeta.srcset) || course.srcset || ''),
        sizes: String((course.imgMeta && course.imgMeta.sizes) || course.sizes || ''),
        width: (course.imgMeta && course.imgMeta.w) || course.width || '',
        height: (course.imgMeta && course.imgMeta.h) || course.height || '',
        lessons: lessons,
        level: level,
        price: course.price == null ? 0 : course.price,
        was: course.was == null ? '' : course.was,
        date: String(course.date || ''),
        href: safeHref(course.url || course.href || (course.id ? 'course.dc.html?id=' + encodeURIComponent(course.id) : '#'))
      };
    }).filter(function (course) {
      return course.title !== '';
    }).sort(function (a, b) {
      return b.date.localeCompare(a.date);
    }).slice(0, 3);
  }

  function cartIcon() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle>' +
      '<path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>';
  }

  function slideMarkup(course, index, total) {
    var short = course.topic.trim();
    var fit = short.length <= 12 ? '' : (short.length <= 22 ? ' is-mid' : ' is-long');
    var image = course.image;
    var attrs = course.srcset
      ? ' srcset="' + escapeHtml(course.srcset) + '" sizes="' + escapeHtml(course.sizes) + '"' +
        (course.width && course.height ? ' width="' + escapeHtml(course.width) + '" height="' + escapeHtml(course.height) + '"' : '')
      : '';
    return '<div class="rel-slide' + (index === 0 ? ' is-active' : '') + '" role="group" aria-roledescription="slide" aria-label="' +
      (index + 1) + ' of ' + total + '"' + (index === 0 ? '' : ' aria-hidden="true" inert') + '>' +
      '<img src="' + escapeHtml(image) + '"' + attrs + ' alt="' + escapeHtml(course.title) + '"' +
      (index === 0 ? ' fetchpriority="high"' : ' fetchpriority="low"') + ' decoding="async">' +
      '<div class="rel-title' + fit + '" title="' + escapeHtml(course.title) + '">' + escapeHtml(short) + '</div>' +
      '<div class="rel-facts"><span><i>Media</i>' + escapeHtml(course.tag.split(/\s*[&,\/]\s*/)[0]) +
      '</span><span><i>Lessons</i>' + escapeHtml(course.lessons) + '</span><span><i>Level</i>' +
      escapeHtml(course.level) + '</span></div>' +
      '<div class="rel-actions"><button class="cc-btn primary" type="button" data-add-to-cart data-tiered data-title="' +
      escapeHtml(course.title) + '" data-price="' + escapeHtml(course.price) + '"' +
      (course.was !== '' ? ' data-was="' + escapeHtml(course.was) + '"' : '') +
      ' data-img="' + escapeHtml(course.image) + '">' + cartIcon() + ' Add to cart</button>' +
      '<a class="cc-btn secondary" href="' + escapeHtml(course.href) + '">View <span class="arr" aria-hidden="true">→</span></a></div></div>';
  }

  function hidden(slide, isHidden) {
    if (isHidden) {
      slide.setAttribute('aria-hidden', 'true');
      slide.setAttribute('inert', '');
    } else {
      slide.removeAttribute('aria-hidden');
      slide.removeAttribute('inert');
    }
  }

  function announce(message) {
    if (typeof window.CasAnnounce === 'function') {
      window.CasAnnounce(message);
    }
  }

  function initCarousel(host) {
    if (host.__casReleaseCarousel) {
      return;
    }
    host.__casReleaseCarousel = true;

    var dots = host.querySelector('.rel-dots');
    var pause = host.querySelector('[data-rel-pause]');
    var slides = Array.prototype.slice.call(host.querySelectorAll('.rel-slide'));

    if (!slides.length) {
      var courses = courseItems();
      courses.forEach(function (course, index) {
        host.insertAdjacentHTML('beforeend', slideMarkup(course, index, courses.length));
      });
      slides = Array.prototype.slice.call(host.querySelectorAll('.rel-slide'));
    }
    if (!slides.length) {
      if (pause) {
        pause.remove();
      }
      return;
    }
    if (!dots) {
      dots = document.createElement('div');
      dots.className = 'rel-dots';
      host.insertBefore(dots, host.firstChild);
    }
    dots.textContent = '';

    var current = 0;
    var isReduced = reducedMotion();
    var timer = null;
    var paused = false;
    var hovered = false;
    var focused = false;

    function show(index, spoken) {
      current = (index + slides.length) % slides.length;
      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === current);
        hidden(slide, slideIndex !== current);
      });
      Array.prototype.forEach.call(dots.querySelectorAll('button'), function (dot, dotIndex) {
        dot.classList.toggle('active', dotIndex === current);
        if (dotIndex === current) {
          dot.setAttribute('aria-current', 'true');
        } else {
          dot.removeAttribute('aria-current');
        }
      });
      if (spoken) {
        var title = slides[current].querySelector('.rel-title');
        title = title ? (title.getAttribute('title') || title.textContent) : '';
        announce('Release ' + (current + 1) + ' of ' + slides.length + '. ' + title);
      }
    }

    slides.forEach(function (slide, index) {
      var title = slide.querySelector('.rel-title');
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('aria-label', 'Show release ' + (index + 1) + ': ' +
        (title ? (title.getAttribute('title') || title.textContent) : ''));
      dot.addEventListener('click', function () {
        show(index, true);
        sync();
      });
      dots.appendChild(dot);
    });

    function shouldRun() {
      return !isReduced && !paused && !hovered && !focused && !document.hidden && slides.length > 1;
    }
    function sync() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
      if (shouldRun()) {
        timer = setInterval(function () {
          show(current + 1, false);
        }, INTERVAL);
      }
    }

    show(0, false);

    if (pause) {
      if (isReduced) {
        pause.remove();
      } else {
        pause.addEventListener('click', function () {
          paused = !paused;
          pause.setAttribute('aria-pressed', paused ? 'true' : 'false');
          pause.setAttribute('aria-label', (paused ? 'Play the ' : 'Pause the ') + 'new releases carousel');
          sync();
          announce(paused ? 'New releases carousel paused.' : 'New releases carousel playing.');
        });
      }
    }
    host.addEventListener('mouseenter', function () {
      hovered = true;
      sync();
    });
    host.addEventListener('mouseleave', function () {
      hovered = false;
      sync();
    });
    host.addEventListener('focusin', function () {
      focused = true;
      sync();
    });
    host.addEventListener('focusout', function () {
      setTimeout(function () {
        focused = host.contains(document.activeElement);
        sync();
      }, 0);
    });
    document.addEventListener('visibilitychange', sync);
    sync();
  }

  function init() {
    document.querySelectorAll('.rel-carousel').forEach(initCarousel);
  }

  whenReady(init);
}());
