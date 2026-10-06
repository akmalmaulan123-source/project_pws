/* Gerak halaman: elemen muncul halus saat masuk layar, dan transisi saat pindah halaman.
   Tanpa JS atau dengan "reduce motion", semua konten tetap tampil normal. */
(function () {
    'use strict';

    var root = document.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;
    var main = document.getElementById('main');
    if (reduce) { return; }

    /* ---------- 1. Muncul bertahap saat masuk layar ---------- */
    var SELECTOR = [
        '.page-head', '.crumbs', '.filters', '.results-bar', '.rows-head',
        '.rows > .row', '.make-grid > li', '.brand-head', '.cmp-actions',
        '.table-wrap', 'details.gen', '.block', '.empty', '.prose-page > *',
        '.pager', 'nav[aria-label="Pagination"]'
    ].join(',');

    // Mulai sedikit SEBELUM elemen masuk layar, supaya saat di-scroll sudah bergerak
    var io = hasIO ? new IntersectionObserver(function (entries) {
        var shown = entries.filter(function (e) { return e.isIntersecting; })
            .sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });

        // jeda bertingkat hanya antar elemen yang muncul bersamaan (maks. 4 langkah, 40ms)
        shown.forEach(function (e, i) {
            e.target.style.setProperty('--mo-d', Math.min(i, 4) * 40 + 'ms');
            e.target.classList.add('mo-in');
            io.unobserve(e.target);
        });
    }, { threshold: 0, rootMargin: '0px 0px 140px 0px' }) : null;

    function prepare(scope) {
        if (!main) { return; }
        var quiet = root.classList.contains('live-quiet');
        var list = [].slice.call((scope || main).querySelectorAll(SELECTOR)).filter(function (el) {
            return !el.hasAttribute('data-mo') &&
                !el.closest('[data-reveal]') && !el.closest('.stage') &&
                !el.closest('.home-page') && !el.closest('.suggest');
        });

        list.forEach(function (el) {
            el.setAttribute('data-mo', '');
            if (quiet || !io) { return; }          // hasil live-search: tampil langsung
            el.classList.add('mo');
            io.observe(el);
        });
    }

    function start() {
        root.classList.add('js');
        prepare();

        // Konten yang diganti lewat live-search / polling ikut diproses
        if (main && 'MutationObserver' in window) {
            new MutationObserver(function () { prepare(); })
                .observe(main, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    /* ---------- 2. Transisi pindah halaman (cadangan untuk browser tanpa View Transitions) ---------- */
    var nativeVT = window.CSSViewTransitionRule !== undefined;
    if (nativeVT) { return; }

    function leaving(a, e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return false; }
        if (a.target && a.target !== '_self') { return false; }
        if (a.hasAttribute('download') || a.origin !== location.origin) { return false; }
        if (a.pathname === location.pathname && a.search === location.search) { return false; }
        return true;
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || !leaving(a, e)) { return; }
        e.preventDefault();
        root.classList.add('is-leaving');
        setTimeout(function () { location.href = a.href; }, 220);
    });

    // Kembali lewat tombol Back (bfcache): pastikan halaman tidak tetap tersembunyi
    window.addEventListener('pageshow', function () { root.classList.remove('is-leaving'); });
})();
