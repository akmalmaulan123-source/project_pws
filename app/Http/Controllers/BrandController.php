<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
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

    /**
     * Halaman "garage": satu mobil ditampilkan lengkap (spesifikasi + semua generasi dan mesin),
     * sedangkan model lain dari merek yang sama ada di deretan bawah. Mobil dipilih lewat ?car={id}.
     */
    public function show(Request $request, Brand $brand)
    {
        $models = $brand->carModels()
            ->withCount('engines')
            ->withMax('engines', 'power_hp')
                        ->orderByRaw('year_start IS NULL')->orderByDesc('year_start')->orderBy('name')
            ->get();

        // Merek sudah dimuat: tidak perlu query ulang per model, dan URL /cars/{id}-{slug} tetap benar.
        $models->each(fn (CarModel $m) => $m->setRelation('brand', $brand));

        $selected = $spec = $summary = $stats = null;

        if ($models->isNotEmpty()) {
            // Tanpa ?car yang valid: model terbaru yang sudah punya data mesin.
            $selected = $models->firstWhere('id', $request->integer('car'))
                ?? $models->first(fn (CarModel $m) => $m->engines_count > 0)
                ?? $models->first();

            $selected->load('generations.engines');

            $engines = $selected->generations->flatMap->engines;
            $summary = [
                'engines' => $engines->count(),
                'generations' => $selected->generations->count(),
                'cheapest' => $engines->whereNotNull('price_usd')->sortBy('price_usd')->first(),
                'fuels' => $engines->pluck('fuel_type')->filter()->unique()->values(),
            ];

            // Mesin terkuat (kalau tenaga sama, yang datanya top speed ada), sama seperti halaman detail mobil.
            $spec = $engines->whereNotNull('power_hp')
                ->sortBy([['power_hp', 'desc'], ['top_speed_kmh', 'desc']])
                ->first() ?? $engines->first();

            $stats = $this->stats($brand, $spec);
        }

        return view('brands.show', compact('brand', 'models', 'selected', 'spec', 'summary', 'stats'));
    }

    /**
     * Empat angka utama + panjang batang. Batang dibandingkan dengan nilai terbaik di seluruh mesin merek ini,
     * jadi batang penuh (dan warna ungu) berarti yang terbaik di merek tersebut.
     */
    private function stats(Brand $brand, ?Engine $spec): array
    {
        $best = Engine::query()
            ->join('generations', 'generations.id', '=', 'engines.generation_id')
            ->join('car_models', 'car_models.id', '=', 'generations.car_model_id')
            ->where('car_models.brand_id', $brand->id)
            ->selectRaw('MAX(engines.power_hp) AS power, MAX(engines.torque_nm) AS torque, '
                .'MIN(CASE WHEN engines.zero_to_100_s > 0 THEN engines.zero_to_100_s END) AS zero, '
                .'MAX(engines.top_speed_kmh) AS top')
            ->first();

        // [label, nilai, satuan, nilai terbaik, lebih kecil lebih baik?, jumlah desimal]
        $defs = [
            ['Power', $spec?->power_hp, 'hp', $best?->power, false, 0],
            ['Torque', $spec?->torque_nm, 'Nm', $best?->torque, false, 0],
            ['0 to 100 km/h', $spec?->zero_to_100_s, 's', $best?->zero, true, 1],
            ['Top speed', $spec?->top_speed_kmh, 'km/h', $best?->top, false, 0],
        ];

        return array_map(function (array $d) {
            [$label, $value, $unit, $top, $lowerBetter, $dec] = $d;
            $value = $value ? (float) $value : null;
            $top = $top ? (float) $top : null;

            $pct = 0;
            $isBest = false;
            if ($value && $top) {
                $pct = $lowerBetter ? $top / $value * 100 : $value / $top * 100;
                $isBest = $lowerBetter ? $value <= $top : $value >= $top;
            }

            return [
                'label' => $label,
                'value' => $value !== null ? number_format($value, $dec) : null,
                'unit' => $unit,
                'pct' => $value ? round(max(4, min(100, $pct)), 1) : 0,
                'best' => $isBest,
            ];
        }, $defs);
    }
}
