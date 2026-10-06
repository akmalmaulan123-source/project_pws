@extends('layouts.app')

@section('title', 'Every car on the grid')

@section('content')
{{-- Tandai JS aktif sebelum konten digambar, supaya animasi muncul tanpa kedip. Tanpa JS semua tetap terlihat. --}}
<script>document.documentElement.classList.add('js');</script>

@php
    // $feature dari HomeController: sebuah Porsche (atau null kalau datanya belum diimpor).
    $feature = $feature ?? null;

    if ($feature) {
        $hp = $feature['power'];
        $kw = round($hp * 0.7457);
        $name = trim($feature['brand'].' '.$feature['model']);
        $specLine = collect([$feature['engine'], $feature['transmission'], $feature['drivetrain']])->filter()->implode(', ');

        // Lingkaran kiri: top speed; kalau datanya kosong, pakai 0 to 100 atau torsi.
        if ($feature['top_speed']) {
            $left = ['Top speed', number_format($feature['top_speed']), 'km/h', number_format(round($feature['top_speed'] * 0.621371)).' mph'];
        } elseif ($feature['zero_to_100']) {
            $left = ['0 to 100', number_format($feature['zero_to_100'], 1), 's', 'km/h'];
        } elseif ($feature['torque']) {
            $left = ['Torque', number_format($feature['torque']), 'Nm', number_format(round($feature['torque'] * 0.737562)).' lb-ft'];
        } else {
            $left = null;
        }
    }
@endphp

<section class="show" aria-labelledby="show-title">
    @if ($feature)
        <div class="show-stage {{ empty($feature['image']) ? 'is-bare' : '' }} {{ ! empty($feature['cutout']) ? 'is-cutout' : '' }}">
            @if (! empty($feature['image']))
                <img class="show-ambient" src="{{ $feature['image'] }}" alt="" aria-hidden="true" decoding="async">
            @endif
            <span class="show-outline" style="--len: {{ max(4, mb_strlen($feature['model'])) }}" aria-hidden="true">{{ $feature['model'] }}</span>
            @if (! empty($feature['image']))
                <span class="show-floor" aria-hidden="true"></span>
                <img class="show-car" src="{{ $feature['image'] }}" alt="{{ $name }}" decoding="async" fetchpriority="high">
            @endif
        </div>

        <div class="wrap show-foot">
            @if ($left)
                <div class="show-ring">
                    <span class="ring-label">{{ $left[0] }}</span>
                    <strong>{{ $left[1] }}<small>{{ $left[2] }}</small></strong>
                    <span class="ring-sub">{{ $left[3] }}</span>
                </div>
            @else
                <span></span>
            @endif

            <div class="show-info">
                <h1 id="show-title">{{ $name }}</h1>
                <p>{{ $specLine }}</p>
                <a class="show-cta" href="{{ route('cars.show', $feature['car_id']) }}">See full specs</a>
            </div>

            <div class="show-ring show-ring--r">
                <span class="ring-label">Power</span>
                <strong>{{ number_format($hp) }}<small>hp</small></strong>
                <span class="ring-sub">{{ number_format($kw) }} kW</span>
            </div>
        </div>
    @else
        <h1 id="show-title" class="sr-only">Every car on the grid</h1>
    @endif
</section>

<div class="wrap">
    <div class="stats-strip" data-reveal>
        <div>
            <strong data-count="{{ $stats['models'] }}">{{ number_format($stats['models']) }}</strong><span>Models</span>
            <svg class="stat-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 17v-4l3-1 3-5h8l3 5 3 1v4"/><path d="M2 17h3M9 17h6M19 17h3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M12 7v5"/></svg>
        </div>
        <div>
            <strong data-count="{{ $stats['engines'] }}">{{ number_format($stats['engines']) }}</strong><span>Engines</span>
            <svg class="stat-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18a9 9 0 0 1 18 0"/><path d="M12 18l5-6"/><circle cx="12" cy="18" r="1.5" fill="currentColor"/><path d="M12 9v2M6 12l1.5 1.5M18 12l-1.5 1.5"/></svg>
        </div>
        <div>
            <strong data-count="{{ $stats['brands'] }}">{{ number_format($stats['brands']) }}</strong><span>Brands</span>
            <svg class="stat-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3v18"/><path d="M5 4h15v9H5"/><path fill="currentColor" stroke="none" d="M8 4h3v3H8zM14 4h3v3h-3zM5 7h3v3H5zM11 7h3v3h-3zM17 7h3v3h-3zM8 10h3v3H8zM14 10h3v3h-3z"/></svg>
        </div>
    </div>
</div>

<div class="wrap page home-page">
    <section class="block" aria-labelledby="new-title" data-reveal>
        <div class="block-head">
            <h2 id="new-title">Newest models</h2>
            <a href="{{ route('cars.index', ['sort' => 'newest']) }}">See all newest</a>
        </div>
        <div class="carousel carousel--showcase" data-carousel>
            <button class="car-nav car-nav-prev" type="button" data-dir="-1" aria-label="Previous models">&lsaquo;</button>
            @include('partials.car-showcase', ['cars' => $newest])
            <button class="car-nav car-nav-next" type="button" data-dir="1" aria-label="Next models">&rsaquo;</button>
        </div>
    </section>

    <section class="block" aria-labelledby="makes-title">
        <div class="block-head" data-reveal>
            <h2 id="makes-title">Browse by brand</h2>
            <a href="{{ route('brands.index') }}">See all {{ number_format($stats['brands']) }} brands</a>
        </div>
        <ul class="make-grid">
    @foreach ($brands as $b)
        <li data-reveal style="--d: {{ $loop->index % 6 }}">
            <a href="{{ route('brands.show', $b) }}" style="--h: {{ $b->hue }}">
                @include('partials.brand-logo', ['brand' => $b])
                <span class="make-name">{{ $b->name }}</span>
                <span class="make-count">{{ $b->car_models_count }} models</span>
            </a>
        </li>
    @endforeach
</ul>
    </section>

    <section class="block" aria-labelledby="fuel-title" data-reveal>
        <div class="block-head"><h2 id="fuel-title">Browse by fuel</h2></div>
        <ul class="fuel-list">
            @foreach ($fuels as $f)
                <li>
                    <a href="{{ route('cars.index', ['fuel_type' => $f['fuel']]) }}">
                        @include('partials.fuel-tag', ['fuel' => $f['fuel']])
                        <span class="fuel-total">{{ number_format($f['total']) }} engines</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</div>

<script src="{{ asset('js/home.js') }}?v={{ filemtime(public_path('js/home.js')) }}" defer></script>
@endsection
