@extends('layouts.app')
@section('title', 'Page not found')
@section('content')
    <div class="wrap page">
        <div class="empty">
            <h1>Page not found</h1>
            <p>The page you asked for does not exist or has moved.</p>
            <p><a class="btn" href="{{ route('cars.index') }}">Browse all cars</a></p>
        </div>
    </div>
@endsection
