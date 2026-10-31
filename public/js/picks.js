/* Halaman Top picks: judul bagian dan kartu muncul saat di-scroll (kartu bergiliran, mengikuti gaya Newest models di beranda).
   Tanpa JS, dengan "reduce motion", atau saat konten hanya diganti live update, semuanya langsung tampil.
   Gerakannya ada di picks.css; skrip ini hanya memasang .is-in saat bagiannya terlihat. */
(function () {
    'use strict';

    var root = document.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;
    var io = null;

    function show(el) { el.classList.add('is-in'); }

    function init() {
        var targets = [].slice.call(document.querySelectorAll('.pk-sh:not([data-pk-in]), .pk-grid:not([data-pk-in])'));
        var instant = reduce || !hasIO || root.classList.contains('live-quiet');

        if (!io && hasIO) {
            io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { show(e.target); io.unobserve(e.target); }
                });
            }, { threshold: 0, rootMargin: '0px 0px 80px 0px' });
        }

        targets.forEach(function (el) {
            el.setAttribute('data-pk-in', '');
            // Urutan kartu dipakai CSS untuk jeda bertingkat
            [].forEach.call(el.children, function (card, i) { card.style.setProperty('--i', i); });
            if (instant) { show(el); } else { io.observe(el); }
        });
    }

    // Skrip dipasang di akhir konten, jadi kartu di atasnya sudah ada: langsung jalan tanpa menunggu DOMContentLoaded.
    // init() aman dipanggil berulang (elemen yang sudah diproses dilewati).
    document.addEventListener('live:swapped', init);
    document.addEventListener('DOMContentLoaded', init);
    init();
})();
