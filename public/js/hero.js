/* Animasi hero beranda (gaya HUD balapan): speedometer digital naik sambil gigi pindah 1 sampai 7,
   LED rpm turun-naik di tiap pergantian gigi, angka Power berhitung naik, plus parallax halus
   mengikuti kursor. Dimuat tanpa defer, tepat setelah hero, supaya angka sudah 0 sebelum HUD muncul.
   Tanpa JS atau dengan "reduce motion", keadaan akhir (gigi 7, kecepatan penuh) langsung tampil. */
(function () {
    'use strict';

    var hero = document.querySelector('.show');
    if (!hero) { return; }

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { return; }

    function fmt(v, d) {
        return v.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    // easeOutCubic
    function ease(p) { return 1 - Math.pow(1 - p, 3); }

    /* ---------- 1. Angka Power berhitung naik ---------- */
    [].forEach.call(hero.querySelectorAll('[data-hero-num]'), function (el) {
        var target = parseFloat(el.getAttribute('data-hero-num'));
        var dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var delay = parseInt(el.getAttribute('data-delay') || '0', 10);
        var dur = 2000;
        if (!target || target <= 0) { return; }

        el.textContent = fmt(0, dec);
        var start = performance.now() + delay;

        function step(now) {
            var p = Math.min(Math.max((now - start) / dur, 0), 1);
            el.textContent = fmt(target * ease(p), dec);
            if (p < 1) {
                window.requestAnimationFrame(step);
            } else {
                el.textContent = fmt(target, dec);
            }
        }
        window.requestAnimationFrame(step);
    });

    /* ---------- 2. Speedometer + gigi + LED rpm ---------- */
    var speedEl = hero.querySelector('[data-hud-speed]');
    var gearBox = hero.querySelector('[data-hud-gear]');
    var gearNum = hero.querySelector('[data-hud-gear-num]');
    var leds = [].slice.call(hero.querySelectorAll('.hud-led i'));
    var topSpeed = speedEl ? parseFloat(speedEl.getAttribute('data-hud-speed')) : 0;

    if (speedEl && topSpeed > 0) {
        var dec = parseInt(speedEl.getAttribute('data-decimals') || '0', 10);
        // Batas pindah gigi sebagai pecahan kecepatan akhir (setara 0, 45, 85, 130, 175, 225, 265, 296 km/h)
        var G = [0, 45, 85, 130, 175, 225, 265, 296].map(function (v) { return v / 296; });
        var DUR = 4200;
        var t0 = performance.now() + 700;
        var lastGear = 0;

        var paint = function (value, gear, rpm) {
            speedEl.textContent = fmt(value, dec);
            if (gearNum) { gearNum.textContent = gear; }
            var on = Math.round(rpm * leds.length);
            leds.forEach(function (el, i) { el.classList.toggle('on', i < on); });
        };

        paint(0, 'N', 0);

        var tick = function (now) {
            var p = Math.min(Math.max((now - t0) / DUR, 0), 1);
            var frac = Math.pow(p, 0.8);
            var g = 1;
            for (var i = 1; i < 7; i++) { if (frac >= G[i]) { g = i + 1; } }
            var lo = G[g - 1], hi = G[g];
            var rpm = p < 1 ? 0.38 + 0.58 * ((frac - lo) / (hi - lo)) : 0.72;

            if (p > 0 && g !== lastGear) {
                if (lastGear && gearBox) {
                    gearBox.classList.remove('is-shift');
                    void gearBox.offsetWidth; // mulai ulang animasi kilat
                    gearBox.classList.add('is-shift');
                }
                lastGear = g;
            }

            if (p <= 0) { paint(0, 'N', 0); } else { paint(topSpeed * frac, g, rpm); }
            if (p < 1) { window.requestAnimationFrame(tick); }
        };
        window.requestAnimationFrame(tick);
    }

/* ---------- 3. Parallax halus: mobil dan cahaya latar (tulisan nama model sengaja diam) ---------- */
    var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var stage = hero.querySelector('.show-stage');
    if (!fine || !stage) { return; }

    var layers = [
        { el: stage.querySelector('.show-car'), x: -14, y: -7 },
        { el: stage.querySelector('.show-ambient'), x: -26, y: -12 }
    ].filter(function (l) { return l.el; });
    if (!layers.length) { return; }

    var tx = 0, ty = 0, cx = 0, cy = 0, raf = 0, armed = false;

    function frame() {
        cx += (tx - cx) * 0.08;
        cy += (ty - cy) * 0.08;
        layers.forEach(function (l) {
            // properti "translate" terpisah dari "transform", jadi tidak mengganggu animasi masuk
            l.el.style.translate = (cx * l.x).toFixed(2) + 'px ' + (cy * l.y).toFixed(2) + 'px';
        });
        if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) {
            raf = window.requestAnimationFrame(frame);
        } else {
            raf = 0;
        }
    }
    function kick() { if (!raf) { raf = window.requestAnimationFrame(frame); } }

    // Aktif setelah animasi masuk selesai, supaya tidak berebut perhatian
    window.setTimeout(function () { armed = true; }, 1500);

    hero.addEventListener('pointermove', function (e) {
        if (!armed) { return; }
        var r = hero.getBoundingClientRect();
        tx = ((e.clientX - r.left) / r.width - 0.5) * 2;
        ty = ((e.clientY - r.top) / r.height - 0.5) * 2;
        kick();
    });
    hero.addEventListener('pointerleave', function () { tx = 0; ty = 0; kick(); });
})();
