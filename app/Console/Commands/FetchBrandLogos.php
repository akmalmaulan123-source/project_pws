<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FetchBrandLogos extends Command
{
    protected $signature = 'brands:fetch-logos
                            {--all : Proses semua merek, bukan hanya yang tampil di halaman utama}
                            {--limit=200 : Maksimal merek yang diproses dalam sekali jalan}
                            {--retry : Coba lagi merek yang sebelumnya tidak ketemu logonya}';

    protected $description = 'Ambil logo merek mobil dari Wikidata/Wikimedia Commons (halaman utama lebih dulu)';

    public function handle(): int
    {
        $homeIds = Brand::withCount('carModels')
            ->orderByDesc('car_models_count')->orderBy('name')
            ->limit(8)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $query = Brand::query()->whereNull('logo_url');

        if (! $this->option('retry')) {
            $query->whereNull('logo_checked_at');
        }

        if ($this->option('all')) {
            if ($homeIds) {
                $query->orderByRaw('id IN ('.implode(',', $homeIds).') DESC');
            }
            $query->orderBy('name');
        } else {
            $query->whereIn('id', $homeIds ?: [0]);
        }

        $brands = $query->limit(max(1, (int) $this->option('limit')))->get();

        if ($brands->isEmpty()) {
            $this->info('Tidak ada merek yang perlu diproses.');

            return self::SUCCESS;
        }

        $found = 0;
        $done = 0;
        $bar = $this->output->createProgressBar($brands->count());
        $bar->start();

        foreach ($brands as $brand) {
            try {
                $url = $this->lookup($brand);
            } catch (ConnectionException $e) {
                $bar->clear();
                $this->warn("Koneksi gagal untuk {$brand->name}: ".$e->getMessage());
                $bar->display();
                $bar->advance();

                continue;
            } catch (RuntimeException $e) {
                $this->newLine(2);
                $this->error($e->getMessage());
                $this->line("Tersimpan sebelum berhenti: {$found} logo dari {$done} merek yang diproses.");

                return self::FAILURE;
            }

            $brand->forceFill(['logo_url' => $url, 'logo_checked_at' => now()])->save();

            $found += $url ? 1 : 0;
            $done++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai: {$found} dari {$brands->count()} merek mendapat logo.");

        return self::SUCCESS;
    }

    /** Wikipedia (cari artikel) -> Wikidata (properti logo P154) -> URL file di Commons. */
    private function lookup(Brand $brand): ?string
    {
        $search = $this->api('https://en.wikipedia.org/w/api.php', [
            'action' => 'query', 'format' => 'json',
            'generator' => 'search', 'gsrsearch' => "{$brand->name} car manufacturer",
            'gsrnamespace' => 0, 'gsrlimit' => 3,
            'prop' => 'pageprops', 'ppprop' => 'wikibase_item',
        ]);

        $name = Str::lower($brand->name);
        $page = collect($search['query']['pages'] ?? [])
            ->sortBy('index')
            ->first(fn ($p) => Str::contains(Str::lower($p['title'] ?? ''), $name) && ! empty($p['pageprops']['wikibase_item']));

        if (! $page) {
            return null;
        }

        sleep(1);

        $claims = $this->api('https://www.wikidata.org/w/api.php', [
            'action' => 'wbgetclaims', 'format' => 'json',
            'entity' => $page['pageprops']['wikibase_item'], 'property' => 'P154',
        ]);

        $logos = collect($claims['claims']['P154'] ?? [])
            ->filter(fn ($c) => ($c['rank'] ?? '') !== 'deprecated' && ! empty($c['mainsnak']['datavalue']['value']));

        // Utamakan logo yang ditandai "preferred", kalau tidak ada ambil yang pertama.
        $claim = $logos->firstWhere('rank', 'preferred') ?? $logos->first();
        $file = $claim['mainsnak']['datavalue']['value'] ?? null;

        sleep(1); // sopan terhadap server Wikimedia

        return $file
            ? 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode(str_replace(' ', '_', $file)).'?width=256'
            : null;
    }

    /** Request GET dengan jeda otomatis bila dibatasi (429). */
    private function api(string $endpoint, array $params): array
    {
        $response = null;

        for ($try = 0; $try < 3; $try++) {
            $response = Http::withUserAgent('ParcFerme/1.0 (EMAIL_KAMU@example.com) laravel-http-client')
                ->timeout(15)
                ->get($endpoint, $params);

            if ($response->status() !== 429) {
                break;
            }

            $wait = min(max((int) $response->header('Retry-After'), 30), 120);
            $this->newLine();
            $this->warn("Dibatasi (429), menunggu {$wait} detik lalu mencoba lagi...");
            sleep($wait);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Masih dibatasi (429). Tunggu 10-15 menit, lalu jalankan lagi.');
        }

        return $response->successful() ? ($response->json() ?? []) : [];
    }
}
