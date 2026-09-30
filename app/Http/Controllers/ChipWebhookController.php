<?php

namespace App\Http\Controllers;

use App\Actions\Installments\ProcessChipPurchase;
use App\Services\Chip\ChipGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CHIP Collect success_callback + webhook endpoint. The X-Signature (RSA/SHA-256
 * over the raw body) is verified before anything is trusted; processing is idempotent.
 */
class ChipWebhookController
{
    public function __invoke(Request $request, ChipGateway $chip, ProcessChipPurchase $process): JsonResponse
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '' || ! $chip->verifySignature($raw, $signature)) {
            Log::warning('CHIP callback rejected: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! isset($payload['id'])) {
            return response()->json(['message' => 'ignored'], 202);
        }

        $tx = $process->handle($payload);

        return response()->json(['message' => $tx ? $tx->status : 'unknown purchase']);
    }
}
