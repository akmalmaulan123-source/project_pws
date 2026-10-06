{{--
    Dropdown filter dengan gaya yang sama seperti menu Sort (tanpa <select> bawaan browser).
    Butuh: $name (nama parameter), $label, $options [nilai => teks], $value (nilai aktif), $all (teks "semua")
    Tanpa JS tiap pilihan tetap berupa tautan; dengan JS pilihan diterapkan lewat form agar isian lain ikut terkirim.
--}}
@php
    $current = (string) ($value ?? '');
    $shown = $current !== '' ? ($options[$current] ?? $all) : $all;
@endphp
<div class="field">
    <span class="field-label" id="lbl-{{ $name }}">{{ $label }}</span>
    <input type="hidden" name="{{ $name }}" value="{{ $current }}">
    <details class="sort-menu dd" data-sort-menu>
        <summary aria-labelledby="lbl-{{ $name }}">
            <span class="sort-value">{{ $shown }}</span>
            <svg class="sort-caret" viewBox="0 0 12 8" width="12" height="8" aria-hidden="true"><path d="M1 1.5 6 6.5 11 1.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>
        <ul class="sort-list">
            <li>
                <a href="{{ request()->fullUrlWithQuery([$name => null, 'page' => null]) }}" data-dd-value="" @class(['is-on' => $current === ''])>
                    <span>{{ $all }}</span>
                    @if ($current === '')<svg viewBox="0 0 14 11" width="14" height="11" aria-hidden="true"><path d="M1.5 5.5 5 9l7.5-7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>@endif
                </a>
            </li>
            @foreach ($options as $val => $text)
                <li>
                    <a href="{{ request()->fullUrlWithQuery([$name => $val, 'page' => null]) }}" data-dd-value="{{ $val }}" @class(['is-on' => $current === (string) $val])>
                        <span>{{ $text }}</span>
                        @if ($current === (string) $val)<svg viewBox="0 0 14 11" width="14" height="11" aria-hidden="true"><path d="M1.5 5.5 5 9l7.5-7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </details>
</div>
