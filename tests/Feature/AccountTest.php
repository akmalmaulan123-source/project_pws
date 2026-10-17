<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Daftar, masuk, keluar, dan menyimpan mobil. Butuh database, jadi memakai RefreshDatabase. */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => 'password123']);
    }

    private function makeCar(): CarModel
    {
        $brandId = DB::table('brands')->insertGetId([
            'name' => 'Testla', 'slug' => 'testla', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $carId = DB::table('car_models')->insertGetId([
            'brand_id' => $brandId, 'name' => 'Roadster', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return CarModel::findOrFail($carId);
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/saved')->assertRedirect(route('login'));
        $this->post('/cars/1/save')->assertRedirect(route('login'));
    }

    public function test_halaman_masuk_dan_daftar_tampil(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
        $this->get('/register')->assertOk()->assertSee('Create account');
    }

    public function test_daftar_membuat_akun_dan_langsung_masuk(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'Budi@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('saved.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']); // email disimpan huruf kecil
    }

    public function test_daftar_menolak_password_pendek(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_masuk_dengan_password_salah_ditolak(): void
    {
        $this->makeUser();

        $this->post('/login', ['email' => 'tester@example.com', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_masuk_dengan_data_benar_berhasil(): void
    {
        $this->makeUser();

        $this->post('/login', ['email' => 'tester@example.com', 'password' => 'password123'])
            ->assertRedirect(route('saved.index'));

        $this->assertAuthenticated();
    }

    public function test_keluar_mengakhiri_sesi(): void
    {
        $this->actingAs($this->makeUser())->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_menyimpan_lalu_menghapus_mobil(): void
    {
        $user = $this->makeUser();
        $car = $this->makeCar();

        $this->actingAs($user)->post('/cars/'.$car->id.'/save')->assertRedirect();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'car_model_id' => $car->id]);

        $this->actingAs($user)->get('/saved')->assertOk()->assertSee('Roadster');

        $this->actingAs($user)->post('/cars/'.$car->id.'/save')->assertRedirect();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'car_model_id' => $car->id]);
    }

    public function test_daftar_saved_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->makeUser())->get('/saved')->assertOk()->assertSee('Nothing saved yet');
    }
}
