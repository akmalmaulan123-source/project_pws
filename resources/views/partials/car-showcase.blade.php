{{--
    Kartu koleksi untuk beranda (Newest models): panel gambar dengan lencana tahun, logo merek + nama model,
    lalu tiga angka ringkas (tenaga, 0-100, kecepatan maks).
    Tanpa foto mobil, logo merek mengisi panel gambar. Foto akan otomatis menggantikannya begitu ada ($m->card_image).
    Butuh: $cars (CarModel dengan brand + agregat engines: max power, max top speed, min 0-100)
    Class "car-cards showcase" dipertahankan: home.js memakainya untuk animasi muncul saat di-scroll.
--}}
<ul class="car-cards showcase nc-grid">
    @foreach ($cars as $m)
        @php
            $card = $m->card_image;
            $hp = $m->engines_max_power_hp;
            $acc = $m->engines_min_zero_to_100_s;
            $top = $m->engines_max_top_speed_kmh;
        @endphp
        <li style="--i: {{ $loop->index }}">
            <a class="nc" href="{{ route('cars.show', $m) }}">
                <span class="nc-media">
                    @if ($m->year_start)
                        <span class="nc-year">{{ $m->year_start }}</span>
                    @endif
                    @if ($card)
                        <img class="nc-img {{ $card['cutout'] ? '' : 'nc-img--photo' }}" src="{{ $card['url'] }}" alt="{{ $m->full_name }}" loading="lazy" decoding="async">
                    @else
                        <span class="nc-mark" aria-hidden="true">
                            @if ($m->brand->logo_src)
                                @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'lg'])
                            @else
                                {{ $m->brand->code }}
                            @endif
                        </span>
                    @endif
                </span>

                <span class="nc-head">
                    <span class="nc-badge" aria-hidden="true">
                        @if ($m->brand->logo_src)
                            @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'sm'])
                        @else
                            {{ $m->brand->code }}
                        @endif
                    </span>
                    <span class="nc-title">
                        <strong>{{ $m->name }}</strong>
                        <small>{{ $m->brand->name }} · {{ $m->engines_count }} {{ \Illuminate\Support\Str::plural('engine', $m->engines_count) }}</small>
                    </span>
                </span>

                <span class="nc-stats">
                    <span><b>{{ $hp ? number_format($hp).' hp' : '–' }}</b><i>Power</i></span>
                    <span><b>{{ $acc ? number_format($acc, 1).'s' : '–' }}</b><i>0–100</i></span>
                    <span><b>{{ $top ? number_format($top).' km/h' : '–' }}</b><i>Top</i></span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
