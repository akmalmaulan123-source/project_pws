/* Perilaku kerangka halaman (dipindah dari skrip inline di layout):
   1) animasi masuk footer saat terlihat, 2) header diberi bayangan setelah halaman di-scroll. */
(function () {
    'use strict';

    // Footer: animasi masuk dimulai saat footer terlihat (tanpa animasi bila reduce motion)
    var f = document.querySelector('.foot');
    if (f) {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || !('IntersectionObserver' in window)) {
            f.classList.add('is-in');
        } else {
            var io = new IntersectionObserver(function (es) {
                if (es[0].isIntersecting) { f.classList.add('is-in'); io.disconnect(); }
            }, { threshold: 0.2 });
            io.observe(f);
        }
    }

    // Header: kelas is-scrolled setelah scroll lebih dari 12px
    var h = document.getElementById('site-top');
    if (h) {
        var on = false;
        var check = function () {
            var s = (window.pageYOffset || document.documentElement.scrollTop) > 12;
            if (s !== on) { on = s; h.classList.toggle('is-scrolled', s); }
        };
        check();
        window.addEventListener('scroll', check, { passive: true });
    }
})();
