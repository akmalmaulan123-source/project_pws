{{-- Menu urutan (dropdown tanpa JS: <details> berisi tautan). Butuh: $sorts [kunci => label], $sort (kunci aktif) --}}
<details class="sort-menu" data-sort-menu>
    <summary aria-label="Sort results">
        <span class="sort-label">Sort by</span>
        <span class="sort-value">{{ $sorts[$sort] }}</span>
        <svg class="sort-caret" viewBox="0 0 12 8" width="12" height="8" aria-hidden="true"><path d="M1 1.5 6 6.5 11 1.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <ul class="sort-list">
        @foreach ($sorts as $key => $label)
            <li>
                <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}" @class(['is-on' => $sort === $key]) @if ($sort === $key) aria-current="true" @endif>
                    <span>{{ $label }}</span>
                    @if ($sort === $key)
                        <svg viewBox="0 0 14 11" width="14" height="11" aria-hidden="true"><path d="M1.5 5.5 5 9l7.5-7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</details>
