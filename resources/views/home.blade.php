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
        $chips = collect([$feature['engine'], $feature['transmission'], $feature['drivetrain']])->filter()->values();

        // Speedometer HUD: top speed; kalau datanya kosong, pakai 0 to 100 atau torsi.
        // $hudRaw/$hudDec dipakai hero.js untuk hitung naik; gigi dan LED rpm mengikuti kecepatan itu.
        $hudRaw = 0;
        $hudDec = 0;
        if ($feature['top_speed']) {
            $hud = ['Top speed', number_format($feature['top_speed']), 'km/h'];
            $hudRaw = $feature['top_speed'];
        } elseif ($feature['zero_to_100']) {
            $hud = ['0 to 100', number_format($feature['zero_to_100'], 1), 's'];
            $hudRaw = $feature['zero_to_100'];
            $hudDec = 1;
        } elseif ($feature['torque']) {
            $hud = ['Torque', number_format($feature['torque']), 'Nm'];
            $hudRaw = $feature['torque'];
        } else {
            $hud = null;
        }
        $powerFill = round(max(12, min(100, $hp / 1200 * 100)), 1);
        $ledTotal = 26;
        $ledOn = (int) round(0.72 * $ledTotal); // keadaan akhir tanpa animasi
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
            <div class="show-info">
                <h1 id="show-title"><em>{{ $feature['brand'] }}</em> {{ $feature['model'] }}</h1>
                @if ($chips->isNotEmpty())
                    <ul class="hud-chips">
                        @foreach ($chips as $chip)
                            <li style="--i: {{ $loop->index }}"><span>{{ $chip }}</span></li>
                        @endforeach
                    </ul>
                @endif
                <a class="show-cta" href="{{ route('cars.show', $feature['car_id']) }}"><span>See full specs</span></a>
            </div>

            {{-- HUD balapan: gigi, speedometer digital, LED rpm, bar tenaga (animasinya di hero.js) --}}
            <div class="hud">
                @if ($hud)
                    <div class="hud-gear" data-hud-gear aria-hidden="true">
                        <div><b data-hud-gear-num>7</b><span class="hud-lb">Gear</span></div>
                    </div>
                @endif
                <div class="hud-speed">
                    <div>
                        @if ($hud)
                            <span class="hud-lb">{{ $hud[0] }}</span>
                            <strong class="hud-big"><span data-hud-speed="{{ $hudRaw }}" data-decimals="{{ $hudDec }}">{{ $hud[1] }}</span><small>{{ $hud[2] }}</small></strong>
                            <div class="hud-led" aria-hidden="true">
                                @for ($i = 0; $i < $ledTotal; $i++)<i @class(['on' => $i < $ledOn])></i>@endfor
                            </div>
                        @endif
                        <div class="hud-power">
                            <span class="hud-lb">Power</span>
                            <span class="hud-track" aria-hidden="true"><i style="--f: {{ $powerFill }}"></i></span>
                            <b><span data-hero-num="{{ $hp }}" data-decimals="0" data-delay="800">{{ number_format($hp) }}</span><small> hp</small></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <h1 id="show-title" class="sr-only">Every car on the grid</h1>
    @endif
</section>
@if ($feature)
    {{-- Tanpa defer: harus jalan sebelum HUD tampil (animasi CSS menunda HUD 0,5 dtk) --}}
    <script src="{{ asset('js/hero.js') }}?v={{ filemtime(public_path('js/hero.js')) }}"></script>
@endif

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
    {{-- data-reveal hanya di judul; kartu-kartu punya animasi muncul sendiri (home.js + .showcase.is-in) --}}
    <section class="block" aria-labelledby="new-title">
        <div class="block-head" data-reveal>
            <h2 id="new-title">Newest models</h2>
            <a href="{{ route('cars.index', ['sort' => 'newest']) }}"><span>See all newest</span></a>
        </div>
        @include('partials.car-showcase', ['cars' => $newest])
    </section>

    <section class="block" aria-labelledby="makes-title">
        <div class="block-head" data-reveal>
            <h2 id="makes-title">Browse by brand</h2>
            <a href="{{ route('brands.index') }}"><span>See all {{ number_format($stats['brands']) }} brands</span></a>
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

    <section class="block" aria-labelledby="fuel-title">
        <div class="block-head" data-reveal><h2 id="fuel-title">Browse by fuel</h2></div>
        <ul class="fuel-list" data-reveal>
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
