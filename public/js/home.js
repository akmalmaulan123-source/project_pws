/* Interaksi halaman utama: muncul saat di-scroll, angka berhitung naik, carousel. */
(function () {
    'use strict';

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;

    /* 1. Elemen muncul halus saat masuk layar */
    var items = [].slice.call(document.querySelectorAll('[data-reveal]'));
    if (!hasIO || reduce) {
        items.forEach(function (el) { el.classList.add('is-in'); });
    } else {
        var revealIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('is-in'); revealIO.unobserve(e.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        items.forEach(function (el) { revealIO.observe(el); });
    }

    /* 2. Angka statistik berhitung naik dari 0 */
    function countUp(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (!target || reduce) { return; }
        var start = null, dur = 1400;
        function step(t) {
            if (start === null) { start = t; }
            var p = Math.min((t - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('en-US');
            if (p < 1) { window.requestAnimationFrame(step); }
        }
        el.textContent = '0';
        window.requestAnimationFrame(step);
    }
    var counters = [].slice.call(document.querySelectorAll('[data-count]'));
    if (hasIO && !reduce) {
        var countIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { countUp(e.target); countIO.unobserve(e.target); }
            });
        }, { threshold: 0.6 });
        counters.forEach(function (el) { countIO.observe(el); });
    }

    /* 3. Carousel model terbaru: tombol geser + status tombol */
    function initCarousels() {
    [].forEach.call(document.querySelectorAll('[data-carousel]'), function (box) {
        var track = box.querySelector('.car-cards');
        var prev = box.querySelector('.car-nav-prev');
        var next = box.querySelector('.car-nav-next');
        if (!track || !prev || !next) { return; }

        function update() {
            prev.disabled = track.scrollLeft <= 4;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        }
        function go(dir) {
            track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior: reduce ? 'auto' : 'smooth' });
        }
        prev.addEventListener('click', function () { go(-1); });
        next.addEventListener('click', function () { go(1); });
        track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
        track.addEventListener('transitionend', function (e) { if (e.propertyName === 'flex-basis') { update(); } });
        window.addEventListener('resize', update);
        update();
    });
    }
    initCarousels();
    document.addEventListener('live:swapped', initCarousels); // konten diganti oleh live update
})();
