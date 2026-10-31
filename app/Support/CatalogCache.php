<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Semua kunci cache katalog di satu tempat.
 * Dulu kunci ditulis terpisah di provider, controller, dan command, lalu ada yang tertinggal versi lama
 * (home.feature.v2 vs v3) sehingga cache tidak pernah terhapus. Ubah kunci di sini saja.
 */
class CatalogCache
{
    public const HOME_FEATURE = 'home.feature.v3';

    public const KEYS = [
        'home.stats',
        'about.stats',
        self::HOME_FEATURE,
        'home.fuels',
        'picks.data.v1',
        'cars.powers',
        'opt.countries',
        'opt.power_max',
        'opt.total_models',
        'opt.has_prices',
    ];

    /** Buang semua cache katalog (dipanggil saat data merek/model/generasi/mesin berubah). */
    public static function flush(): void
    {
        Cache::deleteMultiple(self::KEYS);
    }

    /** Buang hanya mobil unggulan di hero (setelah foto/cutout berubah). */
    public static function forgetFeature(): void
    {
        Cache::forget(self::HOME_FEATURE);
    }
}
