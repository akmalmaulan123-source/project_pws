<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarModel extends Model
{
    protected $fillable = ['brand_id', 'name', 'year_start', 'year_end', 'description', 'image_url', 'image_credit'];

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

        return $this->year_start.' – '.($this->year_end ?: 'present');
    }

    /** Nama file foto halaman detail, mis. "porsche-911-gt3-rs" (sama dengan slug di URL). */
    public function getStageSlugAttribute(): string
    {
        return Str::slug($this->brand?->name.' '.$this->name);
    }

    /**
     * PNG tanpa background hasil `php artisan cars:cutout` (public/images/cars/{id}.png); null bila belum dibuat.
     * Ini satu-satunya gambar mobil yang dipakai di seluruh situs (hero, kartu, halaman detail).
     */
    public function getCutoutAttribute(): ?array
    {
        if (! config('cars.show_images')) {
            return null;
        }

        $path = "images/cars/{$this->id}.png";
        $file = public_path($path);

        if (! is_file($file)) {
            return null;
        }

        return $this->imageInfo($path, $file, true);
    }

    /** Gambar untuk kartu katalog: PNG tanpa background kalau ada, kalau belum pakai foto asli. */
    public function getCardImageAttribute(): ?array
    {
        if (! config('cars.show_images')) {
            return null;
        }

        if ($c = $this->cutout) {
            return ['url' => $c['url'], 'cutout' => true];
        }

        return $this->image_url ? ['url' => $this->image_url, 'cutout' => false] : null;
    }

    /**
     * Gambar halaman detail: PNG tanpa background kalau ada. Foto lama di public/images/stage/{slug}.{ext}
     * masih terbaca sebagai cadangan, tapi sebaiknya dipindah ke public/images/source/ lalu di-cutout.
     * Rasio dibaca dari file supaya kotak foto mengikuti bentuk aslinya; foto lebar mendapat kolom lebih lebar.
     */
    public function getStageImageAttribute(): ?array
    {
        if (! config('cars.show_images')) {
            return null;
        }

        if ($c = $this->cutout) {
            return $c;
        }

        foreach (['webp', 'avif', 'jpg', 'jpeg', 'png'] as $ext) {
            $path = "images/stage/{$this->stage_slug}.{$ext}";
            $file = public_path($path);

            if (is_file($file)) {
                return $this->imageInfo($path, $file, false);
            }
        }

        return null;
    }

    private function imageInfo(string $path, string $file, bool $cutout): array
    {
        $size = @getimagesize($file);
        $ratio = $size && $size[1] > 0 ? $size[0] / $size[1] : 0.8;
        $ratio = max(0.7, min(2.0, $ratio));

        return [
            'url' => asset($path).'?v='.filemtime($file),
            'ratio' => round($ratio, 3),
            'width' => (int) round(min(700, max(480, 480 + ($ratio - 1) * 440))),
            'cutout' => $cutout,
        ];
    }

    /** Filter katalog: q, brand_id, country, fuel_type, min_power */
    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['q'] ?? null, function ($q, $v) {
                // Tiap kata harus cocok dengan nama model ATAU nama merek, mis. "porsche 911" atau "ferrari f40".
                foreach (preg_split('/\s+/', trim($v), -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $like = '%'.addcslashes($term, '%_\\').'%';
                    $q->where(fn ($w) => $w->where('car_models.name', 'like', $like)
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like)));
                }
            })
            ->when($f['brand_id'] ?? null, fn ($q, $v) => $q->where('car_models.brand_id', $v))
            ->when($f['country'] ?? null, fn ($q, $v) => $q->whereHas('brand', fn ($b) => $b->where('country', $v)))
            ->when($f['fuel_type'] ?? null, fn ($q, $v) => $q->whereHas('engines', fn ($e) => $e->where('engines.fuel_type', $v)))
            ->when($f['min_power'] ?? null, fn ($q, $v) => $q->whereHas('engines', fn ($e) => $e->where('engines.power_hp', '>=', $v)));
    }

    /** URL: /cars/123-honda-civic (angka di depan yang dipakai untuk mencari data). */
    public function getRouteKey(): mixed
    {
        return $this->getKey().'-'.Str::slug(($this->relationLoaded('brand') ? $this->brand->name.' ' : '').$this->name);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->with('brand')->findOrFail((int) $value);
    }
}
