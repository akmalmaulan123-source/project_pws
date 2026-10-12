/* Garage: klik model di deretan bawah mengganti isi garage tanpa memuat ulang halaman (URL ikut berubah jadi ?car=ID).
   - Halaman model sekitar diambil lebih awal (saat kursor mendekat dan setelah garage tampil), jadi pergantian nyaris instan.
   - Panel info dan generasi meluncur keluar-masuk searah perpindahan, bagian demi bagian; batang statistik bergeser
     dari nilai lama ke nilai baru; kartu terpilih langsung berpindah dan deretan menggulir halus.
   - Tanpa JS, link tetap bekerja sebagai navigasi biasa. */
(function () {
    'use strict';

    var main = document.getElementById('main');
    if (!main || !window.fetch || !window.DOMParser) { return; }

    var EASE = 'cubic-bezier(0.2, 0.8, 0.2, 1)';
    var OUT_MS = 170;         // panel lama meluncur keluar
    var IN_MS = 480;          // tiap bagian panel baru meluncur masuk (bergiliran)
    var STAGGER_MS = 45;
    var CACHE_MS = 60000;     // halaman yang sudah diambil dipakai ulang selama 1 menit
    var cache = {};
    var token = 0;            // hanya permintaan terakhir yang boleh mengganti isi
    var outAnims = [];

    function current() { return location.pathname + location.search; }
    function reduced() { return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); }
    function canAnimate() { return !reduced() && !!main.animate; }
    function wait(ms) { return new Promise(function (resolve) { setTimeout(resolve, ms); }); }
    function cards() { return [].slice.call(main.querySelectorAll('.gr-card')); }
    function path(a) { return a.pathname + a.search; }

    /* ---------- Ambil halaman (dengan cache, dan diambil lebih awal saat kursor mendekat) ---------- */
    function fetchPage(url) {
        var hit = cache[url];
        if (hit && Date.now() - hit.t < CACHE_MS) { return hit.p; }

        var p = fetch(url, { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) { throw new Error('http ' + res.status); }
                return res.text();
            });
        cache[url] = { t: Date.now(), p: p };
        p.catch(function () { delete cache[url]; });

        var keys = Object.keys(cache);
        if (keys.length > 40) { delete cache[keys[0]]; }
        return p;
    }

    function prefetch(url) {
        if (url !== current()) { fetchPage(url).catch(function () { /* diabaikan: nanti diambil saat diklik */ }); }
    }

    // Mobil di kiri/kanan yang terpilih kemungkinan besar dibuka berikutnya.
    function prefetchNeighbors() {
        var list = cards();
        var i = list.findIndex(function (a) { return a.classList.contains('is-on'); });
        [i + 1, i - 1, i + 2].forEach(function (n) { if (list[n]) { prefetch(path(list[n])); } });
    }

    /* ---------- Deretan model ---------- */
    // Gulir deretan model sehingga mobil yang dipilih ada di tengah (hanya di dalam deretan, halaman tidak ikut bergulir).
    function centerActive(smooth) {
        var rail = main.querySelector('.gr-rail');
        var on = rail && rail.querySelector('.is-on');
        if (!on) { return; }
        var left = on.offsetLeft - (rail.clientWidth - on.offsetWidth) / 2;
        if (smooth && rail.scrollTo && !reduced()) { rail.scrollTo({ left: left, behavior: 'smooth' }); } else { rail.scrollLeft = left; }
    }

    // Tandai kartu terpilih tanpa membuat ulang deretan.
    function setActive(url) {
        var found = false;
        cards().forEach(function (a) {
            var is = path(a) === url;
            found = found || is;
            a.classList.toggle('is-on', is);
            if (is) { a.setAttribute('aria-current', 'true'); } else { a.removeAttribute('aria-current'); }
        });
        return found;
    }

    /* ---------- Animasi ---------- */
    function panels() {
        return ['.gr-info', '.gr-side'].map(function (s) { return main.querySelector(s); }).filter(Boolean);
    }

    // Panel lama meluncur keluar, searah dengan arah pindah (mobil ke kanan = isi bergeser ke kiri).
    function slideOut(dir) {
        outAnims = [];
        if (!canAnimate()) { return wait(0); }
        panels().forEach(function (el) {
            outAnims.push(el.animate(
                [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateX(' + (-dir * 30) + 'px)' }],
                { duration: OUT_MS, easing: 'cubic-bezier(0.4, 0, 1, 1)', fill: 'forwards' }
            ));
        });
        return wait(OUT_MS);
    }

    // Isi baru masuk dari sisi sebaliknya, bagian demi bagian.
    function slideIn(dir) {
        if (canAnimate()) {
            panels().forEach(function (el) {
                [].slice.call(el.children).forEach(function (child, n) {
                    child.animate(
                        [{ opacity: 0, transform: 'translateX(' + (dir * 28) + 'px)' }, { opacity: 1, transform: 'none' }],
                        { duration: IN_MS, delay: Math.min(n, 7) * STAGGER_MS, easing: EASE, fill: 'backwards' }
                    );
                });
            });
            var logo = main.querySelector('.gr-logo');
            if (logo) {
                logo.animate([{ transform: 'scale(0.94)', opacity: 0.6 }, { transform: 'none', opacity: 1 }], { duration: 520, easing: EASE });
            }
        }
        // Setelah animasi masuk dibuat, lepas animasi keluar (panel kembali tampil; anak-anaknya sudah disembunyikan oleh fill backwards).
        outAnims.forEach(function (a) { a.cancel(); });
        outAnims = [];
    }

    // Pasang isi baru. false bila strukturnya tidak cocok (nanti pakai navigasi biasa).
    function apply(fresh, dir) {
        var q = function (root, sel) { return root.querySelector(sel); };
        var cur = { info: q(main, '.gr-info'), side: q(main, '.gr-side'), rail: q(main, '.gr-rail') };
        var nxt = { info: q(fresh, '.gr-info'), side: q(fresh, '.gr-side'), rail: q(fresh, '.gr-rail') };
        for (var k in cur) { if (!cur[k] || !nxt[k]) { return false; } }

        // Nilai batang lama, supaya batang bergeser ke nilai baru (bukan mulai dari nol).
        var old = [].map.call(cur.info.querySelectorAll('.gr-bar i'), function (i) { return i.style.getPropertyValue('--w'); });

        cur.info.innerHTML = nxt.info.innerHTML;
        cur.side.innerHTML = nxt.side.innerHTML;
        cur.info.scrollTop = 0;
        cur.side.scrollTop = 0;

        var bars = [].slice.call(cur.info.querySelectorAll('.gr-bar i'));
        var targets = bars.map(function (i) { return i.style.getPropertyValue('--w'); });
        bars.forEach(function (i, n) { if (old[n]) { i.style.setProperty('--w', old[n]); } });
        void cur.info.offsetWidth;   // paksa gambar ulang agar transisi berjalan

        var picked = nxt.rail.querySelector('.is-on');
        if (picked) { setActive(path(picked)); }

        slideIn(dir);
        requestAnimationFrame(function () {
            bars.forEach(function (i, n) { i.style.setProperty('--w', targets[n]); });
        });
        return true;
    }

    function load(url, push) {
        var seq = ++token;
        document.documentElement.classList.add('live-quiet');   // pintu garasi hanya untuk pemuatan pertama

        // Arah pindah: kartu di kanan yang terpilih = isi bergeser ke kiri, dan sebaliknya.
        var list = cards();
        var from = list.findIndex(function (a) { return a.classList.contains('is-on'); });
        var to = list.findIndex(function (a) { return path(a) === url; });
        var dir = to < 0 || from < 0 || to === from ? 1 : (to > from ? 1 : -1);

        // Kartu langsung berubah saat diklik (tidak menunggu server), deretan menggulir halus.
        if (setActive(url)) { centerActive(true); }

        // Bila server lambat, tampilkan garis aktivitas tipis di atas deretan.
        var busy = setTimeout(function () { main.classList.add('gr-busy'); }, 250);

        Promise.all([fetchPage(url), slideOut(dir)])
            .then(function (r) {
                if (seq !== token) { return; }   // sudah ada klik yang lebih baru
                var doc = new DOMParser().parseFromString(r[0], 'text/html');
                var fresh = doc.getElementById('main');
                if (!fresh || !apply(fresh, dir)) { throw new Error('bad page'); }
                if (doc.title) { document.title = doc.title; }
                if (push) { history.pushState({ gr: 1 }, '', url); }
                setTimeout(prefetchNeighbors, 350);
            })
            .catch(function () {
                if (seq === token) { window.location.href = url; }   // gagal: pakai navigasi biasa
            })
            .then(function () {
                clearTimeout(busy);
                if (seq === token) { main.classList.remove('gr-busy'); }
            });
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var a = e.target.closest ? e.target.closest('.gr-card') : null;
        if (!a || a.origin !== location.origin) { return; }
        e.preventDefault();
        if (path(a) !== current()) { load(path(a), true); }
    });

    // Ambil halaman lebih awal: kursor berhenti di kartu sebentar, atau jari/mouse mulai menekan.
    var hoverTimer = null;
    main.addEventListener('mouseover', function (e) {
        var a = e.target.closest ? e.target.closest('.gr-card') : null;
        clearTimeout(hoverTimer);
        if (a && !a.classList.contains('is-on')) { hoverTimer = setTimeout(function () { prefetch(path(a)); }, 60); }
    });
    main.addEventListener('mouseout', function () { clearTimeout(hoverTimer); });
    main.addEventListener('pointerdown', function (e) {
        var a = e.target.closest ? e.target.closest('.gr-card') : null;
        if (a && !a.classList.contains('is-on')) { prefetch(path(a)); }
    });

    /* Deretan model: roda mouse menggulir ke samping, dan bisa diseret dengan mouse (sentuh/trackpad sudah bawaan). */
    main.addEventListener('wheel', function (e) {
        var rail = e.target.closest ? e.target.closest('.gr-rail') : null;
        if (!rail || rail.scrollWidth <= rail.clientWidth || Math.abs(e.deltaY) <= Math.abs(e.deltaX)) { return; }
        e.preventDefault();
        rail.scrollLeft += e.deltaY;
    }, { passive: false });

    var drag = null;
    main.addEventListener('pointerdown', function (e) {
        var rail = e.pointerType === 'mouse' && e.button === 0 && e.target.closest ? e.target.closest('.gr-rail') : null;
        if (rail) { drag = { rail: rail, x: e.clientX, left: rail.scrollLeft, moved: false }; }
    });
    window.addEventListener('pointermove', function (e) {
        if (!drag) { return; }
        var dx = e.clientX - drag.x;
        if (!drag.moved && Math.abs(dx) > 5) { drag.moved = true; drag.rail.classList.add('is-drag'); }
        if (drag.moved) { drag.rail.scrollLeft = drag.left - dx; }
    });
    function endDrag() {
        if (drag && drag.moved) {
            var r = drag.rail;
            setTimeout(function () { r.classList.remove('is-drag'); }, 0);   // setelah klik "hantu" lewat, supaya seretan tidak membuka mobil
        }
        drag = null;
    }
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);

    /* Generasi: tertutup di awal; klik untuk membuka/menutup dengan animasi tinggi yang halus. */
    main.addEventListener('click', function (e) {
        var sum = e.target.closest ? e.target.closest('.gr-gen > summary') : null;
        if (!sum) { return; }
        e.preventDefault();

        var d = sum.parentNode;
        var body = d.querySelector('.gr-engs');
        var still = !body || !body.animate || reduced();
        var opening = !d.classList.contains('is-open');

        if (d._anim) { d._anim.cancel(); d._anim = null; }
        d.classList.toggle('is-open', opening);

        if (still) { d.open = opening; return; }

        body.style.overflow = 'hidden';
        var done = function () { body.style.overflow = ''; d._anim = null; };

        if (opening) {
            d.open = true;
            var h = body.offsetHeight;
            d._anim = body.animate(
                [{ height: '0px', paddingBottom: '0px', opacity: 0 }, { height: h + 'px', paddingBottom: '6px', opacity: 1 }],
                { duration: 300, easing: EASE }
            );
            d._anim.onfinish = done;
            d._anim.oncancel = done;
        } else {
            var from = body.offsetHeight;
            d._anim = body.animate(
                [{ height: from + 'px', paddingBottom: '6px', opacity: 1 }, { height: '0px', paddingBottom: '0px', opacity: 0 }],
                { duration: 240, easing: EASE }
            );
            d._anim.onfinish = function () { d.open = false; done(); };
            d._anim.oncancel = done;
        }
    });

    window.addEventListener('popstate', function () { load(current(), false); });

    history.replaceState({ gr: 1 }, '', current());
    centerActive(false);
    setTimeout(prefetchNeighbors, 1500);
})();
