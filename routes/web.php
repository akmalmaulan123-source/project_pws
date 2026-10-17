<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\DevReloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SavedController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', AboutController::class)->name('about');

Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
Route::get('/cars/{carModel}', [CarController::class, 'show'])->name('cars.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/insights', InsightsController::class)->name('insights');

// Akun: daftar, masuk, keluar. Percobaan masuk dibatasi supaya password tidak bisa ditebak berulang-ulang.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/saved', [SavedController::class, 'index'])->name('saved.index');
    Route::post('/cars/{carModel}/save', [SavedController::class, 'toggle'])->name('saved.toggle');
});
// Alamat lama halaman Compare diarahkan ke Insights.
Route::redirect('/compare', '/insights');

Route::get('/search/suggest', SearchController::class)->middleware('throttle:120,1')->name('search.suggest');

// Dipanggil live.js tiap beberapa detik: mengembalikan "sidik jari" data, halaman menyegarkan diri kalau berubah.
Route::get('/live', LiveController::class)->middleware('throttle:120,1')->name('live');

// Hanya saat development (APP_ENV=local): auto-refresh browser tiap file proyek berubah. Lihat public/js/devreload.js.
if (app()->environment('local')) {
    Route::get('/__dev-reload', DevReloadController::class)->name('dev.reload');
}
