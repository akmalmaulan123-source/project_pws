@extends('layouts.app')

@section('title', 'Saved cars')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/account.css') }}?v={{ filemtime(public_path('css/account.css')) }}">
@endpush

@section('content')
<div class="wrap page saved" data-static>
    <header class="saved-head">
        <div>
            <p class="acct-eyebrow">Your list</p>
            <h1>Saved cars</h1>
            <p class="saved-sub">Signed in as {{ auth()->user()->name }}.</p>
        </div>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-quiet" type="submit">Sign out</button>
        </form>
    </header>

    @if ($cars->isEmpty())
        <div class="empty">
            <h2>Nothing saved yet</h2>
            <p>Open any car and press Save to keep it here.</p>
            <p><a class="btn" href="{{ route('cars.index') }}">Browse cars</a></p>
        </div>
    @else
        <ul class="saved-list">
            @foreach ($cars as $m)
                <li class="saved-item">
                    <a class="saved-link" href="{{ route('cars.show', $m) }}">
                        @include('partials.brand-logo', ['brand' => $m->brand, 'size' => 'sm'])
                        <span class="saved-main">
                            <span class="saved-model">{{ $m->name }}</span>
                            <span class="saved-meta">{{ $m->brand->name }}, {{ $m->years }}</span>
                        </span>
                        <span class="saved-power">
                            @if ($m->engines_max_power_hp) {{ number_format($m->engines_max_power_hp) }}<small>hp</small> @else <span class="na">–</span> @endif
                        </span>
                    </a>
                    <form method="post" action="{{ route('saved.toggle', $m) }}">
                        @csrf
                        <button class="saved-remove" type="submit" aria-label="Remove {{ $m->full_name }} from saved cars">Remove</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
