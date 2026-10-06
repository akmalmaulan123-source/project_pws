@extends('layouts.app')

@section('title', 'Cars')

@section('content')
<div class="wrap page">
    <header class="page-head">
        <h1>Cars</h1>
        <p>{{ number_format($cars->total()) }} {{ \Illuminate\Support\Str::plural('model', $cars->total()) }}@if ($activeCount) match your filters @endif</p>
    </header>

    <form class="filters" action="{{ route('cars.index') }}" method="get">
        <div class="field field-wide">
            <label for="f-q">Brand or model</label>
            <input id="f-q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="For example Supra" autocomplete="off" data-suggest data-url="{{ route('search.suggest') }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-haspopup="listbox">
        </div>
        @include('partials.filter-select', ['name' => 'brand_id', 'label' => 'Brand', 'options' => $brandOptions->pluck('name', 'id')->all(), 'value' => $filters['brand_id'], 'all' => 'All brands'])
        @include('partials.filter-select', ['name' => 'country', 'label' => 'Country', 'options' => array_combine($countryOptions, $countryOptions), 'value' => $filters['country'], 'all' => 'All countries'])
        @include('partials.filter-select', ['name' => 'fuel_type', 'label' => 'Fuel', 'options' => array_combine($fuelOptions, $fuelOptions), 'value' => $filters['fuel_type'], 'all' => 'Any fuel'])
        <div class="field field-narrow">
            <label for="f-power">Min power (hp)</label>
            <div class="stepper" data-stepper data-step="50" data-min="0" data-max="3000">
                <button type="button" class="step-btn" data-dir="-1" aria-label="Decrease minimum power" tabindex="-1">&minus;</button>
                <input id="f-power" name="min_power" type="number" inputmode="numeric" min="0" max="3000" step="50" value="{{ $filters['min_power'] }}" placeholder="Any">
                <button type="button" class="step-btn" data-dir="1" aria-label="Increase minimum power" tabindex="-1">+</button>
            </div>
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <div class="filter-actions">
            <button type="submit" class="btn">Apply filters</button>
            @if ($activeCount || $sort !== 'brand')
                <a class="btn btn-quiet" href="{{ route('cars.index') }}">Clear</a>
            @endif
        </div>
    </form>

    @if ($cars->count())
        <div class="results-bar">
            <p class="results-count">Showing {{ number_format($cars->firstItem()) }}–{{ number_format($cars->lastItem()) }} of {{ number_format($cars->total()) }}</p>
            @include('partials.sort-menu', ['sorts' => $sorts, 'sort' => $sort])
        </div>
        @include('partials.car-rows', ['cars' => $cars])
        {{ $cars->links() }}
    @else
        <div class="empty">
            <h2>No cars match these filters</h2>
            <p>Remove a filter or lower the minimum power to see more results.</p>
            <p><a class="btn" href="{{ route('cars.index') }}">Clear all filters</a></p>
        </div>
    @endif
</div>
@endsection
