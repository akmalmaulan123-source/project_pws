/* Halaman About: tiap bagian muncul saat di-scroll (judul, paragraf, kartu, daftar istilah, FAQ, chip), angka katalog
   berhitung naik. Elemen yang masuk layar bersamaan muncul bergiliran. Tanpa JS atau dengan "reduce motion" semuanya
   langsung tampil. Gerakannya ada di about.css; skrip ini hanya memasang .is-in saat elemennya terlihat.
   Daftar penanda di bawah harus sama dengan daftar di about.css (bagian "Animasi masuk"). */
(function () {
    'use strict';

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;
    var PARTS = [
        '.ab-head > *', '.ab-stats > div', '.ab-sec h2', '.ab-sec > p', '.ab-card',
        '.ab-gloss > dt', '.ab-gloss > dd', '.ab-faq details', '.ab-chips li'
    ];
    var io = null;

    function countUp(el) {
        var dd = el.querySelector('dd');
        if (!dd || reduce) { return; }
        var final = dd.textContent;
        var to = parseInt(final.replace(/[^0-9]/g, ''), 10);
        if (!to) { return; }
        var t0 = performance.now(), dur = 1100;
        (function step(t) {
            var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
            dd.textContent = p < 1 ? Math.round(to * e).toLocaleString('en-US') : final;
            if (p < 1) { requestAnimationFrame(step); }
        })(t0);
    }

    function show(el) {
        el.classList.add('is-in');
        if (el.matches('.ab-stats > div')) { countUp(el); }
    }

    function init() {
        var scope = document.querySelector('.about');
        if (!scope) { return; }
        var els = [].slice.call(scope.querySelectorAll(PARTS.join(','))).filter(function (el) {
            if (el.hasAttribute('data-ab')) { return false; }   // init() bisa dipanggil dua kali; yang sudah diproses dilewati
            el.setAttribute('data-ab', '');
            return true;
        });

        if (reduce || !hasIO) { els.forEach(show); return; }

        if (!io) {
            io = new IntersectionObserver(function (entries) {
                var shown = entries.filter(function (e) { return e.isIntersecting; })
                    .map(function (e) { return e.target; })
                    .sort(function (a, b) {
                        var ra = a.getBoundingClientRect(), rb = b.getBoundingClientRect();
                        return (ra.top - rb.top) || (ra.left - rb.left);
                    });

                // Jeda bertingkat hanya antar elemen yang muncul bersamaan (maks. 6 langkah, 70ms).
                // Istilah (dt) dan artinya (dd) satu baris, jadi memakai langkah yang sama.
                var step = -1, prev = null;
                shown.forEach(function (el) {
                    var sameRow = prev && el.matches('dd') && prev.matches('dt') && prev.nextElementSibling === el;
                    if (!sameRow) { step++; }
                    el.style.setProperty('--ab-d', Math.min(step, 6) * 70 + 'ms');
                    show(el);
                    io.unobserve(el);
                    prev = el;
                });
            }, { threshold: 0, rootMargin: '0px 0px 60px 0px' });
        }

        els.forEach(function (el) { io.observe(el); });
    }

    // Skrip dipasang di akhir konten, jadi semua elemen di atasnya sudah ada.
    init();
    document.addEventListener('DOMContentLoaded', init);
})();
