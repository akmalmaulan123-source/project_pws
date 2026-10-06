<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generation extends Model
{
    protected $fillable = ['car_model_id', 'name', 'year_start', 'year_end', 'body_type'];

    public static function rules(): array
    {
        return [
            'car_model_id' => ['required', 'integer', 'exists:car_models,id'],
            'name' => ['required', 'string', 'max:150'],
            'year_start' => ['nullable', 'integer', 'between:1880,2100'],
            'year_end' => ['nullable', 'integer', 'between:1880,2100', 'gte:year_start'],
            'body_type' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function engines(): HasMany
    {
        return $this->hasMany(Engine::class)->orderBy('power_hp');
    }

    public function getYearsAttribute(): string
    {
        if (! $this->year_start) {
            return '-';
        }

        return $this->year_start.' – '.($this->year_end ?: 'present');
    }
}
