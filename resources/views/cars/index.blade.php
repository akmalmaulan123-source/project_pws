@extends('layouts.app')

@section('title', 'Cars')
@section('description', 'Pick a car from '.number_format($totalModels).' models: filter by brand, fuel, power and budget, and see its specs at a glance.')

@section('content')
@php
    $fuelOptions = ['Gasoline', 'Diesel', 'Electric', 'Hybrid'];
    $fuelNow = $filters['fuel_type'];
    $powerNow = (int) ($filters['min_power'] ?? 0);
    // Skala slider tenaga: tiap 100 hp sampai 1.000, lalu tiap 250 hp sampai tenaga tertinggi di katalog
    // (tenaga tersebar tidak merata: 422 model di atas 300 hp, tetapi hanya segelintir di atas 1.500 hp).
    $powerStops = [0];
    for ($s = 100; $s <= min(1000, max($powerMax, 100)); $s += 100) { $powerStops[] = $s; }
    for ($s = 1250; $s <= $powerMax; $s += 250) { $powerStops[] = $s; }
    $powerIdx = max(0, count(array_filter($powerStops, fn ($s) => $s <= $powerNow)) - 1);
    // Slider budget (USD). Ujung kanan (0) = tanpa batas; urutan tahap dikirim dari controller.
    $priceNow = (int) ($filters['max_price'] ?? 0);
    $priceIdx = count($priceStops) - 1;
    if ($priceNow > 0) {
        $priceIdx = 0;
        foreach ($priceStops as $i => $stop) { if ($stop > 0 && $stop <= $priceNow) { $priceIdx = $i; } }
    }
    $first = $cars->first();
@endphp
<div class="wrap page sr-page">
    <header class="page-head sr-head">
        <h1>Select <span>your car</span></h1>
        <p class="sr-cnt">{{ number_format($totalModels) }} models · {{ $brandStrip->count() }} brands</p>
    </header>

    <form class="filters sr-form" action="{{ route('cars.index') }}" method="get">
        <div class="sr-search">
            <label class="sr-only" for="f-q">Brand or model</label>
            <input id="f-q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Search brand or model" autocomplete="off" data-suggest data-url="{{ route('search.suggest') }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-haspopup="listbox">
        </div>

        <input type="hidden" name="brand_id" value="{{ $filters['brand_id'] }}">
        <input type="hidden" name="fuel_type" value="{{ $fuelNow }}">
        <input type="hidden" name="min_power" value="{{ $filters['min_power'] }}">
        <input type="hidden" name="max_price" value="{{ $filters['max_price'] }}">

        <div class="sr-brands" role="group" aria-label="Brand" data-sr-group="brand_id">
            <button type="button" class="sr-bb {{ $filters['brand_id'] ? '' : 'is-on' }}" data-sr-pick data-value="">All brands</button>
            @foreach ($brandStrip as $b)
                <button type="button" class="sr-bb {{ (int) $filters['brand_id'] === $b->id ? 'is-on' : '' }}" data-sr-pick data-value="{{ $b->id }}">
                    @include('partials.brand-logo', ['brand' => $b, 'size' => 'xs'])
                    <span>{{ $b->name }}</span><em>{{ $b->car_models_count }}</em>
                </button>
            @endforeach
        </div>

        <div class="sr-ctl">
            <div class="sr-grp">
                <span class="sr-cl">Fuel</span>
                <span class="sr-seg" role="group" aria-label="Fuel" data-sr-group="fuel_type">
                    <button type="button" class="{{ $fuelNow ? '' : 'is-on' }}" data-sr-pick data-value="">All</button>
                    @foreach ($fuelOptions as $f)
                        <button type="button" class="{{ $fuelNow === $f ? 'is-on' : '' }}" data-sr-pick data-value="{{ $f }}" data-g="{{ $f }}"><i class="sr-dot"></i>{{ $f }}</button>
                    @endforeach
                    @if ($fuelNow && ! in_array($fuelNow, $fuelOptions, true))
                        <button type="button" class="is-on" data-sr-pick data-value="{{ $fuelNow }}"><i class="sr-dot"></i>{{ $fuelNow }}</button>
                    @endif
                </span>
            </div>
            <div class="sr-grp sr-pw">
                <label class="sr-cl" for="f-power">Min power</label>
                <input id="f-power" class="sr-range" type="range" min="0" max="{{ count($powerStops) - 1 }}" step="1" value="{{ $powerIdx }}" data-sr-range data-stops="{{ json_encode($powerStops) }}" data-field="min_power" data-prefix="≥ " data-suffix=" hp">
                <b data-sr-pv>{{ $powerNow ? '≥ '.number_format($powerNow).' hp' : 'Any' }}</b>
            </div>
            @if ($hasPrices)
                <div class="sr-grp sr-pw">
                    <label class="sr-cl" for="f-price">Budget</label>
                    <input id="f-price" class="sr-range" type="range" min="0" max="{{ count($priceStops) - 1 }}" step="1" value="{{ $priceIdx }}" data-sr-range data-stops="{{ json_encode($priceStops) }}" data-field="max_price" data-prefix="≤ $" data-any="last">
                    <b data-sr-pv>{{ $priceNow ? '≤ $'.number_format($priceNow) : 'Any' }}</b>
                </div>
            @endif
            @include('partials.filter-select', ['name' => 'country', 'label' => 'Country', 'options' => array_combine($countryOptions, $countryOptions), 'value' => $filters['country'], 'all' => 'All countries'])
            @include('partials.filter-select', ['name' => 'sort', 'label' => 'Sort', 'options' => collect($sorts)->except('brand')->all(), 'value' => $sort === 'brand' ? '' : $sort, 'all' => $sorts['brand']])
            <div class="filter-actions sr-act">
                <button type="submit" class="btn sr-apply">Apply</button>
                @if ($activeCount || $sort !== 'brand')
                    <a class="btn btn-quiet" href="{{ route('cars.index') }}">Reset</a>
                @endif
            </div>
        </div>
    </form>

    @if ($first)
        <p class="results-count sr-line">Showing {{ number_format($cars->firstItem()) }}–{{ number_format($cars->lastItem()) }} of {{ number_format($cars->total()) }} {{ \Illuminate\Support\Str::plural('model', $cars->total()) }}</p>

        <div class="sr-lay" data-showroom>
            <aside class="sr-spot" aria-label="Selected car" style="--cls: {{ $first->sr['clr'] }}">
                <div class="sr-sp1">
                    @include('partials.brand-logo', ['brand' => $first->brand, 'size' => 'md'])
                    <span data-sr="brand">{{ $first->brand->name }}@if ($first->brand->country), {{ $first->brand->country }}@endif</span>
                    <span class="sr-rank" data-sr="rank">@if ($first->sr['rank']) Power rank #{{ $first->sr['rank'] }} @endif</span>
                    <span class="sr-cls" data-sr="cls">{{ $first->sr['cls'] }}</span>
                </div>
                <h2 class="sr-sn" data-sr="name">{{ $first->name }}</h2>
                <p class="sr-sy" data-sr="years">{{ $first->years }} · {{ $first->engines_count }} {{ \Illuminate\Support\Str::plural('engine', $first->engines_count) }} on record</p>

                <div class="sr-price">
                    <span class="sr-pl">From</span>
                    <b data-sr="price" class="{{ $first->sr['price'] ? '' : 'na' }}">{{ $first->sr['price'] ? '$'.number_format($first->sr['price']) : 'Not listed' }}</b>
                    <small data-sr="idr">{{ $first->sr['idr'] }}</small>
                </div>

                <div class="sr-stats">
                    <div class="sr-st"><div class="sr-stt"><span>Power</span><b><span data-sr="hp">{{ $first->sr['hp'] ? number_format($first->sr['hp']) : '–' }}</span><small>hp</small></b></div><div class="sr-br"><i data-sr="hpBar"></i></div></div>
                    <div class="sr-st"><div class="sr-stt"><span>0 to 100 km/h</span><b data-sr="zero">{!! $first->sr['zero'] ? number_format($first->sr['zero'], 1).'<small>s</small>' : '–' !!}</b></div><div class="sr-br"><i data-sr="zeroBar"></i></div></div>
                    <div class="sr-st"><div class="sr-stt"><span>Top speed</span><b data-sr="top">{!! $first->sr['top'] ? number_format($first->sr['top']).'<small>km/h</small>' : '–' !!}</b></div><div class="sr-br"><i data-sr="topBar"></i></div></div>
                    <div class="sr-st"><div class="sr-stt"><span>Engines</span><b data-sr="eng">{{ $first->engines_count ?: '–' }}</b></div></div>
                </div>
                <div class="sr-fu" data-sr="fuels">
                    @foreach ($first->sr['fuels'] as $g)<span class="sr-fl" data-g="{{ $g }}">{{ $g }}</span>@endforeach
                </div>
                <a class="sr-go" href="{{ $first->sr['url'] }}" data-sr="go"><span>Enter garage</span><b aria-hidden="true">&rarr;</b></a>
            </aside>

            <section class="sr-main" aria-label="Cars">
                <div class="sr-rack">
                    @foreach ($cars as $m)
                        @php $s = $m->sr; @endphp
                        <a class="sr-tile {{ $loop->first ? 'is-on' : '' }}" href="{{ $s['url'] }}" style="--i: {{ min($loop->index, 16) }}; --cls: {{ $s['clr'] }}"
                           data-name="{{ $m->name }}" data-brand="{{ $m->brand->name }}" data-country="{{ $m->brand->country }}" data-years="{{ $m->years }}"
                           data-hp="{{ $s['hp'] }}" data-zero="{{ $s['zero'] }}" data-top="{{ $s['top'] }}" data-eng="{{ $m->engines_count }}"
                           data-fuels="{{ implode(',', $s['fuels']) }}" data-rank="{{ $s['rank'] }}"
                           data-price="{{ $s['price'] }}" data-idr="{{ $s['idr'] }}" data-cls="{{ $s['cls'] }}" data-clr="{{ $s['clr'] }}"
                           data-logo="{{ $m->brand->logo_src }}" data-code="{{ $m->brand->code }}" data-hue="{{ $m->brand->hue }}">
                            <span class="sr-tin">
                                <span class="sr-stripe"></span>
                                <span class="sr-t1">@include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'xs'])<span>{{ $m->year_start ?: '–' }}</span></span>
                                <span class="sr-tn">{{ $m->name }}</span>
                                <span class="sr-th">
                                    <span class="sr-cls">{{ $s['cls'] }}</span>
                                    @if ($s['hp'])<b>{{ number_format($s['hp']) }}<small>hp</small></b>@else<span class="na">No power data</span>@endif
                                    @if ($s['zero'])<span class="sr-tz">{{ number_format($s['zero'], 1) }}s</span>@endif
                                </span>
                                <span class="sr-pb"><i style="width: {{ round(min(100, ($s['hp'] ?? 0) / 10) / 10) * 10 }}%"></i></span>
                                <span class="sr-tp"><em>From</em>@if ($s['price'])<b>${{ number_format($s['price']) }}</b>@else<span class="na">Not listed</span>@endif</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                {{ $cars->links() }}
            </section>
        </div>
    @else
        <div class="empty">
            <h2>No cars match these filters</h2>
            <p>Remove a filter or lower the minimum power to see more results.</p>
            <p><a class="btn" href="{{ route('cars.index') }}">Clear all filters</a></p>
        </div>
    @endif
</div>
@push('scripts')
<script src="{{ asset('js/showroom.js') }}?v={{ filemtime(public_path('js/showroom.js')) }}" defer></script>
@endpush
@endsection