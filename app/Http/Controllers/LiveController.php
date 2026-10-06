<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoint ringan untuk polling: mengembalikan "sidik jari" data katalog.
 * Browser membandingkan nilainya dengan yang sebelumnya; kalau berbeda, halaman menyegarkan isinya sendiri.
 */
class LiveController extends Controller
{
    public function __invoke()
    {
        // Dibagi ke semua pengunjung selama 3 detik agar banyak tab tidak membebani database.
        $token = Cache::remember('live.catalog', 3, fn () => md5(implode('|', [
            Brand::count(), Brand::max('updated_at'),
            CarModel::count(), CarModel::max('updated_at'),
            Generation::count(), Generation::max('updated_at'),
            Engine::count(), Engine::max('updated_at'),
        ])));

        return response()->json(['catalog' => $token])->header('Cache-Control', 'no-store');
    }
}
