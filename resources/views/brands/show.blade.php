<!doctype html>
<html lang="en" class="gr-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name') }} - {{ $selected ? $selected->full_name.' garage' : $brand->name.' models' }}</title>
    <meta name="description" content="{{ $selected ? 'Specs, engines and generations of the '.$selected->full_name.', plus every other '.$brand->name.' model in the garage.' : 'Car models from '.$brand->name.'.' }}">
    <meta name="theme-color" content="#0d0d0e">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
{{-- Halaman garage berdiri sendiri: tanpa navbar dan footer, hanya garage. --}}
<body class="gr-body">
<main id="main" class="gr gr-full">
    <a class="gr-back" href="{{ route('brands.index') }}">&larr; All brands</a>

    @if ($selected)
        @php
            $dash = '<span class="na">–</span>';
            $sizes = [
                ['Length', $spec?->length_mm, 'mm'], ['Width', $spec?->width_mm, 'mm'], ['Height', $spec?->height_mm, 'mm'],
                ['Wheelbase', $spec?->wheelbase_mm, 'mm'], ['Curb weight', $spec?->curb_weight_kg, 'kg'],
            ];
        @endphp
        <div class="gr-spot" aria-hidden="true"></div>

        <div class="gr-bay">
            <div class="gr-info">
                <p class="gr-make">
                    @include('partials.brand-logo', ['brand' => $brand, 'size' => 'sm'])
                    <span>{{ $brand->name }}@if ($brand->country), {{ $brand->country }}@endif</span>
                </p>
                <h1 class="gr-model">{{ $selected->name }}</h1>
                <p class="gr-yrs">
                    {{ $selected->years }}
                    @if ($summary['engines'])
                        <span>{{ number_format($summary['engines']) }} {{ \Illuminate\Support\Str::plural('engine', $summary['engines']) }} in {{ number_format($summary['generations']) }} {{ \Illuminate\Support\Str::plural('generation', $summary['generations']) }}</span>
                    @endif
                </p>

                <div class="gr-stats">
                    @foreach ($stats as $s)
                        <div class="gr-stat {{ $s['best'] ? 'is-best' : '' }}">
                            <div class="gr-stat-top">
                                <span>{{ $s['label'] }}</span>
                                <b>@if ($s['value'] !== null) {{ $s['value'] }}<small>{{ $s['unit'] }}</small> @else {!! $dash !!} @endif</b>
                            </div>
                            <div class="gr-bar"><i style="--w: {{ $s['pct'] }}%"></i></div>
                        </div>
                    @endforeach
                </div>

                <dl class="gr-specs">
                    <div><dt>Economy</dt><dd>@if ($spec?->fuel_economy_combined_l100) {{ number_format($spec->fuel_economy_combined_l100, 1) }} L/100 km @else {!! $dash !!} @endif</dd></div>
                    <div><dt>Fuel</dt><dd>@forelse ($summary['fuels'] as $fuel) @include('partials.fuel-tag', ['fuel' => $fuel]) @empty {!! $dash !!} @endforelse</dd></div>
                    <div><dt>Drive</dt><dd>{{ $spec?->drivetrain ?: '–' }}</dd></div>
                    <div><dt>Gearbox</dt><dd>{{ $spec?->transmission ?: '–' }}</dd></div>
                    @foreach ($sizes as [$label, $value, $unit])
                        <div><dt>{{ $label }}</dt><dd>@if ($value) {{ number_format($value) }} {{ $unit }} @else {!! $dash !!} @endif</dd></div>
                    @endforeach
                    <div>
                        <dt>Price from</dt>
                        <dd>@if ($summary['cheapest']) {{ $summary['cheapest']->price_label }} <small class="idr">{{ $summary['cheapest']->price_idr_label }}</small> @else <span class="na">Not listed</span> @endif</dd>
                    </div>
                </dl>

            </div>

            <div class="gr-stage">
                <div class="gr-floor" aria-hidden="true"></div>
                <div class="gr-logo" role="img" aria-label="{{ $brand->name }}">
                    @include('partials.brand-logo', ['brand' => $brand, 'size' => 'xl'])
                </div>
            </div>

            <aside class="gr-side" aria-label="Generations and engines">
                <h2 class="gr-h gr-h--first">Generations and engines</h2>
                @if ($spec)
                    <p class="gr-eng">Specs on the left are for the {{ $spec->label }}, the most powerful engine on record.</p>
                @endif
            @forelse ($selected->generations as $gen)
                <details class="gr-gen">
                    <summary>
                        <span class="gr-gen-n">{{ $gen->name }}</span>
                        <span class="gr-gen-y">{{ $gen->years }}</span>
                        <span class="gr-gen-c">{{ $gen->engines->count() }}</span>
                    </summary>
                    <ul class="gr-engs">
                        @foreach ($gen->engines as $e)
                            <li>
                                <b>{{ $e->label }}</b>
                                <span class="gr-eng-m">
                                    @include('partials.fuel-tag', ['fuel' => $e->fuel_type])
                                    <span>{{ $e->power_hp ? number_format($e->power_hp).' hp' : '–' }}</span>
                                    <span>{{ $e->torque_nm ? number_format($e->torque_nm).' Nm' : '–' }}</span>
                                    <span>{{ $e->zero_to_100_s ? number_format($e->zero_to_100_s, 1).' s' : '–' }}</span>
                                    <span>{{ $e->top_speed_kmh ? number_format($e->top_speed_kmh).' km/h' : '–' }}</span>
                                </span>
                                <span class="gr-eng-s">
                                    {{ collect([$e->transmission, $e->drivetrain, $e->fuel_economy_combined_l100 ? number_format($e->fuel_economy_combined_l100, 1).' L/100 km' : null, $e->price_usd ? $e->price_label : null])->filter()->implode(' · ') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @empty
                <p class="gr-eng">No engine data yet for this model.</p>
            @endforelse
            </aside>
        </div>

        <div class="gr-strip">
            <h2>
                <span>More from {{ $brand->name }}</span>
                <em>{{ number_format($models->count()) }} {{ \Illuminate\Support\Str::plural('model', $models->count()) }} in total</em>
            </h2>
            <div class="gr-rail" role="list">
                @foreach ($models as $m)
                    <a draggable="false" class="gr-card {{ $m->id === $selected->id ? 'is-on' : '' }}" role="listitem" @if ($m->id === $selected->id) aria-current="true" @endif
                       href="{{ route('brands.show', ['brand' => $brand, 'car' => $m->id]) }}">
                        <span class="gr-card-n">{{ $m->name }}</span>
                        <span class="gr-card-m">
                            <span>{{ $m->year_start ?: '–' }}</span>
                            <span>@if ($m->engines_max_power_hp) {{ number_format($m->engines_max_power_hp) }} hp @else – @endif</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="gr-door" aria-hidden="true"><span>GARAGE</span></div>
    @else
        <div class="gr-empty">
            @include('partials.brand-logo', ['brand' => $brand, 'size' => 'lg'])
            <h1>{{ $brand->name }}</h1>
            <p>This brand has no models on record yet.</p>
        </div>
    @endif
</main>
<script src="{{ asset('js/garage.js') }}?v={{ filemtime(public_path('js/garage.js')) }}" defer></script>
</body>
</html>
