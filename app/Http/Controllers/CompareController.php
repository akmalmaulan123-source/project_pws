<?php

namespace App\Http\Controllers;

use App\Models\Engine;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public const MAX = 4;

    /** Baris spesifikasi: label, kolom, satuan, desimal, dan arah "terbaik" (max, min, atau null). */
    public const ROWS = [
        ['Fuel', 'fuel_type', '', 0, null],
        ['Power', 'power_hp', 'hp', 0, 'max'],
        ['Torque', 'torque_nm', 'Nm', 0, 'max'],
        ['0 to 100 km/h', 'zero_to_100_s', 's', 1, 'min'],
        ['Top speed', 'top_speed_kmh', 'km/h', 0, 'max'],
        ['Fuel economy', 'fuel_economy_combined_l100', 'L/100 km', 1, 'min'],
        ['Displacement', 'displacement_cc', 'cc', 0, null],
        ['Cylinders', 'cylinders', '', 0, null],
        ['Transmission', 'transmission', '', 0, null],
        ['Drivetrain', 'drivetrain', '', 0, null],
        ['Length', 'length_mm', 'mm', 0, null],
        ['Width', 'width_mm', 'mm', 0, null],
        ['Height', 'height_mm', 'mm', 0, null],
        ['Wheelbase', 'wheelbase_mm', 'mm', 0, null],
        ['Curb weight', 'curb_weight_kg', 'kg', 0, 'min'],
        ['Price', 'price_usd', 'USD', 0, 'min'],
    ];

    public function __invoke(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('engines')))
            ->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->take(self::MAX)->values();

        $found = Engine::with('generation.carModel.brand')->whereIn('id', $ids)->get()->keyBy('id');
        $engines = $ids->map(fn ($id) => $found->get($id))->filter()->values();

        // Tentukan nilai terbaik per baris; hanya ditandai bila ada >= 2 nilai yang berbeda.
        $best = [];
        foreach (self::ROWS as [, $col, , , $dir]) {
            if (! $dir) {
                continue;
            }
            $vals = $engines->pluck($col)->filter(fn ($v) => $v !== null && (float) $v > 0)->map(fn ($v) => (float) $v);
            if ($vals->count() >= 2 && $vals->unique()->count() > 1) {
                $best[$col] = $dir === 'max' ? $vals->max() : $vals->min();
            }
        }

        return view('compare.index', [
            'engines' => $engines,
            'rows' => self::ROWS,
            'best' => $best,
            'max' => self::MAX,
        ]);
    }
}
