<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\Rule;

class CarModel extends Model
{
    protected $fillable = ['brand_id', 'name', 'year_start', 'year_end', 'description'];

    public static function rules(?int $ignoreId = null, ?int $brandId = null): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('car_models', 'name')->where('brand_id', $brandId)->ignore($ignoreId),
            ],
            'year_start' => ['nullable', 'integer', 'between:1880,2100'],
            'year_end' => ['nullable', 'integer', 'between:1880,2100', 'gte:year_start'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class)->orderByDesc('year_start');
    }

    public function engines(): HasManyThrough
    {
        return $this->hasManyThrough(Engine::class, Generation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->brand?->name.' '.$this->name);
    }

    public function getYearsAttribute(): string
    {
        if (! $this->year_start) {
            return '-';
        }

        return $this->year_start.' – '.($this->year_end ?: 'sekarang');
    }

    /** Filter katalog: q, brand_id, country, year, fuel_type, min_power */
    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('car_models.name', 'like', "%{$v}%")
                  ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$v}%"));
            }))
            ->when($f['brand_id'] ?? null, fn ($q, $v) => $q->where('car_models.brand_id', $v))
            ->when($f['country'] ?? null, fn ($q, $v) => $q->whereHas('brand', fn ($b) => $b->where('country', $v)))
            ->when($f['year'] ?? null, fn ($q, $v) => $q->where('car_models.year_start', '<=', $v)
                ->where(fn ($w) => $w->whereNull('car_models.year_end')->orWhere('car_models.year_end', '>=', $v)))
            ->when($f['fuel_type'] ?? null, fn ($q, $v) => $q->whereHas('engines', fn ($e) => $e->where('engines.fuel_type', $v)))
            ->when($f['min_power'] ?? null, fn ($q, $v) => $q->whereHas('engines', fn ($e) => $e->where('engines.power_hp', '>=', $v)));
    }
}
