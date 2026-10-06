{{--
    Kartu "showroom" untuk beranda (Newest models). Semua kartu kecil; kartu yang di-hover melebar (lihat app.css).
    Butuh: $cars (CarModel dengan brand + agregat engines: max power, max top speed, min 0-100, min harga)
    Track memakai class "car-cards" supaya carousel di home.js tetap bekerja.
--}}
<ul class="car-cards showcase">
    @foreach ($cars as $m)
        @php
            $card = $m->card_image;
            $hp = $m->engines_max_power_hp;
            $acc = $m->engines_min_zero_to_100_s;
            $top = $m->engines_max_top_speed_kmh;
        @endphp
        <li >
            <a class="sc" href="{{ route('cars.show', $m) }}">
                <span class="sc-stage">
                    <span class="sc-no" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                    @if ($card)
                        <img class="sc-img {{ $card['cutout'] ? '' : 'sc-img--photo' }}" src="{{ $card['url'] }}" alt="{{ $m->full_name }}" loading="lazy" decoding="async">
                    @else
                        <span class="sc-mark" aria-hidden="true">
                            @if ($m->brand->logo_src)
                                @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'lg'])
                            @else
                                {{ $m->brand->code }}
                            @endif
                        </span>
                    @endif
                </span>

                <span class="sc-info">
                    <span class="sc-top">
                        <span>{{ $m->brand->name }}</span>
                        <span>{{ $m->years }}</span>
                    </span>
                    <span class="sc-name">{{ $m->name }}</span>

                    <span class="sc-specs">
                        <span><b>{{ $hp ? number_format($hp) : '–' }}</b><i>hp</i></span>
                        <span><b>{{ $acc ? number_format($acc, 1) : '–' }}</b><i>0–100 s</i></span>
                        <span><b>{{ $top ? number_format($top) : '–' }}</b><i>km/h</i></span>
                    </span>

                    <span class="sc-foot">
                        @if ($m->engines_min_price_usd)
                            <span class="sc-price">
                                <em>From</em>${{ number_format($m->engines_min_price_usd) }}
                                <small>{{ \App\Models\Engine::idrLabel($m->engines_min_price_usd) }}</small>
                            </span>
                        @else
                            <span class="sc-price sc-price--na"><em>Price</em>Not listed</span>
                        @endif
                        <span class="sc-go">See full specs <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                    </span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
