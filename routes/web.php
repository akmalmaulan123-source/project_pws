<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\HomeController;
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
