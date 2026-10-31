@extends('layouts.app')

@section('title', 'Create account')
@section('no_footer', '1')
@section('description', 'Create an account to save cars and find them again in one place.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/account.css') }}?v={{ filemtime(public_path('css/account.css')) }}">
@endpush

@section('content')
<div class="wrap page auth" data-static>
    <div class="auth-card">
        <p class="acct-eyebrow">Account</p>
        <h1>Create account</h1>
        <p class="auth-sub">It takes a few seconds. You will be signed in right away.</p>

        <form method="post" action="{{ url('/register') }}">
            @csrf
            <div class="auth-field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="80" required autofocus>
                @error('name')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                @error('email')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <p class="auth-hint">At least 8 characters.</p>
                @error('password')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="password_confirmation">Repeat password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
            </div>
            <button class="btn" type="submit">Create account</button>
        </form>

        <p class="auth-alt">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
    </div>
</div>
@endsection
