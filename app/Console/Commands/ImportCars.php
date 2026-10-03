<?php

namespace App\Console\Commands;

use App\Support\CarDataImporter;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'cars:import', description: 'Impor dataset mobil (merek, model, generasi, mesin) dari database/data/*.csv')]
class ImportCars extends Command
{
    protected $signature = 'cars:import {--fresh : Kosongkan tabel katalog (termasuk review & favorit) sebelum impor}';

    public function handle(): int
    {
        $importer = new CarDataImporter(fn ($m) => $this->line($m));

        if ($this->option('fresh')) {
            $importer->wipe();
            $this->warn('Tabel katalog dikosongkan.');
        }

        try {
            $c = $importer->run();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($cleaned = $importer->cleanedCounts()) {
            $this->line('Nilai tidak wajar di dataset sumber yang dikosongkan (NULL):');
            foreach ($cleaned as $col => $n) {
                $this->line("  - {$col}: {$n}");
            }
        }
        $this->info("Selesai: {$c['brands']} merek, {$c['models']} model, {$c['generations']} generasi, {$c['engines']} mesin.");

        return self::SUCCESS;
    }
}
