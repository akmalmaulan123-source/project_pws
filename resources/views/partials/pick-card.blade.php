{{--
    Kartu satu mobil di halaman Top picks. Desainnya sama dengan kartu Newest models di beranda (.nc):
    panel gambar (nomor urut, kelas, tahun), logo merek + nama model, angka utama daftar, harga, tiga angka ringkas.
    Butuh: $car (CarModel dengan brand), $p (ringkasan dari TopPicks), $metric ['value', 'label'] (angka utama daftar ini).
    Opsional: $rank (nomor urut), $showPrice (default true; false bila harga sudah jadi angka utama).
--}}
@php
    $rank = $rank ?? null;
    $showPrice = $showPrice ?? true;
    $card = $car->card_image;
@endphp
<a class="nc pk-card" href="{{ route('brands.show', ['brand' => $car->brand, 'car' => $car->id]) }}">
    <span class="nc-media">
        @if ($rank)<span class="nc-rank">{{ $rank }}</span>@endif
        @if ($car->year_start)<span class="nc-year">{{ $car->year_start }}</span>@endif
        @if ($card)
            <img class="nc-img {{ $card['cutout'] ? '' : 'nc-img--photo' }}" src="{{ $card['url'] }}" alt="{{ $car->full_name }}" loading="lazy" decoding="async">
        @else
            <span class="nc-mark" aria-hidden="true">
                @if ($car->brand->logo_src) @include('partials.brand-logo', ['brand' => $car->brand, 'size' => 'lg']) @else {{ $car->brand->code }} @endif
            </span>
        @endif
    </span>

    <span class="nc-head">
        <span class="nc-badge" aria-hidden="true">
            @if ($car->brand->logo_src) @include('partials.brand-logo', ['brand' => $car->brand, 'size' => 'sm']) @else {{ $car->brand->code }} @endif
        </span>
        <span class="nc-title">
            <strong>{{ $car->name }}</strong>
            <small>{{ $car->brand->name }}</small>
        </span>
    </span>

    <span class="nc-metric"><b>{{ $metric['value'] }}</b><i>{{ $metric['label'] }}</i></span>

    @if ($showPrice)
        <span class="nc-price">
            @if ($p['price'])
                <i>From</i><b>${{ number_format($p['price']) }}</b><small>{{ \App\Models\Engine::idrLabel($p['price']) }}</small>
            @else
                <i>Price</i><span class="na">Not listed</span>
            @endif
        </span>
    @endif

    <span class="nc-stats">
        <span><b>{{ $p['hp'] ? number_format($p['hp']).' hp' : '–' }}</b><i>Power</i></span>
        <span><b>{{ $p['zero'] ? number_format($p['zero'], 1).'s' : '–' }}</b><i>0–100</i></span>
        <span><b>{{ $p['top'] ? number_format($p['top']).' km/h' : '–' }}</b><i>Top</i></span>
    </span>
</a>
