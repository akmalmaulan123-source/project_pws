<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class AboutController extends Controller
{
    public function __invoke()
    {
        // Halaman ini pada dasarnya statis: kalau database sedang bermasalah, tampilkan tanpa angka, jangan error.
        try {
            $stats = Cache::remember('about.stats', 60, fn () => [
                'brands' => Brand::count(),
                'models' => CarModel::count(),
                'generations' => Generation::count(),
                'engines' => Engine::count(),
            ]);
        } catch (QueryException $e) {
            report($e);
            $stats = null;
        }

        return view('about', ['stats' => $stats]);
    }
}
