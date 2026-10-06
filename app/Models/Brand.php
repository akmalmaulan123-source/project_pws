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

    /**
     * Sumber gambar logo: file lokal public/images/brands/{slug}.(svg|png|webp|jpg) diutamakan
     * (tidak hilang saat impor ulang), lalu logo_url hasil `php artisan brands:fetch-logos`.
     */
    public function getLogoSrcAttribute(): ?string
    {
        static $local = [];

        $local[$this->slug] ??= (function () {
            foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
                if (is_file(public_path("images/brands/{$this->slug}.{$ext}"))) {
                    return asset("images/brands/{$this->slug}.{$ext}");
                }
            }

            return '';
        })();

        return $local[$this->slug] ?: ($this->logo_url ?: null);
    }

    /** Kode 3 huruf untuk penanda visual, mis. TOY, MER. */
    public function getCodeAttribute(): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($this->name));

        return strtoupper(substr($clean, 0, 3));
    }

    /** Sudut warna (0-359) yang stabil per merek, untuk garis warna penanda merek. */
    public function getHueAttribute(): int
    {
        return crc32($this->name) % 360;
    }
}
