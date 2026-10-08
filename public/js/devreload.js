/* Auto-refresh saat development (hanya dimuat kalau APP_ENV=local).
   Tiap file di app/, routes/, config/, resources/views/, public/js, atau public/css disimpan,
   browser langsung ikut: perubahan PHP/Blade/JS memuat ulang halaman, perubahan CSS cukup
   mengganti stylesheet (tanpa reload, posisi scroll dan isi form tetap). */
(function () {
    'use strict';

    var tag = document.currentScript;
    var url = tag && tag.getAttribute('data-url');
    var every = (tag && parseInt(tag.getAttribute('data-interval'), 10)) || 2500;
    if (!url || !window.fetch) { return; }

    var last = null;
    var busy = false;

    function swapStyles() {
        [].forEach.call(document.querySelectorAll('link[rel="stylesheet"]'), function (old) {
            var href = old.getAttribute('href') || '';
            var a = document.createElement('a');
            a.href = href;
            if (a.origin !== location.origin || a.pathname.indexOf('/css/') === -1) { return; }

            var fresh = old.cloneNode();
            fresh.setAttribute('href', href.replace(/([?&])_r=\d+&?/, '$1').replace(/[?&]$/, '') + (href.indexOf('?') === -1 ? '?' : '&') + '_r=' + Date.now());
            // Ganti setelah stylesheet baru siap, supaya tidak ada kedipan tanpa gaya
            fresh.addEventListener('load', function () { if (old.parentNode) { old.parentNode.removeChild(old); } });
            fresh.addEventListener('error', function () { if (fresh.parentNode) { fresh.parentNode.removeChild(fresh); } });
            old.parentNode.insertBefore(fresh, old.nextSibling);
        });
    }

    function check() {
        if (busy || document.hidden || window.__pfNavigating) { return; }
        busy = true;

        fetch(url + '?t=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) { throw new Error('http ' + res.status); }
                return res.json();
            })
            .then(function (now) {
                if (last === null) { last = now; return; }
                if (now.code !== last.code) { last = now; window.location.reload(); return; }
                if (now.css !== last.css) { last = now; swapStyles(); }
            })
            .catch(function () { /* server sedang restart atau error sesaat: coba lagi di putaran berikutnya */ })
            .then(function () { busy = false; });
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) { check(); } });
    window.setInterval(check, every);
    check();
})();
