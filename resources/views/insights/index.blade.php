@extends('layouts.app')

@section('title', 'Insights')
@section('description', 'The catalogue read like an instrument cluster: peak output, fuel mix, models per brand, power by country and by decade.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/insights.css') }}?v={{ filemtime(public_path('css/insights.css')) }}">
@endpush

@section('content')
@php
    $fuelTotal = max(1, array_sum($fuel));
    $pct = fn ($n) => $n / $fuelTotal * 100;
    $gasShare = $pct($fuel['Gasoline']);
    $electrified = $fuel['Hybrid'] + $fuel['Electric'];
    $elecShare = $pct($electrified);

    // Skala dial tenaga: kelipatan 500 hp di atas tenaga tertinggi.
    $peakScale = max(500, (int) ceil($stats['peak'] / 500) * 500);

    $fuelRows = [
        ['Gasoline', 'f1'], ['Diesel', 'f2'], ['Hybrid', 'f3'], ['Electric', 'f4'], ['Other', 'f5'],
    ];

    $brandMax = max(1, collect($topBrands)->max('count'));
    $topBrand = $topBrands[0] ?? null;
    $brandShare = $topBrand && $stats['models'] ? round($topBrand['count'] / $stats['models'] * 100) : 0;

    $countryMax = max(1, collect($countries)->max('avg'));
    $lead = $countries[0] ?? null;

    $dec = collect($decades);
    $decMax = max(1, (int) $dec->max('peak'));
    $decN = max(1, $dec->count());
    $step = 520 / $decN;
    $barW = min(36, $step * 0.5);
    $growth = $dec->count() > 1 && $dec->first()['peak'] > 0 ? $dec->last()['peak'] / $dec->first()['peak'] : null;
@endphp
<div class="wrap page ins">
    <header class="ins-head" data-ins>
        <div>
            <p class="ins-eb">Insights</p>
            <h1 aria-label="The grid, by the numbers">@foreach (['The', 'grid,', 'by', 'the', 'numbers'] as $w)<span class="ins-w" aria-hidden="true"><span style="--i: {{ $loop->index }}">{{ $w }}</span></span> @endforeach</h1>
        </div>
        <p class="ins-lead">The catalogue read like an instrument cluster: output, fuel and electrification, with the detail underneath.</p>
    </header>

    <section class="ins-cluster" data-ins aria-label="Key figures">
        @include('partials.insight-dial', ['value' => $gasShare / 100, 'num' => round($gasShare).'%', 'count' => round($gasShare), 'suffix' => '%', 'unit' => 'GASOLINE', 'cap' => 'Gasoline share', 'sub' => number_format($fuel['Gasoline']).' of '.number_format($fuelTotal).' engines'])
        @include('partials.insight-dial', ['value' => $stats['peak'] / $peakScale, 'num' => number_format($stats['peak']), 'count' => $stats['peak'], 'unit' => 'HP', 'cap' => 'Peak output', 'sub' => 'Highest engine on record', 'big' => true])
        @include('partials.insight-dial', ['value' => $elecShare / 100, 'num' => round($elecShare).'%', 'count' => round($elecShare), 'suffix' => '%', 'unit' => 'ELECTRIFIED', 'cap' => 'Hybrid and electric', 'sub' => number_format($electrified).' of '.number_format($fuelTotal).' engines'])
    </section>

    <section class="ins-totals" data-ins aria-label="Catalogue size">
        <div style="--i: 0"><b data-count="{{ $stats['models'] }}" data-delay="150">{{ number_format($stats['models']) }}</b><span>Models</span></div>
        <div style="--i: 1"><b data-count="{{ $stats['engines'] }}" data-delay="260">{{ number_format($stats['engines']) }}</b><span>Engines</span></div>
        <div style="--i: 2"><b data-count="{{ $stats['brands'] }}" data-delay="370">{{ number_format($stats['brands']) }}</b><span>Brands</span></div>
    </section>

    <section class="ins-mix" data-ins aria-label="Fuel mix">
        <div class="ins-mh"><span>Fuel mix</span><span>{{ number_format($fuelTotal) }} engines</span></div>
        <div class="ins-strip" role="img" aria-label="Share of engines by fuel type">
            @foreach ($fuelRows as [$name, $cls])
                @if ($fuel[$name] > 0)<i class="{{ $cls }}" style="width: {{ round($pct($fuel[$name]), 2) }}%; --i: {{ $loop->index }}"></i>@endif
            @endforeach
        </div>
        <ul class="ins-ml">
            @foreach ($fuelRows as [$name, $cls])
                @if ($fuel[$name] > 0)
                    <li style="--i: {{ $loop->index }}"><i class="{{ $cls }}"></i>{{ $name }} <em>{{ number_format($fuel[$name]) }}</em></li>
                @endif
            @endforeach
        </ul>
    </section>

    <section class="ins-panels" data-ins>
        <div>
            <h2>Models per brand</h2>
            @if ($topBrand)<p class="ins-cp">{{ $topBrand['name'] }} alone is {{ $brandShare }}% of the catalogue.</p>@endif
            <div class="ins-br">
                @foreach ($topBrands as $b)
                    <span style="--i: {{ $loop->index }}">{{ $b['name'] }}</span>
                    <span class="ins-t"><i @class(['hi' => $loop->first]) style="width: {{ round($b['count'] / $brandMax * 100, 1) }}%; --i: {{ $loop->index }}"></i></span>
                    <b style="--i: {{ $loop->index }}" data-count="{{ $b['count'] }}" data-delay="{{ 450 + $loop->index * 70 }}">{{ number_format($b['count']) }}</b>
                @endforeach
            </div>
        </div>

        <div>
            <h2>Power by country</h2>
            <p class="ins-cp">Average engine power of each country's brands.</p>
            <div class="ins-br">
                @foreach ($countries as $c)
                    <span style="--i: {{ $loop->index }}">{{ $c['country'] }}</span>
                    <span class="ins-t"><i @class(['hi' => $loop->first]) style="width: {{ round($c['avg'] / $countryMax * 100, 1) }}%; --i: {{ $loop->index }}"></i></span>
                    <b style="--i: {{ $loop->index }}" data-count="{{ $c['avg'] }}" data-suffix=" hp" data-delay="{{ 600 + $loop->index * 70 }}">{{ number_format($c['avg']) }} hp</b>
                @endforeach
            </div>
            @if ($lead && $lead['brands'] === 1)
                <p class="ins-nt">{{ $lead['country'] }} is a single brand ({{ $lead['brand'] }}), so its average is one maker's range.</p>
            @endif
        </div>

        <div>
            <h2>Peak by decade</h2>
            @if ($growth)<p class="ins-cp">Up {{ number_format($growth, 1) }}&times; since the {{ $dec->first()['decade'] }}s.</p>@endif
            @if ($dec->isNotEmpty())
                <svg class="ins-cols" viewBox="0 0 620 240" role="img" aria-label="Peak engine power per decade">
                    <line class="gl" x1="20" y1="200" x2="600" y2="200"/>
                    @foreach ($dec as $i => $d)
                        @php
                            $h = round($d['peak'] / $decMax * 160, 1);
                            $cx = round(50 + $step * ($i + 0.5) - 30 * 0, 1);
                            $last = $loop->last;
                        @endphp
                        <rect class="{{ $last ? 'last' : '' }}" style="--i: {{ $i }}" x="{{ round($cx - $barW / 2, 1) }}" y="{{ 200 - $h }}" width="{{ round($barW, 1) }}" height="{{ $h }}" rx="2"/>
                        <text class="tx {{ $last ? 'last' : '' }}" style="--i: {{ $i }}" x="{{ $cx }}" y="{{ 192 - $h }}" text-anchor="middle">{{ number_format($d['peak']) }}</text>
                        <text class="tx" style="--i: {{ $i }}" x="{{ $cx }}" y="222" text-anchor="middle">{{ $d['decade'] }}s</text>
                    @endforeach
                </svg>
            @else
                <p class="ins-nt">No generation years on record yet.</p>
            @endif
        </div>
    </section>
</div>
{{-- Tanpa defer: harus jalan sebelum konten terlihat, supaya keadaan awal animasi terpasang tanpa kedip --}}
<script src="{{ asset('js/insights.js') }}?v={{ filemtime(public_path('js/insights.js')) }}"></script>
@endsection
