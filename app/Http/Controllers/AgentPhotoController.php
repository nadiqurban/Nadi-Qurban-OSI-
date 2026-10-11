<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Pengurusan Ejen: the agent's Gambar Terkini (private disk), inline or as a download. */
class AgentPhotoController
{
    public function __invoke(Request $request, Agent $agent): StreamedResponse
    {
        $media = $agent->photo() ?? abort(404);
        $name = 'Gambar-'.$agent->code.'.jpg';

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => ($request->boolean('muat-turun') ? 'attachment' : 'inline').'; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
