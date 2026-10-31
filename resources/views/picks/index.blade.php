@extends('layouts.app')

@section('title', 'Top picks')
@section('description', 'Ready-made shortlists to help you choose: best value by class, the quickest cars under $'.number_format($budgetCap).', top hybrids and EVs, and the cheapest way into each performance class.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/picks.css') }}?v={{ filemtime(public_path('css/picks.css')) }}">
@endpush

@section('content')
{{-- Tandai JS aktif sebelum konten digambar, supaya animasi masuk tidak berkedip. Tanpa JS semua tetap terlihat. --}}
<script>document.documentElement.classList.add('js');</script>
@php
    // Buang entri yang modelnya sudah tidak ada (cache angka bisa lebih tua daripada data).
    $alive = fn (array $list) => array_values(array_filter($list, fn ($p) => isset($models[$p['id']])));
    $value = $alive($picks['value']);
    $quick = $alive($picks['quick']);
    $electrified = $alive($picks['electrified']);
    $any = $value || $quick || $electrified;
@endphp
<div class="wrap page pk">
    <header class="page-head pk-head">
        <h1>Top <span>picks</span></h1>
        <p>Ready-made shortlists from {{ number_format($totalModels) }} models, ranked by the numbers. Prices are estimates in USD.</p>
    </header>

    <details class="pk-how">
        <summary>How the ranking works</summary>
        <div class="pk-how-body">
            <div>
                <h3>Starting variant</h3>
                <p>Every pick is judged on the starting variant of the model’s newest generation: its cheapest engine, with power, speed and price all taken from that same engine. Only models with a generation from {{ $minGenYear }} onward are listed.</p>
            </div>
            <div>
                <h3>Performance Index</h3>
                <p>A score from 0 to 1,000: 50% power, 25% 0–100 km/h and 25% top speed. Missing data is skipped, never guessed.</p>
            </div>
            <div>
                <h3>Value</h3>
                <p>Horsepower per $1,000 of the starting price. Only cars with at least {{ number_format($valueMinHp) }} hp are ranked, so cheap runabouts don’t crowd out the fast cars.</p>
            </div>
            <div>
                <h3>Prices</h3>
                <p>Estimates in USD, shown as “From” (the cheapest variant). Rupiah uses a fixed exchange rate. Treat them as a guide, not a quote.</p>
            </div>
        </div>
    </details>

    @if (! $any)
        <div class="pk-empty">
            <h2>No picks yet</h2>
            <p>Shortlists appear once cars have prices and performance data.</p>
            <a class="btn" href="{{ route('cars.index') }}">Browse all cars</a>
        </div>
    @else
        @if ($value)
            <section class="pk-sec" aria-labelledby="pk-value">
                <div class="pk-sh">
                    <h2 id="pk-value">Best value</h2>
                    <p>The most horsepower for the money among cars with at least {{ number_format($valueMinHp) }} hp.</p>
                </div>
                <div class="pk-grid">
                    @foreach ($value as $p)
                        @include('partials.pick-card', ['car' => $models[$p['id']], 'p' => $p, 'rank' => $loop->iteration, 'metric' => ['value' => number_format($p['value'], 1), 'label' => 'hp per $1K']])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($quick)
            <section class="pk-sec" aria-labelledby="pk-quick">
                <div class="pk-sh">
                    <h2 id="pk-quick">Quickest under ${{ number_format($budgetCap) }}</h2>
                    <p>The fastest 0–100 km/h times among cars whose starting price is ${{ number_format($budgetCap) }} or less.</p>
                    <a class="pk-all" href="{{ route('cars.index', ['max_price' => $budgetCap, 'sort' => 'fast']) }}">See all &rarr;</a>
                </div>
                <div class="pk-grid">
                    @foreach ($quick as $p)
                        @include('partials.pick-card', ['car' => $models[$p['id']], 'p' => $p, 'rank' => $loop->iteration, 'metric' => ['value' => number_format($p['zero'], 1).'s', 'label' => '0–100 km/h']])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($electrified)
            <section class="pk-sec" aria-labelledby="pk-ev">
                <div class="pk-sh">
                    <h2 id="pk-ev">Best hybrid &amp; electric</h2>
                    <p>The best hybrid and electric models up to ${{ number_format($electrifiedCap) }}, ranked by Performance Index.</p>
                    <span class="pk-all-group">
                        <a class="pk-all" href="{{ route('cars.index', ['fuel_type' => 'Electric', 'sort' => 'power']) }}">All electric &rarr;</a>
                        <a class="pk-all" href="{{ route('cars.index', ['fuel_type' => 'Hybrid', 'sort' => 'power']) }}">All hybrid &rarr;</a>
                    </span>
                </div>
                <div class="pk-grid">
                    @foreach ($electrified as $p)
                        @include('partials.pick-card', ['car' => $models[$p['id']], 'p' => $p, 'rank' => $loop->iteration, 'metric' => ['value' => number_format($p['pi']), 'label' => 'Performance Index']])
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    <section class="pk-cta">
        <div>
            <h2>Still deciding?</h2>
            <p>Filter all {{ number_format($totalModels) }} cars by brand, fuel, power and budget.</p>
        </div>
        <a class="btn" href="{{ route('cars.index') }}">Browse all cars</a>
    </section>
</div>
<script src="{{ asset('js/picks.js') }}?v={{ filemtime(public_path('js/picks.js')) }}"></script>
@endsection
