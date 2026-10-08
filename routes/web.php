<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DevReloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
Route::get('/cars/{carModel}', [CarController::class, 'show'])->name('cars.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/compare', CompareController::class)->name('compare');

Route::get('/search/suggest', SearchController::class)->middleware('throttle:120,1')->name('search.suggest');

// Dipanggil live.js tiap beberapa detik: mengembalikan "sidik jari" data, halaman menyegarkan diri kalau berubah.
Route::get('/live', LiveController::class)->middleware('throttle:120,1')->name('live');

// Hanya saat development (APP_ENV=local): auto-refresh browser tiap file proyek berubah. Lihat public/js/devreload.js.
if (app()->environment('local')) {
    Route::get('/__dev-reload', DevReloadController::class)->name('dev.reload');
}
