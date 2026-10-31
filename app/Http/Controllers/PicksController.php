<?php

namespace App\Http\Controllers;

use App\Models\CarModel;
use App\Models\Engine;
use App\Support\TopPicks;
use Illuminate\Support\Facades\Cache;

/** Halaman "Top picks": daftar pilihan siap pakai yang membantu orang memilih mobil. */
class PicksController extends Controller
{
    public function __invoke()
    {
        // Hanya id dan angka yang di-cache; modelnya dimuat per kunjungan (satu query kecil) supaya tidak basi.
        $picks = Cache::remember('picks.data.v2', 600, fn () => TopPicks::build(
            Engine::query()
                ->join('generations', 'generations.id', '=', 'engines.generation_id')
                ->get([
                    'generations.car_model_id as model_id',
                    'generations.year_start as gen_year',
                    'engines.fuel_type',
                    'engines.power_hp',
                    'engines.zero_to_100_s as zero',
                    'engines.top_speed_kmh as top',
                    'engines.price_usd as price',
                ])
                ->map(fn ($r) => $r->getAttributes())
                ->all()
        ));

        return view('picks.index', [
            'picks' => $picks,
            'models' => CarModel::with('brand')->whereIn('id', TopPicks::ids($picks))->get()->keyBy('id'),
            'totalModels' => Cache::remember('opt.total_models', 600, fn () => CarModel::count()),
            'valueMinHp' => TopPicks::VALUE_MIN_HP,
            'budgetCap' => TopPicks::BUDGET_CAP,
            'electrifiedCap' => TopPicks::ELECTRIFIED_CAP,
            'minGenYear' => TopPicks::MIN_GEN_YEAR,
        ]);
    }
}
