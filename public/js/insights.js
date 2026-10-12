/* Animasi halaman Insights: bagian muncul saat masuk layar, busur dial terisi, batang tumbuh, angka berhitung naik.
   Tanpa JS, dengan "reduce motion", atau saat konten hanya diganti live update, semua langsung tampil utuh.
   Keadaan awal (tersembunyi) hanya dipasang lewat class "ins-armed" di <html>, jadi kalau skrip gagal, konten tetap terlihat. */
(function () {
    'use strict';

    var root = document.documentElement;
    var page = document.querySelector('.ins');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!page || reduce || root.classList.contains('live-quiet') || !('IntersectionObserver' in window)) { return; }

    function format(value, dec, suffix) {
        return value.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suffix;
    }

    var counters = [].slice.call(page.querySelectorAll('[data-count]'));
    counters.forEach(function (el) {
        el.setAttribute('data-final', el.textContent);
        el.textContent = format(0, parseInt(el.getAttribute('data-dec'), 10) || 0, el.getAttribute('data-suffix') || '');
    });

    function countUp(el) {
        var target = parseFloat(el.getAttribute('data-count'));
        var dec = parseInt(el.getAttribute('data-dec'), 10) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        var delay = parseInt(el.getAttribute('data-delay'), 10) || 0;
        var dur = parseInt(el.getAttribute('data-dur'), 10) || 1300;
        var final = el.getAttribute('data-final');
        if (isNaN(target)) { return; }

        setTimeout(function () {
            var start = null;
            function step(t) {
                if (start === null) { start = t; }
                var p = Math.min((t - start) / dur, 1);
                var eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p); // melambat mulus di ujung
                el.textContent = format(target * eased, dec, suffix);
                if (p < 1) { requestAnimationFrame(step); } else if (final) { el.textContent = final; }
            }
            requestAnimationFrame(step);
        }, delay);
    }

    root.classList.add('ins-armed');

    // Konten yang diganti live update tampil langsung, tanpa animasi ulang.
    document.addEventListener('live:swapped', function () { root.classList.remove('ins-armed'); });

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) { return; }
            var section = e.target;
            io.unobserve(section);
            section.classList.add('ins-go');
            [].forEach.call(section.querySelectorAll('[data-count]'), countUp);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    [].forEach.call(page.querySelectorAll('[data-ins]'), function (s) { io.observe(s); });
})();
