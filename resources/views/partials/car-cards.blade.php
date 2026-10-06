{{-- Kartu mobil dengan foto. Butuh: $cars (koleksi CarModel dengan brand + agregat engines) --}}
<ul class="car-cards">
    @foreach ($cars as $m)
        <li>
            <a class="car-card" href="{{ route('cars.show', $m) }}">
                <span class="car-photo">
                    @php $card = $m->card_image; @endphp
                    @if ($card)
                        <img class="{{ $card['cutout'] ? 'is-cutout' : '' }}" src="{{ $card['url'] }}" alt="{{ $m->full_name }}" loading="lazy" decoding="async">
                    @else
                        <span class="car-photo-empty" style="--h: {{ $m->brand->hue }}" aria-hidden="true">
                            @if ($m->brand->logo_src)
                                @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'lg'])
                            @else
                                {{ $m->brand->code }}
                            @endif
                        </span>
                    @endif
                    @if ($m->engines_min_price_usd)
                        <span class="price-badge">
                            <small>From</small>${{ number_format($m->engines_min_price_usd) }}
                            <em>{{ \App\Models\Engine::idrLabel($m->engines_min_price_usd) }}</em>
                        </span>
                    @else
                        <span class="price-badge price-badge--na">Price not listed</span>
                    @endif
                </span>
                <span class="car-card-body">
                    <span class="car-card-brand">{{ $m->brand->name }}</span>
                    <span class="car-card-name">{{ $m->name }}</span>
                    <span class="car-card-meta">
                        <span>{{ $m->years }}</span>
                        @if ($m->engines_max_power_hp)
                            <span>{{ number_format($m->engines_max_power_hp) }} hp</span>
                        @endif
                    </span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
