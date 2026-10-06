<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;

/**
 * Saran pencarian saat mengetik (autocomplete).
 * Urutan: yang diawali kata yang diketik lebih dulu, lalu yang hanya mengandungnya.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = is_string($v = $request->query('q')) ? mb_substr(trim($v), 0, 60) : '';

        if ($q === '') {
            return response()->json(['q' => '', 'brands' => [], 'cars' => [], 'all_url' => route('cars.index')]);
        }

        $like = addcslashes($q, '%_\\');
        $starts = $like.'%';
        $contains = '%'.$like.'%';

        // Satu huruf: merek yang diawali huruf itu saja. Dua huruf atau lebih: boleh mengandung.
        $brands = Brand::query()
            ->withCount('carModels')
            ->where('name', 'like', mb_strlen($q) === 1 ? $starts : $contains)
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$starts])
            ->orderBy('name')
            ->limit(4)->get()
            ->map(fn (Brand $b) => [
                'name' => $b->name,
                'meta' => $b->car_models_count.' '.($b->car_models_count === 1 ? 'model' : 'models'),
                'url' => route('brands.show', $b),
                'logo' => $b->logo_src,
                'code' => $b->code,
                'hue' => $b->hue,
            ])->values();

        $cars = CarModel::query()
            ->select('car_models.*')
            ->join('brands', 'brands.id', '=', 'car_models.brand_id')
            ->with('brand')
            ->withMax('engines', 'power_hp')
            ->filter(['q' => $q])
            ->orderByRaw('CASE WHEN car_models.name LIKE ? THEN 0 WHEN brands.name LIKE ? THEN 1 ELSE 2 END', [$starts, $starts])
            ->orderBy('brands.name')
            ->orderBy('car_models.name')
            ->limit(8)->get()
            ->map(fn (CarModel $m) => [
                'name' => $m->full_name,
                'brand' => $m->brand->name,
                'model' => $m->name,
                'meta' => collect([
                    $m->year_start ? $m->years : null,
                    $m->engines_max_power_hp ? number_format($m->engines_max_power_hp).' hp' : null,
                ])->filter()->implode(' · '),
                'url' => route('cars.show', $m),
                'logo' => $m->brand->logo_src,
                'code' => $m->brand->code,
                'hue' => $m->brand->hue,
            ])->values();

        return response()
            ->json([
                'q' => $q,
                'brands' => $brands,
                'cars' => $cars,
                'all_url' => route('cars.index', ['q' => $q]),
            ])
            ->header('Cache-Control', 'no-store');
    }
}
