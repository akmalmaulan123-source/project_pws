/* Live update.
   1) Pencarian, filter, urutan, dan pagination mengganti hasil tanpa memuat ulang halaman.
   2) Halaman memeriksa perubahan data secara berkala dan menyegarkan isinya sendiri. */
(function () {
    'use strict';

    var main = document.getElementById('main');
    var pill = document.getElementById('live-pill');
    if (!main || !pill || !window.fetch || !window.URLSearchParams || !window.DOMParser || !window.AbortController) { return; }

    var POLL_MS = parseInt(pill.getAttribute('data-interval'), 10) || 8000;
    var DEBOUNCE_MS = 350;
    var STORE_ON = 'pf-live';
    var STORE_COMPARE = 'pf-compare';

    var controller = null;   // permintaan halaman yang sedang berjalan
    var typingTimer = null;  // jeda ketikan di kolom filter
    var pollTimer = null;
    var toastTimer = null;
    var toastEl = null;
    var lastToken = null;
    var enabled = true;
    try { enabled = window.localStorage.getItem(STORE_ON) !== 'off'; } catch (e) { /* abaikan */ }

    function current() { return location.pathname + location.search; }

    // Halaman statis (About) dan Compare (URL + localStorage jadi sumbernya) tidak perlu disegarkan otomatis.
    function isStatic() { return !!document.querySelector('.prose-page, [data-compare-col]'); }

    function buildUrl(form) {
        var params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (typeof value === 'string' && value.trim() !== '') { params.append(key, value.trim()); }
        });
        var query = params.toString();
        return new URL(form.action, location.href).pathname + (query ? '?' + query : '');
    }

    function copyFields(fromForm, toForm) {
        [].forEach.call(toForm.elements, function (el) {
            var src = el.name ? fromForm.elements[el.name] : null;
            if (src && 'value' in src) { el.value = src.value; }
        });
    }

    function restoreCompare() {
        var ids = [];
        try {
            ids = (JSON.parse(window.localStorage.getItem(STORE_COMPARE) || '[]') || []).map(function (x) { return String(x.id); });
        } catch (e) { /* abaikan */ }
        [].forEach.call(main.querySelectorAll('.js-compare'), function (cb) {
            cb.checked = ids.indexOf(cb.getAttribute('data-id')) !== -1;
        });
    }

    function toast(text) {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'live-toast';
            toastEl.setAttribute('role', 'status');
            toastEl.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = text;
        toastEl.classList.add('is-on');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
    }

    function setBusy(on, silent) {
        main.classList.toggle('is-loading', on && !silent);
        document.documentElement.classList.toggle('live-busy', on && !silent);
    }

    /* Ganti isi <main> dengan versi baru. Form filter tidak disentuh supaya fokus dan kursor tetap di tempatnya. */
    function swap(doc, syncFields) {
        var fresh = doc.getElementById('main');
        var openState = [].map.call(main.querySelectorAll('details'), function (d) { return d.open; });

        [].forEach.call(fresh.querySelectorAll('[data-reveal]'), function (el) { el.classList.add('is-in'); });

        var oldForm = main.querySelector('form.filters');
        var newForm = fresh.querySelector('form.filters');

        if (oldForm && newForm && oldForm.parentNode && newForm.parentNode) {
            var oldWrap = oldForm.parentNode;
            var newWrap = newForm.parentNode;
            var oldActions = oldForm.querySelector('.filter-actions');
            var newActions = newForm.querySelector('.filter-actions');
            if (oldActions && newActions) { oldActions.innerHTML = newActions.innerHTML; }
            if (syncFields) { copyFields(newForm, oldForm); }

            while (oldWrap.firstChild !== oldForm) { oldWrap.removeChild(oldWrap.firstChild); }
            while (oldForm.nextSibling) { oldWrap.removeChild(oldForm.nextSibling); }
            while (newWrap.firstChild && newWrap.firstChild !== newForm) { oldWrap.insertBefore(newWrap.firstChild, oldForm); }
            var rest = newForm.nextSibling;
            while (rest) { var next = rest.nextSibling; oldWrap.appendChild(rest); rest = next; }
        } else {
            while (main.firstChild) { main.removeChild(main.firstChild); }
            while (fresh.firstChild) { main.appendChild(fresh.firstChild); }
        }

        [].forEach.call(main.querySelectorAll('details'), function (d, i) {
            if (openState[i] !== undefined) { d.open = openState[i]; }
        });

        if (doc.title) { document.title = doc.title; }
        document.documentElement.classList.add('live-quiet'); // animasi masuk hanya untuk pemuatan pertama
        restoreCompare();
        document.dispatchEvent(new CustomEvent('live:swapped'));
    }

    function scrollToResults() {
        var top = main.getBoundingClientRect().top + window.pageYOffset - 72;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }

    function load(url, opts) {
        opts = opts || {};
        if (controller) { controller.abort(); }
        var ctl = controller = new AbortController();
        setBusy(true, opts.silent);

        return fetch(url, {
            headers: { 'X-Live': '1', 'Accept': 'text/html' },
            credentials: 'same-origin', cache: 'no-store', signal: ctl.signal
        })
            .then(function (res) {
                if (!res.ok) { throw new Error('http ' + res.status); }
                return res.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                if (!doc.getElementById('main')) { throw new Error('no main'); }
                swap(doc, opts.sync);
                if (opts.push) { history.pushState({ live: 1 }, '', url); }
                if (opts.scroll) { scrollToResults(); }
                if (opts.silent) { toast('Data diperbarui'); }
                return true;
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') { return false; }
                if (!opts.silent) { window.location.href = url; } // gagal: pakai navigasi biasa
                return false;
            })
            .then(function (ok) {
                if (ctl === controller) { controller = null; setBusy(false); }
                return ok;
            });
    }

    function go(url, opts) {
        if (url === current()) { return Promise.resolve(false); }
        return load(url, opts);
    }

    /* ---------- 1. Filter & pagination tanpa reload ---------- */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.matches('form.filters')) { return; }
        e.preventDefault();
        clearTimeout(typingTimer); typingTimer = null;
        go(buildUrl(form), { push: true });
    });

    document.addEventListener('input', function (e) {
        var el = e.target;
        var form = el.form;
        if (!form || !form.matches('form.filters') || el.tagName === 'SELECT') { return; }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () {
            typingTimer = null;
            go(buildUrl(form), { push: true });
        }, DEBOUNCE_MS);
    });

    document.addEventListener('change', function (e) {
        var el = e.target;
        var form = el.form;
        if (!form || !form.matches('form.filters') || el.tagName !== 'SELECT') { return; }
        go(buildUrl(form), { push: true });
    });

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var a = e.target.closest ? e.target.closest('.pager a, form.filters a.btn-quiet') : null;
        if (!a || a.target || a.origin !== location.origin) { return; }
        e.preventDefault();
        go(a.pathname + a.search, { push: true, sync: true, scroll: !!a.closest('.pager') });
    });

    // Tombol Back/Forward: muat ulang isi sesuai URL dan samakan isi kolom filter.
    window.addEventListener('popstate', function () { load(current(), { sync: true }); });

    /* ---------- 2. Segarkan otomatis saat data berubah ---------- */
    function setState(state) {
        var labels = { on: 'Live', paused: 'Dijeda', offline: 'Offline' };
        pill.setAttribute('data-state', state);
        pill.querySelector('.live-text').textContent = labels[state];
        pill.setAttribute('aria-pressed', state === 'paused' ? 'false' : 'true');
        pill.title = state === 'paused' ? 'Live update dijeda. Klik untuk mengaktifkan.' : 'Live update aktif. Klik untuk menjeda.';
    }

    function schedule() {
        clearTimeout(pollTimer);
        if (enabled) { pollTimer = setTimeout(poll, POLL_MS); }
    }

    function poll() {
        if (!enabled) { return; }
        if (document.hidden) { schedule(); return; }

        fetch(pill.getAttribute('data-url'), { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) { throw new Error('http ' + res.status); }
                return res.json();
            })
            .then(function (data) {
                setState('on');
                if (lastToken === null || isStatic()) { lastToken = data.catalog; return; }
                if (data.catalog === lastToken) { return; }
                // Sedang mengetik atau memuat: biarkan, perubahan terdeteksi lagi di pengecekan berikutnya.
                if (typingTimer !== null || controller) { return; }
                lastToken = data.catalog;
                load(current(), { silent: true });
            })
            .catch(function () { setState('offline'); })
            .then(schedule);
    }

    pill.addEventListener('click', function () {
        enabled = !enabled;
        try { window.localStorage.setItem(STORE_ON, enabled ? 'on' : 'off'); } catch (e) { /* abaikan */ }
        setState(enabled ? 'on' : 'paused');
        if (enabled) { poll(); } else { clearTimeout(pollTimer); }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && enabled) { clearTimeout(pollTimer); poll(); }
    });

    history.replaceState({ live: 1 }, '', current());
    setState(enabled ? 'on' : 'paused');
    if (enabled) { poll(); }
})();
