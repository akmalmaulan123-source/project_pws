/* Kartu mobil -> halaman garasi: logo merek dan nama mobil di kartu "terbang" ke posisinya di garasi
   (View Transitions antar-halaman, Chrome/Edge 124+ dan Safari 18.2+). Browser lain tetap memakai pudar biasa.
   - Halaman asal (pageswap): kartu yang diklik memberi nama transisi pada logo dan namanya.
   - Halaman garasi (pagereveal): logo dan nama di panggung memakai nama yang sama; pintu garasi dilewati
     karena gerakan "terbang" itu sudah menjadi pembuka. Pemuatan langsung/dari luar tetap memakai pintu garasi.
   Dimuat di layout biasa dan di <head> halaman garasi (harus siap sebelum halaman pertama digambar). */
(function () {
    'use strict';

    var KEY = 'gr-morph';
    var MAX_AGE = 4000;     // penanda hanya berlaku sebentar, supaya tidak salah sasaran
    var root = document.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var last = null;        // tautan terakhir yang diklik

    function dest(path) { return path.pathname + path.search; }
    function isGarage(u) { return /^\/brands\/[^/]+\/?$/.test(u.pathname) && u.searchParams.has('car'); }

    // Bagian kartu yang jadi "terbang": logo merek dan nama mobil (kartu .nc dan baris daftar .row).
    function parts(a) {
        return {
            logo: a.querySelector('.nc-badge, .brand-logo'),
            name: a.querySelector('.nc-title strong, .row-model')
        };
    }

    function setNames(p, on) {
        if (p.logo) { p.logo.style.viewTransitionName = on ? 'car-logo' : ''; }
        if (p.name) { p.name.style.viewTransitionName = on ? 'car-name' : ''; }
    }

    document.addEventListener('click', function (e) {
        last = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    }, true);

    // Halaman asal: tentukan kartu yang diklik sebelum halaman lama difoto
    window.addEventListener('pageswap', function (e) {
        if (reduce || !e.viewTransition || !e.activation || !e.activation.entry) { return; }

        var to = new URL(e.activation.entry.url);
        if (to.origin !== location.origin || !isGarage(to)) { return; }

        var a = last && last.isConnected && dest(last) === dest(to) ? last : null;
        if (!a) { return; }
        var p = parts(a);
        if (!p.logo || !p.name) { return; }

        setNames(p, true);
        try { sessionStorage.setItem(KEY, dest(to) + '|' + Date.now()); } catch (_) { /* diabaikan */ }
        e.viewTransition.finished.then(function () { setNames(p, false); }, function () { setNames(p, false); });
    });

    // Halaman garasi: logo dan nama di panggung menerima gerakan dari kartu
    window.addEventListener('pagereveal', function (e) {
        if (reduce || !e.viewTransition) { return; }

        var flag = null;
        try { flag = sessionStorage.getItem(KEY); sessionStorage.removeItem(KEY); } catch (_) { /* diabaikan */ }
        if (!flag) { return; }

        var bits = flag.split('|');
        if (Date.now() - parseInt(bits[1], 10) > MAX_AGE || bits[0] !== location.pathname + location.search) { return; }

        var logo = document.querySelector('.gr-logo');
        var name = document.querySelector('.gr-model');
        if (!logo || !name) { return; }

        root.classList.add('live-quiet', 'gr-morph');   // lewati pintu garasi dan animasi masuk logo
        logo.style.viewTransitionName = 'car-logo';
        name.style.viewTransitionName = 'car-name';

        var clean = function () {
            logo.style.viewTransitionName = '';
            name.style.viewTransitionName = '';
            root.classList.remove('gr-morph');
        };
        e.viewTransition.finished.then(clean, clean);
    });
})();
