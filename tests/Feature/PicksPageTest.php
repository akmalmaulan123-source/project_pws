<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Halaman Top picks dan pengalihan dari alamat lama. */
class PicksPageTest extends TestCase
{
    use RefreshDatabase;

    private function car(int $brandId, string $name, float $hp, ?float $zero, ?int $price, string $fuel = 'Gasoline'): void
    {
        $now = ['created_at' => now(), 'updated_at' => now()];
        $modelId = DB::table('car_models')->insertGetId(['brand_id' => $brandId, 'name' => $name] + $now);
        $genId = DB::table('generations')->insertGetId(['car_model_id' => $modelId, 'name' => 'Gen 1'] + $now);
        DB::table('engines')->insert([
            'generation_id' => $genId, 'label' => 'Engine', 'fuel_type' => $fuel,
            'power_hp' => $hp, 'zero_to_100_s' => $zero, 'top_speed_kmh' => 250, 'price_usd' => $price,
        ] + $now);
    }

    private function seedCars(): void
    {
        $brandId = DB::table('brands')->insertGetId([
            'name' => 'Testla', 'slug' => 'testla', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->car($brandId, 'Roadster', 800, 2.8, 200000);
        $this->car($brandId, 'Hatchy', 250, 6.0, 25000);
        $this->car($brandId, 'Zapper', 400, 4.0, 60000, 'Electric');
        $this->car($brandId, 'Mystery', 500, 3.5, null);
    }

    public function test_halaman_menampilkan_semua_bagian_dan_mobilnya(): void
    {
        $this->seedCars();

        $this->get('/top-picks')
            ->assertOk()
            ->assertSee('Top')
            ->assertSee('Best value')
            ->assertSee('Quickest under $100,000', false)
            ->assertSee('Best hybrid &amp; electric', false)
            ->assertSee('Roadster')
            ->assertSee('Hatchy')
            ->assertSee('Zapper')
            ->assertDontSee('data-pk-tab', false);
    }

    public function test_mobil_tanpa_harga_tidak_masuk_daftar_harga(): void
    {
        $this->seedCars();

        $this->get('/top-picks')->assertOk()->assertDontSee('Mystery');
    }

    public function test_tautan_semua_mengarah_ke_filter_di_halaman_cars(): void
    {
        $this->seedCars();

        $this->get('/top-picks')
            ->assertOk()
            ->assertSee('max_price=100000', false)
            ->assertSee('fuel_type=Electric', false);
    }

    public function test_tanpa_data_menampilkan_pesan_kosong(): void
    {
        $this->get('/top-picks')->assertOk()->assertSee('No picks yet');
    }

    public function test_alamat_insights_lama_dialihkan_permanen(): void
    {
        $this->get('/insights')->assertStatus(301)->assertRedirect(route('picks'));
    }

    public function test_menu_menampilkan_top_picks(): void
    {
        $this->get('/about')->assertOk()->assertSee('Top picks')->assertDontSee('Insights');
    }
}
