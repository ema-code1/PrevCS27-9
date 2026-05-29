/* PrevCS global smooth scroll. Loaded by templates/layout.php. */
(() => {
  'use strict';

  if (window.__PCS_SMOOTH_SCROLL_READY) return;
  window.__PCS_SMOOTH_SCROLL_READY = true;

  const prefersReducedMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const state = {
    lenis: null,
    rafId: 0
  };

  const easing = t => Math.min(1, 1.001 - Math.pow(2, -10 * t));

  function fixedOffset() {
    const header = document.querySelector('.topbar, .landing-header, header');
    if (!header) return 0;

    const style = window.getComputedStyle(header);
    if (style.position !== 'fixed' && style.position !== 'sticky') return 0;

    return Math.ceil(header.getBoundingClientRect().height + 12);
  }

  function findTarget(hash) {
    if (!hash || hash === '#') return null;

    const id = hash.charAt(0) === '#' ? hash.slice(1) : hash;
    if (!id) return null;

    const decoded = decodeURIComponent(id);
    const byId = document.getElementById(decoded);
    if (byId) return byId;

    if (!window.CSS || typeof window.CSS.escape !== 'function') return null;
    return document.querySelector(`[name="${window.CSS.escape(decoded)}"]`);
  }

  function nativeScrollTo(target, options = {}) {
    const offset = typeof options.offset === 'number' ? options.offset : -fixedOffset();
    const immediate = options.immediate || prefersReducedMotion;

    if (typeof target === 'number') {
      window.scrollTo({ top: Math.max(0, target + offset), behavior: immediate ? 'auto' : 'smooth' });
      return;
    }

    if (typeof target === 'string') {
      const found = findTarget(target);
      if (!found) return;
      target = found;
    }

    if (!target || typeof target.getBoundingClientRect !== 'function') return;

    const top = target.getBoundingClientRect().top + window.scrollY + offset;
    window.scrollTo({ top: Math.max(0, top), behavior: immediate ? 'auto' : 'smooth' });
  }

  const api = {
    get lenis() {
      return state.lenis;
    },
    scrollTo(target, options = {}) {
      const offset = typeof options.offset === 'number' ? options.offset : -fixedOffset();

      if (state.lenis && typeof state.lenis.scrollTo === 'function' && !prefersReducedMotion) {
        state.lenis.scrollTo(target, {
          offset,
          immediate: !!options.immediate,
          duration: typeof options.duration === 'number' ? options.duration : 1.05,
          easing
        });
        return;
      }

      nativeScrollTo(target, { ...options, offset });
    },
    resize() {
      if (state.lenis && typeof state.lenis.resize === 'function') state.lenis.resize();
      if (window.ScrollTrigger && typeof window.ScrollTrigger.refresh === 'function') {
        window.ScrollTrigger.refresh();
      }
    },
    stop() {
      if (state.lenis && typeof state.lenis.stop === 'function') state.lenis.stop();
    },
    start() {
      if (state.lenis && typeof state.lenis.start === 'function') state.lenis.start();
    }
  };

  window.PCSSmoothScroll = api;
  window.PCSLenis = null;

  function syncScrollTools(event) {
    if (window.ScrollTrigger && typeof window.ScrollTrigger.update === 'function') {
      window.ScrollTrigger.update();
    }
    window.dispatchEvent(new CustomEvent('pcs:smooth-scroll', { detail: event || null }));
  }

  function initLenis() {
    if (prefersReducedMotion || window.__PCS_DISABLE_SMOOTH_SCROLL) return;
    if (typeof window.Lenis !== 'function') return;

    const lenis = new window.Lenis({
      duration: 1.05,
      easing,
      smoothWheel: true,
      syncTouch: false,
      gestureOrientation: 'vertical',
      wheelMultiplier: 0.85,
      touchMultiplier: 1.1,
      autoResize: true
    });

    state.lenis = lenis;
    window.PCSLenis = lenis;
    document.documentElement.classList.add('pcs-smooth-scroll-ready');

    lenis.on('scroll', syncScrollTools);

    const raf = (time) => {
      lenis.raf(time);
      state.rafId = window.requestAnimationFrame(raf);
    };

    state.rafId = window.requestAnimationFrame(raf);
    syncScrollTools();
  }

  document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest('a[href*="#"]');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href || href === '#') return;

    let url;
    try {
      url = new URL(href, window.location.href);
    } catch (error) {
      return;
    }

    const samePage = url.origin === window.location.origin
      && url.pathname.replace(/\/+$/, '') === window.location.pathname.replace(/\/+$/, '')
      && url.search === window.location.search;

    if (!samePage || !url.hash) return;

    const target = findTarget(url.hash);
    if (!target) return;

    event.preventDefault();
    api.scrollTo(target);
    window.history.replaceState(null, '', url.hash);
  });

  window.addEventListener('load', () => {
    api.resize();

    if (window.location.hash) {
      const target = findTarget(window.location.hash);
      if (target) window.setTimeout(() => api.scrollTo(target, { immediate: true }), 60);
    }
  }, { once: true });

  window.addEventListener('resize', () => api.resize(), { passive: true });

  initLenis();
})();
