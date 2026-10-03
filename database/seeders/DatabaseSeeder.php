<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\CarDataImporter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin & user contoh (ganti password sebelum dipakai sungguhan)
        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin', 'password' => 'password',
        ])->forceFill(['is_admin' => true])->save();

        User::updateOrCreate(['email' => 'user@example.com'], [
            'name' => 'User Contoh', 'password' => 'password',
        ]);

        // Data mobil dari CSV (dilewati bila sudah ada)
        $importer = new CarDataImporter(fn ($m) => $this->command?->line($m));
        if ($importer->isEmpty()) {
            $importer->run();
        } else {
            $this->command?->warn('Data mobil sudah ada, impor dilewati. Pakai `php artisan cars:import --fresh` untuk impor ulang.');
        }
    }
}
