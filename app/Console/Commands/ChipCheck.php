<?php

namespace App\Console\Commands;

use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Support\PaymentGateways;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Read-only CHIP Collect health check (no keys are printed): which gateway is bound,
 * whether keys are set, and whether CHIP accepts the Secret Key and the Brand ID.
 */
class ChipCheck extends Command
{
    protected $signature = 'chip:check';

    protected $description = 'Semak sambungan CHIP Collect (tanpa memaparkan kunci)';

    public function handle(ChipGateway $chip, PaymentGateways $gateways, Settings $settings): int
    {
        $this->line('Gateway   : '.($chip instanceof ChipClient ? 'CHIP sebenar' : 'SIMULASI ('.class_basename($chip).')'));
        $this->line('Persekitaran: '.app()->environment());
        $this->line('Diaktifkan: '.($gateways->enabled('chip') ? 'Ya' : 'Tidak'));
        $this->line('Kunci diisi: '.($chip->isConfigured() ? 'Ya' : 'TIDAK — isi Brand ID & Secret Key di Tetapan › Integrasi API'));

        if (! $chip instanceof ChipClient || ! $chip->isConfigured()) {
            return self::FAILURE;
        }

        $base = rtrim((string) config('services.chip.base_url'), '/').'/';
        $secret = (string) ($settings->get('chip.secret_key') ?: config('services.chip.secret_key'));
        $brand = (string) ($settings->get('chip.brand_id') ?: config('services.chip.brand_id'));

        try {
            $key = Http::baseUrl($base)->withToken($secret)->acceptJson()->timeout(15)->get('public_key/');
            $this->line('Secret Key: '.($key->successful() ? 'DITERIMA ('.$key->status().')' : 'DITOLAK ('.$key->status().')'));

            // A purchase list filtered by brand: 2xx = the key may use this brand.
            $brandCheck = Http::baseUrl($base)->withToken($secret)->acceptJson()->timeout(15)->get('purchases/', ['brand_id' => $brand, 'limit' => 1]);
            $this->line('Brand ID  : '.($brandCheck->successful() ? 'DITERIMA ('.$brandCheck->status().')' : 'SEMAK ('.$brandCheck->status().': '.mb_substr((string) $brandCheck->body(), 0, 160).')'));

            return $key->successful() ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Tidak dapat menghubungi CHIP: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
