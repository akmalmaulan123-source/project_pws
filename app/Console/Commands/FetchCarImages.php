<?php

namespace App\Console\Commands;

use App\Models\CarModel;
use App\Models\Engine;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FetchCarImages extends Command
{
    protected $signature = 'cars:fetch-images
                            {--all : Proses semua model, bukan hanya yang tampil di halaman utama}
                            {--limit=100 : Maksimal model yang diproses dalam sekali jalan}
                            {--retry : Coba lagi model yang sebelumnya tidak ketemu fotonya}';

    protected $description = 'Ambil foto mobil dari Wikipedia untuk tiap model (halaman utama lebih dulu)';

    public function handle(): int
    {
        $homeIds = $this->homepageIds();

        $query = CarModel::query()->with('brand')->whereNull('image_url');

        if (! $this->option('retry')) {
            $query->whereNull('image_checked_at');
        }

        if ($this->option('all')) {
            // Model yang tampil di halaman utama diproses paling awal.
            if ($homeIds) {
                $query->orderByRaw('id IN (' . implode(',', $homeIds) . ') DESC');
            }
            $query->orderByDesc('year_start')->orderBy('id');
        } else {
            $query->whereIn('id', $homeIds ?: [0]);
        }

        $models = $query->limit(max(1, (int) $this->option('limit')))->get();

        if ($models->isEmpty()) {
            $this->info('Tidak ada model yang perlu diproses.');

            return self::SUCCESS;
        }

        $found = 0;
        $done = 0;
        $bar = $this->output->createProgressBar($models->count());
        $bar->start();

        foreach ($models as $model) {
            try {
                $result = $this->lookup($model);
            } catch (ConnectionException $e) {
                $bar->clear();
                $this->warn("Koneksi gagal untuk {$model->full_name}, dilewati (tidak ditandai).");
                $bar->display();
                $bar->advance();

                continue;
            } catch (RuntimeException $e) {
                $this->newLine(2);
                $this->error($e->getMessage());
                $this->line("Tersimpan sebelum berhenti: {$found} foto dari {$done} model yang diproses.");
                Cache::forget('home.feature.v2');

                return self::FAILURE;
            }

            $model->forceFill([
                'image_url' => $result['url'] ?? null,
                'image_credit' => $result['credit'] ?? null,
                'image_checked_at' => now(),
            ])->save();

            $found += $result ? 1 : 0;
            $done++;
            $bar->advance();
            sleep(1); // pelan-pelan supaya tidak dibatasi Wikipedia
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai: {$found} dari {$models->count()} model mendapat foto.");

        // Halaman utama menyimpan data di cache; buang supaya foto baru langsung tampil.
        Cache::forget('home.feature.v2');
        $this->line('Cache menara halaman utama dibersihkan.');

        return self::SUCCESS;
    }

    /** ID model yang tampil di halaman utama (8 terbaru + Porsche unggulan hero). */
    private function homepageIds(): array
    {
        $newest = CarModel::query()
            ->whereNotNull('year_start')
            ->orderByDesc('year_start')->orderBy('id')
            ->limit(8)->pluck('id');

        // Porsche unggulan di hero ikut diambil fotonya lebih dulu.
        $feature = CarModel::query()
            ->whereHas('brand', fn ($q) => $q->where('name', 'Porsche'))
            ->whereIn('name', \App\Http\Controllers\HomeController::FEATURE_MODELS)
            ->pluck('id');

        return $newest->merge($feature)->unique()->map(fn($id) => (int) $id)->values()->all();
    }

    /** @return array{url: string, credit: string}|null */
    private function lookup(CarModel $model): ?array
    {
        $response = null;

        for ($try = 0; $try < 3; $try++) {
            $response = Http::withUserAgent('ParcFerme/1.0 (akmalmaulan123@gmail.com) laravel-http-client')
                ->timeout(15)
                ->get('https://en.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'generator' => 'search',
                    'gsrsearch' => "{$model->brand->name} {$model->name} car",
                    'gsrnamespace' => 0,
                    'gsrlimit' => 1,
                    'prop' => 'pageimages|info',
                    'piprop' => 'thumbnail',
                    'pithumbsize' => 800,
                    'inprop' => 'url',
                ]);

            if ($response->status() !== 429) {
                break;
            }

            $wait = min(max((int) $response->header('Retry-After'), 30), 120);
            $this->newLine();
            $this->warn("Dibatasi Wikipedia (429), menunggu {$wait} detik lalu mencoba lagi...");
            sleep($wait);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Masih dibatasi oleh Wikipedia (429). Tunggu 10-15 menit, lalu jalankan lagi.');
        }

        if (! $response->successful()) {
            return null;
        }

        $page = collect($response->json('query.pages') ?? [])->first();
        $src = $page['thumbnail']['source'] ?? null;

        if (! $page || ! $src) {
            return null;
        }

        // Hindari hasil yang salah sasaran: judul artikel harus memuat NAMA MODEL (bukan sekadar nama merek,
        // karena artikel apa pun dari merek itu akan lolos dan fotonya bisa mobil lain).
        // Nama model yang panjang dipersingkat dari belakang ("Cayenne Turbo S" -> "Cayenne").
        if (! $this->titleMatchesModel($page['title'] ?? '', $model->name)) {
            return null;
        }

        // Lewati logo/ikon (biasanya SVG).
        if (preg_match('/\.svg|logo|icon|badge/i', $src)) {
            return null;
        }

        return ['url' => $src, 'credit' => $page['fullurl'] ?? 'https://en.wikipedia.org'];
    }

    private function titleMatchesModel(string $title, string $name): bool
    {
        $norm = fn (string $v) => preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($v)));
        $title = ' '.trim($norm($title)).' ';
        $words = array_values(array_filter(explode(' ', $norm($name))));

        while ($words) {
            $needle = ' '.implode(' ', $words).' ';
            // Satu kata sangat pendek (mis. "t", "s") terlalu longgar untuk dianggap cocok.
            if (mb_strlen(implode('', $words)) >= 3 && str_contains($title, $needle)) {
                return true;
            }
            array_pop($words);
        }

        return false;
    }
}
