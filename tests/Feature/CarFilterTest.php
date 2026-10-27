<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Filter budget (max_price) di halaman /cars: dibandingkan dengan harga termurah tiap model ("From"). */
class CarFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $brandId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brandId = DB::table('brands')->insertGetId([
            'name' => 'Testla', 'slug' => 'testla', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Satu model dengan satu mesin per harga (null = mesin tanpa harga). */
    private function car(string $name, array $prices): void
    {
        $now = ['created_at' => now(), 'updated_at' => now()];
        $modelId = DB::table('car_models')->insertGetId(['brand_id' => $this->brandId, 'name' => $name] + $now);
        $genId = DB::table('generations')->insertGetId(['car_model_id' => $modelId, 'name' => 'Gen 1'] + $now);

        foreach ($prices as $i => $price) {
            DB::table('engines')->insert([
                'generation_id' => $genId, 'label' => 'Engine '.$i, 'power_hp' => 300, 'price_usd' => $price,
            ] + $now);
        }
    }

    private function seedCars(): void
    {
        $this->car('Cheapo', [30000]);
        $this->car('Splitty', [50000, 300000]);   // termurah $50 ribu, jadi lolos budget $100 ribu
        $this->car('Luxo', [900000]);
        $this->car('Priceless', [null]);
    }

    public function test_budget_menyaring_berdasarkan_harga_termurah_model(): void
    {
        $this->seedCars();

        $this->get('/cars?max_price=100000')
            ->assertOk()
            ->assertSee('Cheapo')
            ->assertSee('Splitty')
            ->assertDontSee('Luxo')
            ->assertDontSee('Priceless');
    }

    public function test_budget_besar_memuat_semua_model_yang_punya_harga(): void
    {
        $this->seedCars();

        $this->get('/cars?max_price=1000000')
            ->assertOk()
            ->assertSee('Luxo')
            ->assertDontSee('Priceless');
    }

    public function test_tanpa_budget_semua_model_tampil(): void
    {
        $this->seedCars();

        $this->get('/cars')->assertOk()->assertSee('Luxo')->assertSee('Priceless');
    }

    public function test_budget_tidak_valid_diabaikan(): void
    {
        $this->seedCars();

        foreach (['abc', '0', '-5', '999999999999'] as $bad) {
            $this->get('/cars?max_price='.$bad)->assertOk()->assertSee('Luxo')->assertSee('Priceless');
        }
    }

    public function test_slider_budget_tampil_bila_ada_harga(): void
    {
        $this->seedCars();

        $this->get('/cars')->assertOk()->assertSee('id="f-price"', false)->assertSee('name="max_price"', false);
    }

    public function test_slider_budget_disembunyikan_bila_katalog_belum_punya_harga(): void
    {
        $this->car('Plain', [null]);

        $this->get('/cars')->assertOk()->assertDontSee('id="f-price"', false);
    }
}
