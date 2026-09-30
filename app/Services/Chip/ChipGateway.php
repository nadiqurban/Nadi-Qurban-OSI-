<?php

namespace App\Services\Chip;

/**
 * CHIP Collect (gate.chip-in.asia) — the parts the instalment portal needs.
 * Bound to ChipClient; tests and local demos bind FakeChipGateway.
 */
interface ChipGateway
{
    public function isConfigured(): bool;

    /**
     * POST /purchases/ → Purchase (id, status, checkout_url, …).
     *
     * @param  array<string, mixed>  $body  without brand_id (added by the gateway)
     * @return array<string, mixed>
     */
    public function createPurchase(array $body): array;

    /**
     * GET /purchases/{id}/
     *
     * @return array<string, mixed>
     */
    public function getPurchase(string $purchaseId): array;

    /** RSA PKCS#1 v1.5 / SHA-256 check of X-Signature over the raw body. */
    public function verifySignature(string $rawBody, string $signature): bool;
}
