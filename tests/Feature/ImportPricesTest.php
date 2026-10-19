<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Perintah cars:import-prices: harga per model berlaku untuk semua mesin model itu. */
class ImportPricesTest extends TestCase
{
    use RefreshDatabase;

    /** Satu merek, satu model, satu generasi, satu mesin. Mengembalikan id mesin. */
    private function seedEngine(): int
    {
        $now = ['created_at' => now(), 'updated_at' => now()];
        $brandId = DB::table('brands')->insertGetId(['name' => 'Testla', 'slug' => 'testla'] + $now);
        $modelId = DB::table('car_models')->insertGetId(['brand_id' => $brandId, 'name' => 'Roadster'] + $now);
        $genId = DB::table('generations')->insertGetId(['car_model_id' => $modelId, 'name' => 'Gen 1'] + $now);

        return DB::table('engines')->insertGetId(['generation_id' => $genId, 'label' => 'Base'] + $now);
    }

    private function csvFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'prices');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_harga_model_diterapkan_ke_mesinnya(): void
    {
        $engineId = $this->seedEngine();
        $file = $this->csvFile("make,model,price_usd\n".'Testla,Roadster,"$55,000"'."\n");

        $this->artisan('cars:import-prices', ['file' => $file])->assertExitCode(0);
        @unlink($file);

        $this->assertDatabaseHas('engines', ['id' => $engineId, 'price_usd' => 55000]);
    }

    public function test_csv_titik_koma_dari_excel_indonesia_terbaca(): void
    {
        $engineId = $this->seedEngine();
        $file = $this->csvFile("make;model;price_usd\nTestla;Roadster;55000\n");

        $this->artisan('cars:import-prices', ['file' => $file])->assertExitCode(0);
        @unlink($file);

        $this->assertDatabaseHas('engines', ['id' => $engineId, 'price_usd' => 55000]);
    }

    public function test_baris_dengan_harga_kosong_dilewati(): void
    {
        $engineId = $this->seedEngine();
        $file = $this->csvFile("make,model,price_usd,years\nTestla,Roadster,,2020-sekarang\n");

        $this->artisan('cars:import-prices', ['file' => $file])->assertExitCode(0);
        @unlink($file);

        $this->assertDatabaseHas('engines', ['id' => $engineId, 'price_usd' => null]);
    }
}
