@extends('layouts.app')

@section('title', 'Sign in')
@section('description', 'Sign in to save cars and find them again in one place.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/account.css') }}?v={{ filemtime(public_path('css/account.css')) }}">
@endpush

@section('content')
<div class="wrap page auth" data-static>
    <div class="auth-card">
        <p class="acct-eyebrow">Account</p>
        <h1>Sign in</h1>
        <p class="auth-sub">Save the cars you like and find them again in one place.</p>

        <form method="post" action="{{ route('login') }}">
            @csrf
            <div class="auth-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <label class="auth-check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Keep me signed in</span>
            </label>
            <button class="btn" type="submit">Sign in</button>
        </form>

        <p class="auth-alt">New here? <a href="{{ route('register') }}">Create an account</a></p>
    </div>
</div>
@endsection
