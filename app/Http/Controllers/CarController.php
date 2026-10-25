<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Support\Fuel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CarController extends Controller
{
    private const SORTS = [
        'brand' => 'Brand, A to Z',
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'power' => 'Most powerful',
        'fast' => 'Quickest 0 to 100',
        'top' => 'Highest top speed',
        'price_asc' => 'Price, low to high',
        'price_desc' => 'Price, high to low',
        'variants' => 'Most engines',
    ];

    private const PER_PAGE = 48;

    public function index(Request $request)
    {
        // Parameter yang tidak valid diabaikan, bukan menimbulkan error.
        $text = fn (string $key, int $max) => is_string($v = $request->query($key)) ? mb_substr(trim($v), 0, $max) : '';
        $sort = array_key_exists($text('sort', 20), self::SORTS) ? $text('sort', 20) : 'brand';
        $filters = [
            'q' => $text('q', 100),
            'brand_id' => $request->integer('brand_id') ?: null,
            'country' => $text('country', 80) ?: null,
            'fuel_type' => $text('fuel_type', 60) ?: null,
            'min_power' => ($p = $request->integer('min_power')) > 0 && $p <= 3000 ? $p : null,
        ];

        $query = CarModel::query()
            ->select('car_models.*')
            ->join('brands', 'brands.id', '=', 'car_models.brand_id')
            ->with('brand')
            ->withCount('engines')
            ->withMax('engines', 'power_hp')
            ->withMin('engines', 'price_usd')
            ->withMin('engines', 'zero_to_100_s')
            ->withMax('engines', 'top_speed_kmh')
            ->filter($filters);

        match ($sort) {
            'newest' => $query->orderByRaw('car_models.year_start IS NULL')->orderByDesc('car_models.year_start'),
            'oldest' => $query->orderByRaw('car_models.year_start IS NULL')->orderBy('car_models.year_start'),
            'power' => $query->orderByRaw('engines_max_power_hp IS NULL')->orderByDesc('engines_max_power_hp'),
            'fast' => $query->orderByRaw('engines_min_zero_to_100_s IS NULL')->orderBy('engines_min_zero_to_100_s'),
            'top' => $query->orderByRaw('engines_max_top_speed_kmh IS NULL')->orderByDesc('engines_max_top_speed_kmh'),
            'price_asc' => $query->orderByRaw('engines_min_price_usd IS NULL')->orderBy('engines_min_price_usd'),
            'price_desc' => $query->orderByRaw('engines_min_price_usd IS NULL')->orderByDesc('engines_min_price_usd'),
            'variants' => $query->orderByDesc('engines_count'),
            default => null,
        };
        $query->orderBy('brands.name')->orderBy('car_models.name');

        $cars = $query->paginate(self::PER_PAGE)->withQueryString();

        // Bahan bakar tiap mobil di halaman ini (satu query), dikelompokkan jadi Gasoline / Diesel / Electric / Hybrid.
        $fuels = Engine::query()
            ->join('generations', 'generations.id', '=', 'engines.generation_id')
            ->whereIn('generations.car_model_id', $cars->pluck('id'))
            ->whereNotNull('engines.fuel_type')
            ->distinct()
            ->get(['generations.car_model_id as mid', 'engines.fuel_type'])
            ->groupBy('mid')
            ->map(fn ($g) => $g->pluck('fuel_type')->map(fn ($f) => Fuel::group($f))->filter()->unique()->values()->all());

        // Peringkat tenaga dari seluruh katalog (daftar tenaga per model disimpan sebentar supaya tidak dihitung tiap kunjungan).
        $powers = Cache::remember('cars.powers', 600, fn () => Engine::query()
            ->join('generations', 'generations.id', '=', 'engines.generation_id')
            ->groupBy('generations.car_model_id')
            ->selectRaw('MAX(engines.power_hp) AS p')
            ->pluck('p')->map(fn ($v) => (float) $v)->filter()->values()->all());

        $cars->getCollection()->each(function (CarModel $m) use ($fuels, $powers) {
            $hp = $m->engines_max_power_hp ? (float) $m->engines_max_power_hp : null;
            $m->setAttribute('sr', [
                'hp' => $hp ? (int) round($hp) : null,
                'zero' => $m->engines_min_zero_to_100_s ? (float) $m->engines_min_zero_to_100_s : null,
                'top' => $m->engines_max_top_speed_kmh ? (int) $m->engines_max_top_speed_kmh : null,
                'fuels' => $fuels[$m->id] ?? [],
                'rank' => $hp ? 1 + count(array_filter($powers, fn ($p) => $p > $hp)) : null,
                'price' => $m->engines_min_price_usd ? (int) $m->engines_min_price_usd : null,
                'idr' => $m->engines_min_price_usd ? Engine::idrLabel((int) $m->engines_min_price_usd) : null,
                'cls' => self::tier($hp)[0],
                'clr' => self::tier($hp)[1],
                'url' => route('brands.show', ['brand' => $m->brand, 'car' => $m->id]),
            ]);
        });

        return view('cars.index', [
            'cars' => $cars,
            'filters' => $filters,
            'sort' => $sort,
            'sorts' => collect(self::SORTS)
                ->reject(fn ($label, $key) => str_starts_with($key, 'price') && ! Cache::remember('opt.has_prices', 600, fn () => Engine::whereNotNull('price_usd')->exists()))
                ->all(),
            'brandStrip' => Brand::withCount('carModels')->orderBy('name')->get(),
            'powerMax' => Cache::remember('opt.power_max', 600, fn () => (int) Engine::max('power_hp')),
            'totalModels' => Cache::remember('opt.total_models', 600, fn () => CarModel::count()),
            'countryOptions' => Cache::remember('opt.countries', 3600, fn () => Brand::whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->all()),
            'activeCount' => collect($filters)->filter()->count(),
        ]);
    }

    /** Kelas performa ala game balap dari tenaga maksimum: [huruf, warna]. */
    private static function tier(?float $hp): array
    {
        return match (true) {
            $hp === null => ['–', '#9c9ca0'],
            $hp >= 700 => ['S', '#c98bff'],
            $hp >= 500 => ['A', '#ff5446'],
            $hp >= 300 => ['B', '#ffd21f'],
            default => ['C', '#3fcf7b'],
        };
    }

    public function show(CarModel $carModel)
    {
        $carModel->load(['brand', 'generations.engines']);

        $engines = $carModel->generations->flatMap->engines;
        $summary = [
            'engines' => $engines->count(),
            'generations' => $carModel->generations->count(),
            'max_power' => $engines->max('power_hp'),
            'min_price' => $engines->whereNotNull('price_usd')->min('price_usd'),
            'fuels' => $engines->pluck('fuel_type')->filter()->unique()->values(),
        ];

        // Spesifikasi di samping mobil: mesin terkuat (kalau tenaga sama, yang datanya top speed ada).
        $spec = $engines->whereNotNull('power_hp')
            ->sortBy([['power_hp', 'desc'], ['top_speed_kmh', 'desc']])
            ->first() ?? $engines->first();

        $related = CarModel::query()
            ->with('brand')
            ->withCount('engines')
            ->withMax('engines', 'power_hp')
            ->withMin('engines', 'price_usd')
            ->where('brand_id', $carModel->brand_id)
            ->whereKeyNot($carModel->id)
            ->orderByDesc('year_start')
            ->limit(6)->get();

        $saved = auth()->check() && auth()->user()->favorites()->whereKey($carModel->id)->exists();

        return view('cars.show', compact('carModel', 'summary', 'related', 'spec', 'saved'));
    }
}
