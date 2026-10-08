<?php

namespace App\Services\Chip;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * In-memory CHIP for tests and local demos (CHIP_FAKE=true, never in production):
 * the checkout URL goes straight back to success_redirect and the purchase reads as
 * paid (or as failed when $failNext is set). Signatures use a generated RSA key pair.
 */
class FakeChipGateway implements ChipGateway
{
    public bool $failNext = false;

    private ?string $privateKey = null;

    private ?string $publicKey = null;

    public function isConfigured(): bool
    {
        return true;
    }

    public function createPurchase(array $body): array
    {
        $id = (string) Str::uuid();
        $status = $this->failNext ? 'error' : 'paid';
        $this->failNext = false;

        $purchase = [
            'id' => $id,
            'status' => $status,
            'reference' => $body['reference'] ?? null,
            'client' => $body['client'] ?? [],
            'created_on' => now()->timestamp,
            'checkout_url' => $body[$status === 'paid' ? 'success_redirect' : 'failure_redirect'] ?? $body['success_redirect'] ?? '/',
            'purchase' => $body['purchase'] ?? [],
            'transaction_data' => ['payment_method' => $body['payment_method_whitelist'][0] ?? 'fpx'],
            'payment' => $status === 'paid' ? ['amount' => array_sum(array_column((array) ($body['purchase']['products'] ?? []), 'price')), 'paid_on' => now()->timestamp] : null,
            'is_test' => true,
        ];

        Cache::put('fake-chip:'.$id, $purchase, now()->addDay());

        return $purchase;
    }

    public function getPurchase(string $purchaseId): array
    {
        return (array) Cache::get('fake-chip:'.$purchaseId, ['id' => $purchaseId, 'status' => 'error']);
    }

    public function verifySignature(string $rawBody, string $signature): bool
    {
        $this->keys();
        $key = $this->publicKey ? openssl_pkey_get_public($this->publicKey) : false;

        return $key !== false && openssl_verify($rawBody, (string) base64_decode($signature, true), $key, OPENSSL_ALGO_SHA256) === 1;
    }

    /** Sign a payload like CHIP would (tests). */
    public function sign(string $rawBody): string
    {
        $this->keys();
        openssl_sign($rawBody, $signature, (string) $this->privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    private function keys(): void
    {
        if ($this->privateKey !== null) {
            return;
        }

        // Windows PHP builds need an explicit openssl.cnf (shipped in extras/ssl).
        $config = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $cnf = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';

        if (is_file($cnf)) {
            $config['config'] = $cnf;
        }

        $key = openssl_pkey_new($config);

        if ($key === false) {
            return;
        }

        openssl_pkey_export($key, $private, null, $config);
        $this->privateKey = $private;
        $this->publicKey = (string) (openssl_pkey_get_details($key)['key'] ?? '');
    }
}
