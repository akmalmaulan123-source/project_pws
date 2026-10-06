{{-- Daftar mobil bergaya layar timing. Butuh: $cars (koleksi CarModel dengan brand + agregat) --}}
<div class="rows" role="list">
    <div class="rows-head" aria-hidden="true">
        <span></span><span>Model</span><span class="r row-engines">Engines</span><span class="r row-power">Max power</span><span class="r row-price">From</span>
    </div>
    @foreach ($cars as $m)
        <a class="row" role="listitem" href="{{ route('cars.show', $m) }}">
            @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'sm'])
            <span class="row-main">
                <span class="row-model">{{ $m->name }}</span>
                <span class="row-sub">{{ $m->brand->name }}, {{ $m->years }}</span>
            </span>
            <span class="row-num row-engines">
                @if ($m->engines_count) {{ number_format($m->engines_count) }}<small>{{ \Illuminate\Support\Str::plural('engine', $m->engines_count) }}</small> @else <span class="na">–</span> @endif
            </span>
            <span class="row-num row-power">
                @if ($m->engines_max_power_hp) {{ number_format($m->engines_max_power_hp) }}<small>hp</small> @else <span class="na">–</span> @endif
            </span>
            <span class="row-num row-price">
                @if ($m->engines_min_price_usd) ${{ number_format($m->engines_min_price_usd) }} @else <span class="na">Not listed</span> @endif
            </span>
        </a>
    @endforeach
</div>
