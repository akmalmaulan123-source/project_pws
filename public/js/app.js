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
        if (form.classList.contains('sr-form')) {
            // Halaman Cars: terapkan lewat live-search (tanpa memuat ulang); tampilan menu disamakan oleh showroom.js.
            var menu = a.closest('details');
            if (menu) { menu.removeAttribute('open'); }
            input.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
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
