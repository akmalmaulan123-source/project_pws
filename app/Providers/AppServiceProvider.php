<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use App\Support\CatalogCache;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.parc');
        Paginator::defaultSimpleView('pagination.parc');

        // Data beranda & opsi filter disimpan di cache; buang saat katalog berubah supaya live update menampilkan data terbaru.
        $flush = fn () => CatalogCache::flush();
        foreach ([Brand::class, CarModel::class, Generation::class, Engine::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}
