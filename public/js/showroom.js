/* Halaman Cars (showroom).
   - Arahkan kursor (atau ketuk di layar sentuh) ke kartu mobil: panel sorotan menampilkan spesifikasinya, dengan bar bersegmen, kelas performa, dan harga.
   - Strip brand, pilihan bahan bakar, dan slider (tenaga, budget) mengisi kolom tersembunyi di form lalu memicu live-search (live.js).
   Memakai delegasi event di document, jadi tetap bekerja setelah live.js mengganti isi halaman. */
(function () {
    'use strict';

    var CHECK = '<svg viewBox="0 0 14 11" width="14" height="11" aria-hidden="true"><path d="M1.5 5.5 5 9l7.5-7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    var hpNow = 0;
    var tweenId = 0;

    function $(root, key) { return root.querySelector('[data-sr="' + key + '"]'); }
    function fmt(n) { return Number(n).toLocaleString('en-US'); }
    function reduced() { return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); }

    /* ---------- Panel sorotan ---------- */
    function countTo(el, to) {
        var from = hpNow, id = ++tweenId, t0 = performance.now();
        hpNow = to;
        if (reduced()) { el.textContent = to ? fmt(to) : '–'; return; }
        (function step(t) {
            if (id !== tweenId) { return; }
            var p = Math.min(1, (t - t0) / 700), e = 1 - Math.pow(1 - p, 3);
            var v = Math.round(from + (to - from) * e);
            el.textContent = v || to ? fmt(v) : '–';
            if (p < 1) { requestAnimationFrame(step); }
        })(t0);
    }

    function show(tile) {
        var spot = document.querySelector('[data-showroom] .sr-spot');
        if (!spot || !tile) { return; }
        var d = tile.dataset;

        document.querySelectorAll('.sr-tile.is-on').forEach(function (t) { t.classList.remove('is-on'); });
        tile.classList.add('is-on');

        // Logo brand
        var logo = spot.querySelector('.brand-logo');
        if (logo) {
            var img = logo.querySelector('img');
            logo.style.setProperty('--h', d.hue);
            logo.classList.remove('is-broken');
            if (d.logo) {
                if (!img) {
                    img = document.createElement('img');
                    img.alt = '';
                    img.onerror = function () { logo.classList.add('is-broken'); };
                    logo.insertBefore(img, logo.firstChild);
                }
                if (img.getAttribute('src') !== d.logo) { img.src = d.logo; }
            } else {
                logo.classList.add('is-broken');
            }
            var fb = logo.querySelector('.logo-fallback');
            if (fb) { fb.textContent = d.code; }
        }

        $(spot, 'brand').textContent = d.brand + (d.country ? ', ' + d.country : '');
        $(spot, 'rank').textContent = d.rank ? 'Power rank #' + d.rank : '';
        var name = $(spot, 'name');
        if (name.textContent !== d.name) {
            name.textContent = d.name;
            name.classList.remove('is-pop');
            void name.offsetWidth;
            name.classList.add('is-pop');
        }
        var eng = parseInt(d.eng, 10) || 0;
        $(spot, 'years').textContent = d.years + ' · ' + eng + (eng === 1 ? ' engine' : ' engines') + ' on record';

        var hp = parseInt(d.hp, 10) || 0;
        countTo($(spot, 'hp'), hp);

        var price = parseInt(d.price, 10) || 0;
        var pe = $(spot, 'price');
        pe.textContent = price ? '$' + fmt(price) : 'Not listed';
        pe.classList.toggle('na', !price);
        $(spot, 'idr').textContent = price ? (d.idr || '') : '';

        // Bar bersegmen: lebar dibulatkan ke kelipatan 10% (10 segmen)
        function seg(p) { return Math.max(10, Math.min(100, Math.round(p / 10) * 10)) + '%'; }
        $(spot, 'hpBar').style.width = hp ? seg(hp / 10) : '0';

        var z = parseFloat(d.zero), top = parseInt(d.top, 10);
        $(spot, 'zero').innerHTML = z ? z.toFixed(1) + '<small>s</small>' : '–';
        $(spot, 'zeroBar').style.width = z ? seg((1 - (z - 2) / 6) * 100) : '0';
        $(spot, 'top').innerHTML = top ? fmt(top) + '<small>km/h</small>' : '–';
        $(spot, 'topBar').style.width = top ? seg(top / 4) : '0';
        $(spot, 'eng').textContent = eng || '–';

        var box = $(spot, 'fuels');
        box.textContent = '';
        (d.fuels ? d.fuels.split(',') : []).forEach(function (g) {
            var s = document.createElement('span');
            s.className = 'sr-fl';
            s.setAttribute('data-g', g);
            s.textContent = g;
            box.appendChild(s);
        });
        $(spot, 'go').href = tile.href;
    }

    function init() {
        var first = document.querySelector('[data-showroom] .sr-tile');
        if (!first) { return; }
        hpNow = 0;
        show(first);          // isi awal + speedometer naik dari nol
        syncFilters();
    }

    /* ---------- Filter: strip brand, bahan bakar, slider tenaga dan budget ---------- */
    function hidden(name) { return document.querySelector('form.sr-form input[type="hidden"][name="' + name + '"]'); }

    // Samakan tampilan (chip, slider) dengan nilai kolom tersembunyi, mis. setelah Back/Forward atau tombol Reset.
    function syncFilters() {
        [].forEach.call(document.querySelectorAll('form.sr-form [data-sr-group]'), function (group) {
            var input = hidden(group.getAttribute('data-sr-group'));
            var val = input ? input.value : '';
            [].forEach.call(group.querySelectorAll('[data-sr-pick]'), function (b) {
                b.classList.toggle('is-on', b.getAttribute('data-value') === val);
            });
        });
        // Dropdown Country dan Sort (komponen yang sama dengan halaman Brands): teks, penanda pilihan, dan centang.
        [].forEach.call(document.querySelectorAll('form.sr-form .field'), function (field) {
            var input = field.querySelector('input[type="hidden"]');
            var menu = field.querySelector('.sort-menu.dd');
            if (!input || !menu) { return; }
            [].forEach.call(menu.querySelectorAll('a[data-dd-value]'), function (a) {
                var on = a.getAttribute('data-dd-value') === input.value;
                var svg = a.querySelector('svg');
                a.classList.toggle('is-on', on);
                if (on) {
                    menu.querySelector('.sort-value').textContent = a.querySelector('span').textContent;
                    if (!svg) { a.insertAdjacentHTML('beforeend', CHECK); }
                } else if (svg) {
                    svg.remove();
                }
            });
        });

        // Slider (tenaga, budget): posisi = tahap tertinggi yang tidak melebihi nilai (nilai bebas dari URL tetap ditampilkan apa adanya).
        [].forEach.call(document.querySelectorAll('form.sr-form [data-sr-range]'), function (range) {
            var input = hidden(range.getAttribute('data-field'));
            if (!input) { return; }
            var now = parseInt(input.value, 10) || 0;
            var st = stops(range);
            var idx = (now <= 0 && range.getAttribute('data-any') === 'last') ? st.length - 1 : 0;
            if (now > 0) { st.forEach(function (s, i) { if (s > 0 && s <= now) { idx = i; } }); }
            range.value = idx;
            var label = range.parentNode.querySelector('[data-sr-pv]');
            if (label) { label.textContent = now > 0 ? rangeText(range, now) : 'Any'; }
        });
    }

    // Teks nilai slider, mis. "≥ 500 hp" atau "≤ $250,000".
    function rangeText(range, value) {
        return (range.getAttribute('data-prefix') || '') + fmt(value) + (range.getAttribute('data-suffix') || '');
    }

    // Tahap nilai slider (tidak linear), dikirim server lewat data-stops.
    function stops(range) {
        try { return JSON.parse(range.getAttribute('data-stops')) || []; } catch (e) { return []; }
    }

    function fire(input) { input.dispatchEvent(new Event('input', { bubbles: true })); }

    document.addEventListener('click', function (e) {
        var pick = e.target.closest ? e.target.closest('[data-sr-pick]') : null;
        if (!pick) { return; }
        var group = pick.closest('[data-sr-group]');
        var input = group && hidden(group.getAttribute('data-sr-group'));
        if (!input) { return; }
        input.value = pick.getAttribute('data-value');
        syncFilters();
        fire(input);
    });

    // Kolom tersembunyi berubah (dropdown, Back/Forward): samakan tampilan.
    document.addEventListener('input', function (e) {
        var t = e.target;
        if (t.type === 'hidden' && t.form && t.form.classList.contains('sr-form')) { syncFilters(); }
    });

    document.addEventListener('input', function (e) {
        var range = e.target;
        if (!range.matches || !range.matches('[data-sr-range]')) { return; }
        var field = hidden(range.getAttribute('data-field'));
        if (!field) { return; }
        var value = stops(range)[parseInt(range.value, 10)] || 0;
        field.value = value > 0 ? String(value) : '';   // 0 = tanpa filter
        syncFilters();
        fire(field);
    });

    /* ---------- Sorotan: kursor atau ketukan ---------- */
    var touch = window.matchMedia ? window.matchMedia('(hover: none)') : { matches: false };

    document.addEventListener('mouseover', function (e) {
        var tile = e.target.closest ? e.target.closest('[data-showroom] .sr-tile') : null;
        if (tile && !tile.classList.contains('is-on')) { show(tile); }
    });

    // Layar sentuh: ketukan pertama menampilkan spesifikasi, ketukan kedua (atau tombol Enter garage) membuka garage.
    document.addEventListener('click', function (e) {
        var tile = e.target.closest ? e.target.closest('[data-showroom] .sr-tile') : null;
        if (!tile || !touch.matches || tile.classList.contains('is-on')) { return; }
        e.preventDefault();
        show(tile);
    });

    document.addEventListener('live:swapped', init);
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
