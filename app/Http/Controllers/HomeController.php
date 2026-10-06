<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /** Porsche yang boleh tampil di hero, urut dari yang paling diinginkan. */
    public const FEATURE_MODELS = ['911 GT3 RS', '911 GT2 RS', '911 GT3', '918', 'Carrera GT'];

    public function index()
    {
        $ttl = 60;

        $stats = Cache::remember('home.stats', $ttl, fn () => [
            'brands' => Brand::count(),
            'models' => CarModel::count(),
            'engines' => Engine::count(),
        ]);

        // Mobil unggulan di hero: sebuah Porsche. Urutan pilihan = yang pertama punya foto;
        // kalau belum ada yang berfotokan, tetap pakai 911 GT3 RS (hero tampil tanpa foto).
        $feature = Cache::remember('home.feature.v2', 300, function () {
            $models = CarModel::query()
                ->with('brand')
                ->whereHas('brand', fn ($q) => $q->where('name', 'Porsche'))
                ->get();

            $pick = null;
            foreach (self::FEATURE_MODELS as $name) {
                $m = $models->firstWhere('name', $name);
                if ($m && $m->image_url) {
                    $pick = $m;
                    break;
                }
            }
            $pick ??= $models->firstWhere('name', self::FEATURE_MODELS[0]) ?? $models->first();

            if (! $pick) {
                return null;
            }

            // Mesin terkuat model itu; kalau tenaganya sama, pilih yang datanya top speed lengkap.
            $e = Engine::query()
                ->whereHas('generation', fn ($q) => $q->where('car_model_id', $pick->id))
                ->whereNotNull('power_hp')
                ->orderByDesc('power_hp')
                ->orderByRaw('top_speed_kmh is null')
                ->first();

            if (! $e) {
                return null;
            }

            // PNG transparan hasil `php artisan cars:cutout`; kalau belum ada, pakai foto asli.
            $cutoutFile = public_path("images/cars/{$pick->id}.png");
            $showImages = (bool) config('cars.show_images');
            $cutout = $showImages && is_file($cutoutFile)
                ? asset("images/cars/{$pick->id}.png").'?v='.filemtime($cutoutFile)
                : null;

            return [
                'car_id' => $pick->id,
                'brand' => $pick->brand->name,
                'model' => $pick->name,
                'image' => $showImages ? ($cutout ?? $pick->image_url) : null,
                'cutout' => (bool) $cutout,
                'engine' => $e->label,
                'power' => (float) $e->power_hp,
                'top_speed' => $e->top_speed_kmh ? (float) $e->top_speed_kmh : null,
                'zero_to_100' => $e->zero_to_100_s ? (float) $e->zero_to_100_s : null,
                'torque' => $e->torque_nm ? (float) $e->torque_nm : null,
                'transmission' => $e->transmission,
                'drivetrain' => $e->drivetrain,
            ];
        });

        $fuels = Cache::remember('home.fuels', $ttl, fn () => Engine::query()
            ->select('fuel_type', DB::raw('count(*) as total'))
            ->whereNotNull('fuel_type')
            ->groupBy('fuel_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['fuel' => $r->fuel_type, 'total' => (int) $r->total])->all());

        $brands = Brand::withCount('carModels')->orderByDesc('car_models_count')->orderBy('name')->limit(8)->get();

        $newest = CarModel::query()
            ->with('brand')
            ->withCount('engines')
            ->withMax('engines', 'power_hp')
            ->withMax('engines', 'top_speed_kmh')
            ->withMin('engines', 'zero_to_100_s')
            ->withMin('engines', 'price_usd')
            ->whereNotNull('year_start')
            ->orderByDesc('year_start')->orderBy('id')
            ->limit(8)->get();

        return view('home', compact('stats', 'feature', 'fuels', 'brands', 'newest'));
    }
}
