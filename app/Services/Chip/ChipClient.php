<?php

namespace App\Services\Chip;

use App\Support\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real CHIP Collect client. Keys come from Tetapan › Integrasi (encrypted settings)
 * with .env fallback. Test vs live is decided by the secret key.
 */
class ChipClient implements ChipGateway
{
    public function __construct(private readonly Settings $settings) {}

    private function brandId(): string
    {
        return (string) ($this->settings->get('chip.brand_id') ?: config('services.chip.brand_id'));
    }

    private function secretKey(): string
    {
        return (string) ($this->settings->get('chip.secret_key') ?: config('services.chip.secret_key'));
    }

    public function isConfigured(): bool
    {
        return $this->brandId() !== '' && $this->secretKey() !== '';
    }

    private function http(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('CHIP Collect belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim((string) config('services.chip.base_url'), '/').'/')
            ->withToken($this->secretKey())
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }

    public function createPurchase(array $body): array
    {
        $response = $this->http()->post('purchases/', ['brand_id' => $this->brandId()] + $body);

        if ($response->status() !== 201) {
            throw new RuntimeException('CHIP: '.($response->json('__all__.message') ?? 'gagal mencipta pembelian ('.$response->status().')'));
        }

        return (array) $response->json();
    }

    public function getPurchase(string $purchaseId): array
    {
        $response = $this->http()->get('purchases/'.rawurlencode($purchaseId).'/');

        if (! $response->successful()) {
            throw new RuntimeException('CHIP: pembelian tidak dijumpai ('.$response->status().').');
        }

        return (array) $response->json();
    }

    public function verifySignature(string $rawBody, string $signature): bool
    {
        $decoded = base64_decode($signature, true);

        if ($decoded === false || $decoded === '') {
            return false;
        }

        foreach ($this->publicKeys() as $pem) {
            $key = openssl_pkey_get_public($pem);

            if ($key !== false && openssl_verify($rawBody, $decoded, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Webhook key (from settings) + company key for success_callback (GET /public_key/, cached a day).
     *
     * @return list<string>
     */
    private function publicKeys(): array
    {
        $keys = array_filter([
            (string) $this->settings->get('chip.webhook_public_key'),
            (string) $this->settings->get('chip.public_key'),
        ]);

        if ($this->isConfigured()) {
            $company = Cache::remember('chip.company_public_key.'.md5($this->secretKey()), now()->addDay(), function () {
                $response = $this->http()->get('public_key/');
                $body = $response->json();

                return is_string($body) ? $body : (string) ($body['public_key'] ?? trim($response->body(), "\" \n\r\t"));
            });

            if ($company !== '') {
                $keys[] = str_replace('\n', "\n", $company);
            }
        }

        return array_values(array_unique($keys));
    }
}
