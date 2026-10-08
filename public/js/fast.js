/* Navigasi lebih responsif: halaman tujuan diambil lebih dulu saat kursor mendekat atau jari menyentuh link.
   Chrome/Edge memakai Speculation Rules (di layout); ini cadangan untuk browser lain. */
(function () {
    'use strict';

    var conn = navigator.connection || {};
    if (conn.saveData || /(^|-)2g$/.test(conn.effectiveType || '')) { return; }

    var seen = {};
    var timer = null;

    function internal(a) {
        if (!a || !a.href || a.origin !== location.origin) { return false; }
        if (a.target && a.target !== '_self') { return false; }
        if (a.hasAttribute('download') || a.hash && a.pathname === location.pathname) { return false; }
        if (a.pathname === location.pathname && a.search === location.search) { return false; }
        if (/\/(live|__dev-reload|search)(\/|$)/.test(a.pathname)) { return false; }
        return true;
    }

    function prefetch(url) {
        if (seen[url]) { return; }
        seen[url] = true;
        var l = document.createElement('link');
        l.rel = 'prefetch';
        l.href = url;
        l.as = 'document';
        document.head.appendChild(l);
    }

    function target(e) {
        return e.target && e.target.closest ? e.target.closest('a[href]') : null;
    }

    document.addEventListener('mouseover', function (e) {
        var a = target(e);
        if (!internal(a)) { return; }
        clearTimeout(timer);
        timer = setTimeout(function () { prefetch(a.href); }, 65);   // hindari mengambil link yang hanya dilewati
    }, { passive: true });

    document.addEventListener('mouseout', function () { clearTimeout(timer); }, { passive: true });

    document.addEventListener('touchstart', function (e) {
        var a = target(e);
        if (internal(a)) { prefetch(a.href); }
    }, { passive: true });

    /* Tandai "sedang pindah halaman" supaya polling latar belakang (live.js, devreload.js) berhenti sebentar
       dan tidak mengantre di server saat link diklik. */
    function leave() { window.__pfNavigating = true; }
    document.addEventListener('click', function (e) {
        var a = target(e);
        if (!a || !internal(a) || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        // Cek setelah semua handler jalan: link yang ditangani live.js (filter, urutan, halaman) tidak pindah halaman.
        setTimeout(function () { if (!e.defaultPrevented) { leave(); } }, 0);
    }, true);
    window.addEventListener('beforeunload', leave);
    window.addEventListener('pageshow', function () { window.__pfNavigating = false; });
})();
