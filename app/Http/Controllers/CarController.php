<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CarController extends Controller
{
    private const SORTS = [
        'brand' => 'Brand, A to Z',
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'power' => 'Most powerful',
        'price_asc' => 'Price, low to high',
        'price_desc' => 'Price, high to low',
        'variants' => 'Most engines',
    ];

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
            ->filter($filters);

        match ($sort) {
            'newest' => $query->orderByRaw('car_models.year_start IS NULL')->orderByDesc('car_models.year_start'),
            'oldest' => $query->orderByRaw('car_models.year_start IS NULL')->orderBy('car_models.year_start'),
            'power' => $query->orderByRaw('engines_max_power_hp IS NULL')->orderByDesc('engines_max_power_hp'),
            'price_asc' => $query->orderByRaw('engines_min_price_usd IS NULL')->orderBy('engines_min_price_usd'),
            'price_desc' => $query->orderByRaw('engines_min_price_usd IS NULL')->orderByDesc('engines_min_price_usd'),
            'variants' => $query->orderByDesc('engines_count'),
            default => null,
        };
        $query->orderBy('brands.name')->orderBy('car_models.name');

        $cars = $query->paginate(30)->withQueryString();

        return view('cars.index', [
            'cars' => $cars,
            'filters' => $filters,
            'sort' => $sort,
            'sorts' => self::SORTS,
           'brandOptions' => Brand::orderBy('name')->get(['id', 'name']),
            'countryOptions' => Cache::remember('opt.countries', 3600, fn() => Brand::whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->all()),
            'fuelOptions' => Cache::remember('opt.fuels', 3600, fn() => Engine::whereNotNull('fuel_type')->distinct()->orderBy('fuel_type')->pluck('fuel_type')->all()),
            'activeCount' => collect($filters)->filter()->count(),
        ]);
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

        return view('cars.show', compact('carModel', 'summary', 'related', 'spec'));
    }
}
