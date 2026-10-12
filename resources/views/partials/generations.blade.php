{{-- Daftar generasi dan mesin satu mobil. Butuh: $carModel (generations.engines sudah dimuat) --}}
<section aria-labelledby="gens-title">
    <div class="block-head">
        <h2 id="gens-title">Generations and engines</h2>
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
                                <th scope="row" class="eng">
                                    {{ $e->label }}
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
