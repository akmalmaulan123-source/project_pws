@extends('layouts.app')

@section('title', $brand->name.' models')

@section('content')
<div class="wrap page">
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('brands.index') }}">Brands</a>
        <span aria-current="page">{{ $brand->name }}</span>
    </nav>

    <header class="page-head">
        <div class="brand-head">
            @include('partials.brand-logo', ['brand' => $brand, 'size' => 'lg'])
            <div>
                <h1>{{ $brand->name }}</h1>
                <p>{{ $brand->country ?: 'Country unknown' }}, {{ number_format($models->total()) }} {{ \Illuminate\Support\Str::plural('model', $models->total()) }}</p>
            </div>
        </div>
    </header>

    @if ($brand->description)
        <p class="car-desc">{{ $brand->description }}</p>
    @endif

    @if ($models->count())
        @include('partials.car-rows', ['cars' => $models])
        {{ $models->links() }}
    @else
        <div class="empty">
            <h2>No models yet</h2>
            <p>This brand has no models on record.</p>
        </div>
    @endif
</div>
@endsection
