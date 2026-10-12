<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use App\Support\Fuel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Halaman Insights: statistik katalog yang dihitung langsung dari database. */
class InsightsController extends Controller
{
    public function __invoke()
    {
        // Pendek: live update menyegarkan halaman tiap data berubah.
        $data = Cache::remember('insights.data.v1', 5, fn () => $this->build());

        return view('insights.index', $data);
    }

    private function build(): array
    {
        $stats = [
            'models' => CarModel::count(),
            'engines' => Engine::count(),
            'brands' => Brand::count(),
            'peak' => (int) round((float) Engine::max('power_hp')),
        ];

        // 1) Jumlah model per brand (8 teratas).
        $topBrands = Brand::withCount('carModels')
            ->orderByDesc('car_models_count')->orderBy('name')->limit(8)->get()
            ->map(fn ($b) => ['name' => $b->name, 'count' => (int) $b->car_models_count])->all();

        // 2) Jumlah mesin per kelompok bahan bakar.
        $fuel = ['Gasoline' => 0, 'Diesel' => 0, 'Hybrid' => 0, 'Electric' => 0, 'Other' => 0];
        Engine::query()
            ->select('fuel_type', DB::raw('count(*) as total'))
            ->whereNotNull('fuel_type')->groupBy('fuel_type')->get()
            ->each(function ($r) use (&$fuel) {
                if ($g = Fuel::group($r->fuel_type)) {
                    $fuel[$g] += (int) $r->total;
                }
            });

        // 3) Rata-rata tenaga mesin per negara asal brand (7 teratas).
        $countries = Engine::query()
            ->join('generations', 'generations.id', '=', 'engines.generation_id')
            ->join('car_models', 'car_models.id', '=', 'generations.car_model_id')
            ->join('brands', 'brands.id', '=', 'car_models.brand_id')
            ->where('engines.power_hp', '>', 0)
            ->whereNotNull('brands.country')
            ->groupBy('brands.country')
            ->selectRaw('brands.country as country, AVG(engines.power_hp) as avg_hp, COUNT(DISTINCT brands.id) as brand_count, MIN(brands.name) as brand_name')
            ->orderByDesc('avg_hp')->limit(7)->get()
            ->map(fn ($r) => [
                'country' => $r->country,
                'avg' => (int) round((float) $r->avg_hp),
                'brands' => (int) $r->brand_count,
                'brand' => $r->brand_name,
            ])->all();

        // 4) Tenaga tertinggi tiap dekade (mulai 1960-an), dari tahun awal generasi.
        $decades = Generation::query()
            ->join('engines', 'engines.generation_id', '=', 'generations.id')
            ->whereNotNull('generations.year_start')
            ->where('generations.year_start', '>=', 1960)
            ->where('engines.power_hp', '>', 0)
            ->selectRaw('(generations.year_start - (generations.year_start % 10)) as decade, MAX(engines.power_hp) as peak')
            ->groupBy('decade')->orderBy('decade')->get()
            ->map(fn ($r) => ['decade' => (int) $r->decade, 'peak' => (int) round((float) $r->peak)])->all();

        return compact('stats', 'topBrands', 'fuel', 'countries', 'decades');
    }
}
