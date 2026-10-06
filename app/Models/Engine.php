<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Engine extends Model
{
    public const FIELDS = [
        'generation_id', 'label', 'fuel_type', 'cylinders', 'displacement_cc', 'power_hp', 'torque_nm',
        'transmission', 'drivetrain', 'zero_to_100_s', 'top_speed_kmh', 'fuel_economy_combined_l100',
        'length_mm', 'width_mm', 'height_mm', 'wheelbase_mm', 'curb_weight_kg', 'price_usd',
    ];

    protected $fillable = self::FIELDS;

    protected function casts(): array
    {
        return [
            'power_hp' => 'float',
            'torque_nm' => 'float',
            'zero_to_100_s' => 'float',
            'top_speed_kmh' => 'float',
            'fuel_economy_combined_l100' => 'float',
        ];
    }

    public static function rules(): array
    {
        return [
            'generation_id' => ['required', 'integer', 'exists:generations,id'],
            'label' => ['required', 'string', 'max:200'],
            'fuel_type' => ['nullable', 'string', 'max:60'],
            'cylinders' => ['nullable', 'integer', 'between:1,24'],
            'displacement_cc' => ['nullable', 'integer', 'between:0,20000'],
            'power_hp' => ['nullable', 'numeric', 'between:0,3000'],
            'torque_nm' => ['nullable', 'numeric', 'between:0,5000'],
            'transmission' => ['nullable', 'string', 'max:255'],
            'drivetrain' => ['nullable', 'string', 'max:255'],
            'zero_to_100_s' => ['nullable', 'numeric', 'between:0,60'],
            'top_speed_kmh' => ['nullable', 'numeric', 'between:0,600'],
            'fuel_economy_combined_l100' => ['nullable', 'numeric', 'between:0,60'],
            'length_mm' => ['nullable', 'integer', 'between:0,20000'],
            'width_mm' => ['nullable', 'integer', 'between:0,5000'],
            'height_mm' => ['nullable', 'integer', 'between:0,5000'],
            'wheelbase_mm' => ['nullable', 'integer', 'between:0,10000'],
            'curb_weight_kg' => ['nullable', 'integer', 'between:0,20000'],
            'price_usd' => ['nullable', 'integer', 'between:0,100000000'],
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    /** "$32,500" atau null bila harga belum diisi. */
    public function getPriceLabelAttribute(): ?string
    {
        return $this->price_usd ? '$'.number_format($this->price_usd) : null;
    }

    /** Estimasi Rupiah dari kurs tetap di config/parcferme.php, mis. "Rp 520 million". */
    public function getPriceIdrLabelAttribute(): ?string
    {
        return $this->price_usd ? self::idrLabel($this->price_usd) : null;
    }

    public static function idrLabel(int|float $usd): string
    {
        $idr = $usd * config('parcferme.usd_to_idr');

        return $idr >= 1_000_000_000
            ? 'Rp '.rtrim(rtrim(number_format($idr / 1_000_000_000, 2), '0'), '.').' billion'
            : 'Rp '.number_format(round($idr / 1_000_000)).' million';
    }
}
