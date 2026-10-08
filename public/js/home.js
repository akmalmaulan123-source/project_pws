/* Interaksi halaman utama: muncul saat di-scroll, judul diketik, angka berhitung naik, carousel, kartu Newest models. */
(function () {
    'use strict';

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;

    /* 0. Judul bagian "diketik" (Newest models, Browse by ...): pecah teks jadi huruf supaya CSS bisa
          memunculkannya satu per satu dengan kursor yang mengikuti ketikan. Dilewati kalau reduce motion atau saat
          konten hanya diganti live update (judul langsung tampil utuh). Teks asli tetap terbaca
          pembaca layar lewat aria-label. */
    function typeifyHeadings() {
        if (reduce || document.documentElement.classList.contains('live-quiet')) { return; }
        [].forEach.call(document.querySelectorAll('.home-page .block-head h2:not([data-tw])'), function (h) {
            var text = h.textContent.trim();
            if (!text) { return; }
            h.setAttribute('data-tw', '');
            h.setAttribute('aria-label', text);
            h.textContent = '';

            var n = 0;
            text.split(/\s+/).forEach(function (word, wi) {
                if (wi > 0) { h.appendChild(document.createTextNode(' ')); }
                var w = document.createElement('span');
                w.className = 'tw-w';
                w.setAttribute('aria-hidden', 'true');
                word.split('').forEach(function (ch) {
                    var c = document.createElement('span');
                    c.className = 'tw-c';
                    c.style.setProperty('--i', n++);
                    c.textContent = ch;
                    w.appendChild(c);
                });
                h.appendChild(w);
            });
        });
    }
    typeifyHeadings();

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

    /* 4. Kartu Newest models: muncul bertahap saat bagian ini di-scroll sampai terlihat.
          Animasinya ada di CSS (.showcase.is-in); di sini hanya penanda kapan mulai.
          Terpisah dari animasi hover (pelebaran kartu), yang tidak disentuh. */
    function initShowcase() {
        [].forEach.call(document.querySelectorAll('.showcase:not([data-sc-ready])'), function (list) {
            list.setAttribute('data-sc-ready', '');
            if (!hasIO || reduce) { list.classList.add('is-in'); return; }
            var io = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) {
                    list.classList.add('is-in');
                    io.disconnect();
                }
            }, { threshold: 0.25, rootMargin: '0px 0px -60px 0px' });
            io.observe(list);
        });
    }
    initShowcase();
    document.addEventListener('live:swapped', initShowcase);
})();
