<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Portal Ejen "Bukti": an agent may view the payment proof of orders from their own link only. */
class AgentProofController
{
    public function __invoke(Request $request, Payment $payment): StreamedResponse
    {
        $agentId = $request->user()?->agent?->id;
        abort_unless($agentId !== null && $payment->order->agent_id === $agentId, 404);

        $media = $payment->proof() ?? abort(404);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
