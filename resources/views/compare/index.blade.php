@extends('layouts.app')

@section('title', 'Compare engines')

@section('content')
<div class="wrap page">
    <header class="page-head">
        <h1>Compare engines</h1>
        <p>Pick up to {{ $max }} engines. Purple marks the best figure in each row.</p>
    </header>

    @if ($engines->isEmpty())
        <div class="empty">
            <h2>Nothing to compare yet</h2>
            <p>Open a car and tick the engines you want to compare. They will appear here.</p>
            <p><a class="btn" href="{{ route('cars.index') }}">Browse cars</a></p>
        </div>
    @else
        <div class="table-wrap">
            <table class="spec cmp">
                <thead>
                    <tr>
                        <td></td>
                        @foreach ($engines as $e)
                            @php $car = $e->generation->carModel; @endphp
                            <th scope="col" class="cmp-col" data-compare-col data-id="{{ $e->id }}" data-name="{{ $car->brand->name }} {{ $car->name }}, {{ $e->label }}">
                                @include('partials.brand-logo', ['brand' => $car->brand])
                                <a class="cmp-car" href="{{ route('cars.show', $car) }}">{{ $car->brand->name }} {{ $car->name }}</a>
                                <span class="cmp-eng">{{ $e->label }}</span>
                                <span class="cmp-gen">{{ $e->generation->name }}</span>
                                <a class="cmp-remove" href="{{ route('compare', ['engines' => $engines->pluck('id')->reject(fn ($i) => $i === $e->id)->implode(',')]) }}">Remove</a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as [$label, $col, $unit, $dec])
                        <tr>
                            <th scope="row" class="cmp-label">{{ $label }}</th>
                            @foreach ($engines as $e)
                                @php
                                    $v = $e->{$col};
                                    $isBest = isset($best[$col]) && $v !== null && (float) $v === (float) $best[$col];
                                @endphp
                                <td class="{{ $isBest ? 'is-best' : '' }}">
                                    @if ($v === null || $v === '')
                                        <span class="na">–</span>
                                    @elseif ($col === 'price_usd')
                                        {{ $e->price_label }}<small class="idr">{{ $e->price_idr_label }}</small>
                                    @elseif (is_numeric($v))
                                        {{ number_format((float) $v, $dec) }} {{ $unit }}
                                    @else
                                        {{ $v }}
                                    @endif
                                    @if ($isBest)<span class="sr-only"> (best)</span>@endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="cmp-actions">
            <a class="btn btn-quiet" href="{{ route('cars.index') }}">Add more engines</a>
            <a class="btn btn-quiet js-clear-compare" href="{{ route('compare') }}">Clear comparison</a>
        </p>
    @endif
</div>
@endsection
