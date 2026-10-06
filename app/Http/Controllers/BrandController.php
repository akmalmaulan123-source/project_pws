<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    private const SORTS = [
        'name' => 'Name, A to Z',
        'name_desc' => 'Name, Z to A',
        'models' => 'Most models',
    ];

    public function index(Request $request)
    {
        $text = fn (string $key, int $max) => is_string($v = $request->query($key)) ? mb_substr(trim($v), 0, $max) : '';
        $q = $text('q', 100);
        $country = $text('country', 80);
        $sort = array_key_exists($text('sort', 20), self::SORTS) ? $text('sort', 20) : 'name';

        $brands = Brand::query()
            ->withCount('carModels')
            ->when($q !== '', fn ($b) => $b->where('name', 'like', "%{$q}%"))
            ->when($country !== '', fn ($b) => $b->where('country', $country))
            ->when($sort === 'models', fn ($b) => $b->orderByDesc('car_models_count')->orderBy('name'))
            ->when($sort === 'name_desc', fn ($b) => $b->orderByDesc('name'))
            ->when($sort === 'name', fn ($b) => $b->orderBy('name'))
            ->get();

        $sorts = self::SORTS;

        $countries = Cache::remember('opt.countries', 3600, fn () => Brand::whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->all());

        return view('brands.index', compact('brands', 'q', 'country', 'countries', 'sort', 'sorts'));
    }

    public function show(Brand $brand)
    {
        $models = $brand->carModels()
            ->with('brand')
            ->withCount('engines')
            ->withMax('engines', 'power_hp')
            ->withMin('engines', 'price_usd')
            ->orderByRaw('year_start IS NULL')->orderByDesc('year_start')->orderBy('name')
            ->paginate(30);

        return view('brands.show', compact('brand', 'models'));
    }
}
