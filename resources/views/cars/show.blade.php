@extends('layouts.app')

@section('title', $carModel->full_name.' specs')
@section('description', 'Engines, power and specifications for the '.$carModel->full_name.'.')

@section('content')
<div class="wrap page">
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('cars.index') }}">Cars</a>
        <a href="{{ route('brands.show', $carModel->brand) }}">{{ $carModel->brand->name }}</a>
        <span aria-current="page">{{ $carModel->name }}</span>
    </nav>

    @php
        $nEng = $summary['engines'];
        $dash = '<span class="na">–</span>';
        $drive = $spec ? collect([$spec->drivetrain, $spec->transmission])->filter()->implode(', ') : '';
    @endphp

    <header class="car-intro">
        <p class="car-make">
            @include('partials.brand-logo', ['brand' => $carModel->brand, 'size' => 'sm'])
            <span>{{ $carModel->brand->name }}@if ($carModel->brand->country), {{ $carModel->brand->country }}@endif</span>
        </p>
        <h1>{{ $carModel->name }}</h1>
        <p class="car-years">
            {{ $carModel->years }}
            @if ($nEng)
                <span class="car-count">{{ number_format($nEng) }} {{ \Illuminate\Support\Str::plural('engine', $nEng) }} in {{ number_format($summary['generations']) }} {{ \Illuminate\Support\Str::plural('generation', $summary['generations']) }}</span>
            @endif
        </p>
    </header>

    {{-- Panggung: foto di tengah, spesifikasi di kiri dan kanan. Gambar = PNG tanpa background dari `php artisan cars:cutout` (sumber foto: public/images/source/). --}}
    @php $img = $carModel->stage_image; @endphp
    <script>
        /* Animasi tampil: foto dan angka muncul setelah foto selesai dimuat. Tanpa JS semuanya langsung terlihat. */
        (function () {
            var root = document.documentElement;
            root.classList.add('js');
            document.addEventListener('DOMContentLoaded', function () {
                var stage = document.querySelector('.stage');
                if (!stage) { return; }
                var photo = stage.querySelector('.stage-car img');
                var go = function () {
                    if (stage.classList.contains('is-ready')) { return; }
                    requestAnimationFrame(function () { stage.classList.add('is-ready'); });
                };
                if (!photo || (photo.complete && photo.naturalWidth)) { go(); return; }
                photo.addEventListener('load', go);
                photo.addEventListener('error', go);
                setTimeout(go, 3000);
            });
        })();
    </script>
    <section class="stage" aria-label="{{ $carModel->full_name }} at a glance" @if ($img) style="--stage-w: {{ $img['width'] }}px" @endif>
        <dl class="spec-col spec-col--l">
            <div class="spec-item">
                <dt>Power</dt>
                <dd>@if ($spec?->power_hp) {{ number_format($spec->power_hp) }}<small>hp</small><span class="spec-sub">{{ number_format(round($spec->power_hp * 0.7457)) }} kW</span> @else {!! $dash !!} @endif</dd>
            </div>
            <div class="spec-item">
                <dt>Torque</dt>
                <dd>@if ($spec?->torque_nm) {{ number_format($spec->torque_nm) }}<small>Nm</small><span class="spec-sub">{{ number_format(round($spec->torque_nm * 0.737562)) }} lb-ft</span> @else {!! $dash !!} @endif</dd>
            </div>
            <div class="spec-item">
                <dt>0 to 100 km/h</dt>
                <dd>@if ($spec?->zero_to_100_s) {{ number_format($spec->zero_to_100_s, 1) }}<small>s</small> @else {!! $dash !!} @endif</dd>
            </div>
            <div class="spec-item">
                <dt>Economy</dt>
                <dd>@if ($spec?->fuel_economy_combined_l100) {{ number_format($spec->fuel_economy_combined_l100, 1) }}<small>L/100 km</small> @else {!! $dash !!} @endif</dd>
            </div>
        </dl>

        <figure class="stage-car {{ $img ? '' : 'is-empty' }} {{ ! empty($img['cutout']) ? 'is-cutout' : '' }}" @if ($img) style="--ratio: {{ $img['ratio'] }}" @endif>
            @if ($img)
                <img src="{{ $img['url'] }}" alt="{{ $carModel->full_name }}" decoding="async" fetchpriority="high">
            @else
                <figcaption>
                    <strong>{{ $carModel->name }}</strong>
                    @if (config('cars.show_images'))
                        <span>Photo coming soon</span>
                        @if (config('app.debug'))
                            <code>public/images/source/{{ $carModel->stage_slug }}.jpg</code>
                        @endif
                    @endif
                </figcaption>
            @endif
        </figure>

        <dl class="spec-col spec-col--r">
            <div class="spec-item">
                <dt>Top speed</dt>
                <dd>@if ($spec?->top_speed_kmh) {{ number_format($spec->top_speed_kmh) }}<small>km/h</small><span class="spec-sub">{{ number_format(round($spec->top_speed_kmh * 0.621371)) }} mph</span> @else {!! $dash !!} @endif</dd>
            </div>
            <div class="spec-item">
                <dt>Fuel</dt>
                <dd class="spec-text">
                    @forelse ($summary['fuels'] as $fuel)
                        @include('partials.fuel-tag', ['fuel' => $fuel])
                    @empty
                        {!! $dash !!}
                    @endforelse
                </dd>
            </div>
            <div class="spec-item">
                <dt>Drive and gearbox</dt>
                <dd class="spec-text">{{ $drive ?: '–' }}</dd>
            </div>
            <div class="spec-item">
                <dt>Price from</dt>
                <dd>
                    @if ($summary['min_price'])
                        ${{ number_format($summary['min_price']) }}<span class="spec-sub">{{ \App\Models\Engine::idrLabel($summary['min_price']) }}</span>
                    @else
                        {!! $dash !!}
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    @if ($spec)
        <p class="stage-note">Figures shown for the {{ $spec->label }}, the most powerful engine on record. All engines are listed below.</p>
    @endif

    @if ($carModel->description)
        <p class="car-desc">{{ $carModel->description }}</p>
    @endif

    <section aria-labelledby="gens-title">
        <div class="block-head">
            <h2 id="gens-title">Generations and engines</h2>
            <p class="muted">Tick engines to compare them. Your selection stays as you browse.</p>
        </div>

        @forelse ($carModel->generations as $gen)
            @php
                $count = $gen->engines->count();
                $top = (float) $gen->engines->max('power_hp');
            @endphp
            <details class="gen" @if ($loop->first) open @endif>
                <summary>
                    <span class="gen-name">{{ $gen->name }}</span>
                    <span class="gen-years">{{ $gen->years }}</span>
                    <span class="gen-count">{{ $count }} {{ \Illuminate\Support\Str::plural('engine', $count) }}</span>
                </summary>
                <div class="table-wrap">
                    <table class="spec">
                        <thead>
                            <tr>
                                <th scope="col"><span class="sr-only">Compare</span></th>
                                <th scope="col">Engine</th>
                                <th scope="col">Fuel</th>
                                <th scope="col" class="num">Power</th>
                                <th scope="col" class="num">Torque</th>
                                <th scope="col" class="num">0 to 100</th>
                                <th scope="col" class="num">Top speed</th>
                                <th scope="col" class="num">Economy</th>
                                <th scope="col">Gearbox</th>
                                <th scope="col">Drive</th>
                                <th scope="col" class="num">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gen->engines as $e)
                                @php $isTopPower = $count > 1 && $top > 0 && (float) $e->power_hp === $top; @endphp
                                <tr @if ($isTopPower) class="is-top-row" @endif>
                                    <td class="pick">
                                        <input type="checkbox" class="js-compare" id="cmp-{{ $e->id }}"
                                               data-id="{{ $e->id }}"
                                               data-name="{{ $carModel->full_name }}, {{ $e->label }}"
                                               aria-label="Compare {{ $e->label }}">
                                    </td>
                                    <th scope="row" class="eng">
                                        <label for="cmp-{{ $e->id }}">{{ $e->label }}</label>
                                        @if ($isTopPower)<span class="sr-only">Most powerful in this generation</span>@endif
                                    </th>
                                    <td>@include('partials.fuel-tag', ['fuel' => $e->fuel_type])</td>
                                    <td class="num">
                                        @if ($e->power_hp) {{ number_format($e->power_hp) }} hp @else <span class="na">–</span> @endif
                                    </td>
                                    <td class="num">@if ($e->torque_nm) {{ number_format($e->torque_nm) }} Nm @else <span class="na">–</span> @endif</td>
                                    <td class="num">@if ($e->zero_to_100_s) {{ number_format($e->zero_to_100_s, 1) }} s @else <span class="na">–</span> @endif</td>
                                    <td class="num">@if ($e->top_speed_kmh) {{ number_format($e->top_speed_kmh) }} km/h @else <span class="na">–</span> @endif</td>
                                    <td class="num">@if ($e->fuel_economy_combined_l100) {{ number_format($e->fuel_economy_combined_l100, 1) }} L/100 km @else <span class="na">–</span> @endif</td>
                                    <td class="wrapcell">{{ $e->transmission ?: '–' }}</td>
                                    <td class="wrapcell">{{ $e->drivetrain ?: '–' }}</td>
                                    <td class="num">
                                        @if ($e->price_usd)
                                            {{ $e->price_label }}<small class="idr">{{ $e->price_idr_label }}</small>
                                        @else
                                            <span class="na">Not listed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div class="empty">
                <h3>No engine data yet</h3>
                <p>This model has no generations or engines on record.</p>
            </div>
        @endforelse
    </section>

    @if ($related->isNotEmpty())
        <section class="block" aria-labelledby="more-title">
            <div class="block-head">
                <h2 id="more-title">More from {{ $carModel->brand->name }}</h2>
                <a href="{{ route('brands.show', $carModel->brand) }}">All {{ $carModel->brand->name }} models</a>
            </div>
            @include('partials.car-rows', ['cars' => $related])
        </section>
    @endif
</div>
@endsection
