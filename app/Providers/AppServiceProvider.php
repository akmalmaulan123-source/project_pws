<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
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
        $flush = fn () => Cache::deleteMultiple(['home.stats', 'home.feature.v2', 'home.fuels', 'opt.countries', 'opt.fuels']);
        foreach ([Brand::class, CarModel::class, Generation::class, Engine::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}
