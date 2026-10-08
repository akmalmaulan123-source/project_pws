/* Saran pencarian saat mengetik (gaya command palette): tab All / Brands / Cars, merek dan mobil yang
   diawali (atau mengandung) huruf yang diketik. Navigasi keyboard: panah atas/bawah, Enter membuka,
   Shift+Enter membuka semua hasil, Esc menutup. */
(function () {
    'use strict';

    if (!window.fetch || !window.AbortController) { return; }

    var DEBOUNCE_MS = 120;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
        var tabs = el('div', 'sg-tabs');
        var body = el('div', 'sg-body');
        var timer = null, ctl = null, openTimer = null;
        var active = -1, links = [], lastQ = null, data = null, filter = 'all';

        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Filter results');
        body.id = listId;
        body.setAttribute('role', 'listbox');
        panel.hidden = true;
        panel.appendChild(tabs);
        panel.appendChild(body);
        host.classList.add('suggest-host');
        host.appendChild(panel);
        input.setAttribute('aria-controls', listId);

        function close() {
            panel.hidden = true;
            panel.classList.remove('is-opening');
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

        function option(a) {
            a.id = listId + '-' + links.length;
            a.setAttribute('role', 'option');
            a.setAttribute('aria-selected', 'false');
            a.style.setProperty('--i', Math.min(links.length, 10));
            a.addEventListener('mouseenter', function () { setActive(links.indexOf(a)); });
            a.addEventListener('mousedown', function (e) { e.preventDefault(); }); // fokus tetap di kolom
            links.push(a);
        }

        function group(title, items, q, kind) {
            if (!items.length) { return; }
            var g = el('div', 'sg-group');
            g.setAttribute('role', 'group');
            g.setAttribute('aria-label', title);
            g.appendChild(el('p', 'sg-title', title));
            items.forEach(function (item) {
                var a = el('a', 'sg-item');
                a.href = item.url;
                option(a);
                a.appendChild(logo(item));
                var text = el('span', 'sg-text');
                var name = el('span', 'sg-name');
                highlight(name, item.name, q);
                text.appendChild(name);
                var meta = kind === 'car' && item.years !== undefined ? item.years : item.meta;
                if (meta) { text.appendChild(el('span', 'sg-meta', meta)); }
                a.appendChild(text);
                if (kind === 'brand') {
                    a.appendChild(el('span', 'sg-kind', 'Brand'));
                } else if (item.hp) {
                    a.appendChild(el('span', 'sg-hp', item.hp.toLocaleString('en-US') + ' hp'));
                }
                g.appendChild(a);
            });
            body.appendChild(g);
        }

        function buildTabs() {
            tabs.textContent = '';
            [['all', 'All', data.brands.length + data.cars.length],
             ['brands', 'Brands', data.brands.length],
             ['cars', 'Cars', data.cars.length]].forEach(function (d) {
                var b = el('button', 'sg-tab');
                var on = filter === d[0];
                b.type = 'button';
                b.setAttribute('role', 'tab');
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.classList.toggle('is-on', on);
                b.appendChild(document.createTextNode(d[1]));
                b.appendChild(el('small', '', String(d[2])));
                if (d[0] !== 'all' && d[2] === 0) { b.disabled = true; }
                b.addEventListener('mousedown', function (e) { e.preventDefault(); }); // fokus tetap di kolom
                b.addEventListener('click', function () { filter = d[0]; buildTabs(); buildList(); });
                tabs.appendChild(b);
            });
        }

        function buildList() {
            body.textContent = '';
            body.scrollTop = 0;
            links = [];
            active = -1;
            input.removeAttribute('aria-activedescendant');

            if (filter !== 'cars') { group('Brands', data.brands, data.q, 'brand'); }
            if (filter !== 'brands') { group('Cars', data.cars, data.q, 'car'); }

            if (!links.length) {
                body.appendChild(el('p', 'sg-empty', 'No cars or brands match \u201C' + data.q + '\u201D.'));
            }

            var foot = el('div', 'sg-foot');
            foot.setAttribute('role', 'presentation');

            var keys = el('span', 'sg-keys');
            keys.setAttribute('aria-hidden', 'true');
            [['\u2191\u2193', 'Navigate'], ['\u21B5', 'Open'], ['esc', 'Close']].forEach(function (k) {
                var s = el('span');
                s.appendChild(el('kbd', '', k[0]));
                s.appendChild(document.createTextNode(k[1]));
                keys.appendChild(s);
            });
            foot.appendChild(keys);

            var all = el('a', 'sg-all');
            all.href = data.all_url;
            option(all);
            all.appendChild(el('span', '', 'See all results for \u201C' + data.q + '\u201D'));
            all.appendChild(el('kbd', '', '\u21E7\u21B5'));
            foot.appendChild(all);

            body.appendChild(foot);
        }

        function render(d) {
            var wasHidden = panel.hidden;
            data = d;
            if ((filter === 'brands' && !d.brands.length) || (filter === 'cars' && !d.cars.length)) { filter = 'all'; }

            var any = d.brands.length + d.cars.length > 0;
            tabs.hidden = !any;
            if (any) { buildTabs(); }
            buildList();

            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');

            // Animasi buka hanya saat panel baru muncul, bukan di setiap ketikan
            if (wasHidden && !reduce) {
                panel.classList.add('is-opening');
                clearTimeout(openTimer);
                openTimer = setTimeout(function () { panel.classList.remove('is-opening'); }, 900);
            }
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
                .then(function (d) {
                    if (input.value.trim() !== q) { return; } // sudah berubah lagi
                    lastQ = q;
                    render(d);
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
            else if (e.key === 'Enter' && e.shiftKey && data) { e.preventDefault(); window.location.href = data.all_url; }
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
