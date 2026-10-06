/* Saran pencarian saat mengetik: merek dan mobil yang diawali (atau mengandung) huruf yang diketik.
   Navigasi keyboard: panah atas/bawah, Enter untuk membuka, Esc untuk menutup. */
(function () {
    'use strict';

    if (!window.fetch || !window.AbortController) { return; }

    var DEBOUNCE_MS = 120;
    var uid = 0;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    // Teks dengan bagian yang cocok ditebalkan (tanpa innerHTML).
    function highlight(parent, text, q) {
        var i = q ? text.toLowerCase().indexOf(q.toLowerCase()) : -1;
        if (i < 0) { parent.appendChild(document.createTextNode(text)); return; }
        parent.appendChild(document.createTextNode(text.slice(0, i)));
        parent.appendChild(el('mark', '', text.slice(i, i + q.length)));
        parent.appendChild(document.createTextNode(text.slice(i + q.length)));
    }

    function logo(item) {
        var box = el('span', 'sg-logo');
        box.style.setProperty('--h', item.hue);
        if (item.logo) {
            var img = document.createElement('img');
            img.src = item.logo; img.alt = ''; img.loading = 'lazy';
            img.onerror = function () { box.removeChild(img); box.appendChild(el('b', '', item.code)); };
            box.appendChild(img);
        } else {
            box.appendChild(el('b', '', item.code));
        }
        return box;
    }

    function attach(input) {
        var host = input.parentNode;
        var listId = 'sg-list-' + (++uid);
        var panel = el('div', 'suggest');
        var timer = null, ctl = null, active = -1, links = [], lastQ = null;

        panel.id = listId;
        panel.setAttribute('role', 'listbox');
        panel.hidden = true;
        host.classList.add('suggest-host');
        host.appendChild(panel);
        input.setAttribute('aria-controls', listId);

        function close() {
            panel.hidden = true;
            active = -1;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        }

        function setActive(i) {
            if (!links.length) { return; }
            active = (i + links.length) % links.length;
            links.forEach(function (a, n) {
                a.classList.toggle('is-active', n === active);
                a.setAttribute('aria-selected', n === active ? 'true' : 'false');
            });
            input.setAttribute('aria-activedescendant', links[active].id);
            links[active].scrollIntoView({ block: 'nearest' });
        }

        function section(title, items, q, big) {
            if (!items.length) { return; }
            panel.appendChild(el('p', 'sg-title', title));
            items.forEach(function (item) {
                var a = el('a', 'sg-item');
                a.href = item.url;
                a.id = listId + '-' + links.length;
                a.setAttribute('role', 'option');
                a.setAttribute('aria-selected', 'false');
                a.appendChild(logo(item));
                var text = el('span', 'sg-text');
                var name = el('span', 'sg-name');
                highlight(name, item.name, q);
                text.appendChild(name);
                if (item.meta) { text.appendChild(el('span', 'sg-meta', item.meta)); }
                a.appendChild(text);
                a.addEventListener('mouseenter', function () { setActive(links.indexOf(a)); });
                a.addEventListener('mousedown', function (e) { e.preventDefault(); }); // fokus tetap di kolom
                links.push(a);
                panel.appendChild(a);
            });
        }

        function render(data) {
            panel.textContent = '';
            links = [];
            active = -1;
            input.removeAttribute('aria-activedescendant');

            section('Brands', data.brands, data.q);
            section('Cars', data.cars, data.q);

            if (!data.brands.length && !data.cars.length) {
                panel.appendChild(el('p', 'sg-empty', 'No cars or brands match \u201C' + data.q + '\u201D.'));
            }

            var all = el('a', 'sg-all', 'See all results for \u201C' + data.q + '\u201D');
            all.href = data.all_url;
            all.id = listId + '-' + links.length;
            all.setAttribute('role', 'option');
            all.setAttribute('aria-selected', 'false');
            all.addEventListener('mouseenter', function () { setActive(links.indexOf(all)); });
            all.addEventListener('mousedown', function (e) { e.preventDefault(); });
            links.push(all);
            panel.appendChild(all);

            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function fetchNow() {
            var q = input.value.trim();
            if (q === '') { lastQ = null; close(); return; }
            if (q === lastQ && !panel.hidden) { return; }
            if (ctl) { ctl.abort(); }
            ctl = new AbortController();
            var mine = ctl;
            fetch(input.getAttribute('data-url') + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin', signal: mine.signal
            })
                .then(function (res) { if (!res.ok) { throw new Error('http ' + res.status); } return res.json(); })
                .then(function (data) {
                    if (input.value.trim() !== q) { return; } // sudah berubah lagi
                    lastQ = q;
                    render(data);
                })
                .catch(function (err) { if (!err || err.name !== 'AbortError') { close(); } });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(fetchNow, DEBOUNCE_MS);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim() !== '') { fetchNow(); }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { if (!panel.hidden) { e.preventDefault(); close(); } return; }
            if (panel.hidden || !links.length) { return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active < 0 ? links.length - 1 : active - 1); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); window.location.href = links[active].href; }
        });

        document.addEventListener('click', function (e) {
            if (!host.contains(e.target)) { close(); }
        });

        if (input.form) {
            input.form.addEventListener('submit', function () { clearTimeout(timer); if (ctl) { ctl.abort(); } close(); });
        }
    }

    [].forEach.call(document.querySelectorAll('input[data-suggest]'), attach);
})();
