<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'country', 'description'];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            if (blank($brand->slug) || $brand->isDirty('name') && ! $brand->isDirty('slug')) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public static function rules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($ignoreId)],
            'country' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        return $term ? $query->where('name', 'like', '%'.$term.'%') : $query;
    }
}
