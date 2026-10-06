/* Pilihan bandingkan: disimpan di localStorage agar tetap ada saat berpindah halaman. */
(function () {
    'use strict';

    var KEY = 'pf-compare';
    var tray = document.getElementById('compare-tray');
    if (!tray) { return; }

    var url = tray.getAttribute('data-url');
    var max = parseInt(tray.getAttribute('data-max'), 10) || 4;
    var memory = [];
    var message = '';

    function load() {
        try {
            var v = JSON.parse(window.localStorage.getItem(KEY) || '[]');
            return Array.isArray(v) ? v.filter(function (x) { return x && x.id; }) : [];
        } catch (e) {
            return memory;
        }
    }

    function save(list) {
        memory = list;
        try { window.localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* penyimpanan tidak tersedia */ }
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function render() {
        var list = load();
        var ids = list.map(function (x) { return String(x.id); });

        Array.prototype.forEach.call(document.querySelectorAll('.js-compare'), function (cb) {
            cb.checked = ids.indexOf(cb.getAttribute('data-id')) !== -1;
        });

        tray.textContent = '';
        if (!list.length) {
            tray.hidden = true;
            document.body.classList.remove('has-tray');
            fitPadding();
            return;
        }

        var inner = el('div', 'wrap tray-in');
        inner.appendChild(el('span', 'tray-title', 'Compare ' + list.length + ' of ' + max));

        var ul = el('ul', 'tray-list');
        list.forEach(function (item) {
            var li = el('li', 'tray-chip');
            li.appendChild(el('span', '', item.name || ('Engine ' + item.id)));
            var btn = el('button', '', '×');
            btn.type = 'button';
            btn.setAttribute('data-remove', item.id);
            btn.setAttribute('aria-label', 'Remove ' + (item.name || 'engine') + ' from comparison');
            li.appendChild(btn);
            ul.appendChild(li);
        });
        inner.appendChild(ul);

        if (message) { inner.appendChild(el('span', 'tray-msg', message)); }

        if (list.length >= 2) {
            var link = el('a', 'btn', 'Compare ' + list.length + ' engines');
            link.href = url + '?engines=' + ids.join(',');
            inner.appendChild(link);
        } else {
            inner.appendChild(el('span', 'tray-msg', 'Pick at least one more engine.'));
        }

        var clear = el('button', 'tray-clear', 'Clear');
        clear.type = 'button';
        clear.setAttribute('data-clear', '1');
        inner.appendChild(clear);

        tray.appendChild(inner);
        tray.hidden = false;
        document.body.classList.add('has-tray');
        fitPadding();
    }

    // Beri ruang di bawah halaman sebesar tinggi tray agar isi tidak tertutup.
    function fitPadding() {
        document.body.style.paddingBottom = tray.hidden ? '' : (tray.offsetHeight + 8) + 'px';
    }
    window.addEventListener('resize', fitPadding);

    // Di halaman bandingkan, URL adalah sumber kebenaran: samakan isi tray dengan kolom yang tampil.
    var cols = document.querySelectorAll('[data-compare-col]');
    if (cols.length) {
        save(Array.prototype.map.call(cols, function (c) {
            return { id: c.getAttribute('data-id'), name: c.getAttribute('data-name') };
        }));
    }

    document.addEventListener('change', function (e) {
        var cb = e.target;
        if (!cb.classList || !cb.classList.contains('js-compare')) { return; }
        var list = load();
        var id = cb.getAttribute('data-id');
        message = '';

        if (cb.checked) {
            if (list.length >= max) {
                cb.checked = false;
                message = 'You can compare up to ' + max + ' engines.';
            } else if (!list.some(function (x) { return String(x.id) === id; })) {
                list.push({ id: id, name: cb.getAttribute('data-name') });
            }
        } else {
            list = list.filter(function (x) { return String(x.id) !== id; });
        }
        save(list);
        render();
    });

    tray.addEventListener('click', function (e) {
        var t = e.target;
        if (t.getAttribute('data-remove')) {
            var id = t.getAttribute('data-remove');
            save(load().filter(function (x) { return String(x.id) !== String(id); }));
            message = '';
            render();
        } else if (t.getAttribute('data-clear')) {
            save([]);
            message = '';
            render();
        }
    });

    var clearLink = document.querySelector('.js-clear-compare');
    if (clearLink) { clearLink.addEventListener('click', function () { save([]); }); }

    render();
})();

/* Menu urutan: tutup saat klik di luar atau tekan Esc. Pilihan di kolom filter langsung diterapkan. */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        [].forEach.call(document.querySelectorAll('details[data-sort-menu][open]'), function (d) {
            if (!d.contains(e.target)) { d.removeAttribute('open'); }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        [].forEach.call(document.querySelectorAll('details[data-sort-menu][open]'), function (d) {
            d.removeAttribute('open');
            var s = d.querySelector('summary');
            if (s) { s.focus(); }
        });
    });

    // Pilihan dropdown filter: isi input tersembunyi lalu kirim form (isian lain ikut terkirim).
    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[data-dd-value]') : null;
        if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) { return; }
        var field = a.closest('.field');
        var input = field && field.querySelector('input[type="hidden"]');
        var form = input && input.form;
        if (!form) { return; }
        e.preventDefault();
        input.value = a.getAttribute('data-dd-value');
        form.submit();
    });

    // Tombol − / + pada kolom tenaga minimum.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.step-btn') : null;
        var box = btn && btn.closest('[data-stepper]');
        if (!box) { return; }
        var input = box.querySelector('input');
        var step = parseInt(box.getAttribute('data-step'), 10) || 1;
        var min = parseInt(box.getAttribute('data-min'), 10) || 0;
        var max = parseInt(box.getAttribute('data-max'), 10) || 100000;
        var cur = parseInt(input.value, 10);
        var next = Math.min(max, Math.max(min, (isNaN(cur) ? 0 : cur) + step * parseInt(btn.getAttribute('data-dir'), 10)));
        input.value = next === min ? '' : String(next); // nilai minimum = tanpa filter
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
})();
