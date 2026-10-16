@extends('layouts.app')
@section('title', 'Page not found')
@section('content')
    <div class="wrap page">
        <div class="err err--silver">
            <div class="err-flag" aria-hidden="true"></div>
            <p class="err-code">404</p>
            <h1>Off the racing line</h1>
            <p class="err-text">The page you asked for does not exist or has moved. Head back to the garage and pick another car.</p>
            <p class="err-actions">
                <a class="btn" href="{{ route('cars.index') }}">Browse all cars</a>
                <a class="btn btn-quiet" href="{{ route('home') }}">Back to home</a>
            </p>
        </div>
    </div>
@endsection
