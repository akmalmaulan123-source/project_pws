@extends('layouts.app')

@section('title', 'Brands')

@section('content')
<div class="wrap page">
    <header class="page-head">
        <h1>Brands</h1>
        <p>{{ number_format($brands->count()) }} {{ \Illuminate\Support\Str::plural('brand', $brands->count()) }}</p>
    </header>

    <form class="filters filters-short" action="{{ route('brands.index') }}" method="get">
        <div class="field field-wide">
            <label for="b-q">Name</label>
            <input id="b-q" name="q" type="search" value="{{ $q }}" placeholder="For example Toyota">
        </div>
        @include('partials.filter-select', ['name' => 'country', 'label' => 'Country', 'options' => array_combine($countries, $countries), 'value' => $country, 'all' => 'All countries'])
        <input type="hidden" name="sort" value="{{ $sort }}">
        <div class="filter-actions">
            <button type="submit" class="btn">Apply filters</button>
            @if ($q !== '' || $country !== '' || $sort !== 'name')
                <a class="btn btn-quiet" href="{{ route('brands.index') }}">Clear</a>
            @endif
        </div>
    </form>

    @if ($brands->count())
        <div class="results-bar">
            <p class="results-count">{{ number_format($brands->count()) }} {{ \Illuminate\Support\Str::plural('brand', $brands->count()) }}</p>
            @include('partials.sort-menu', ['sorts' => $sorts, 'sort' => $sort])
        </div>
        <ul class="make-grid make-grid-full">
            @foreach ($brands as $b)
                <li>
                    <a href="{{ route('brands.show', $b) }}">
                        @include('partials.brand-logo', ['brand' => $b])
                        <span class="make-name">{{ $b->name }}</span>
                        <span class="make-count">{{ $b->country ?: 'Country unknown' }}, {{ $b->car_models_count }} {{ \Illuminate\Support\Str::plural('model', $b->car_models_count) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <div class="empty">
            <h2>No brands found</h2>
            <p>Check the spelling or clear the country filter.</p>
            <p><a class="btn" href="{{ route('brands.index') }}">Show all brands</a></p>
        </div>
    @endif
</div>
@endsection
