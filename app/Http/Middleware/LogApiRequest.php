<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\ApiRequest;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Rejects revoked keys and records every /api/v1 call for the usage meter. */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve through the token itself so the tokenable is typed as a model, not a User.
        $client = PersonalAccessToken::findToken((string) $request->bearerToken())?->tokenable;
        abort_unless($client instanceof ApiClient && $client->revoked_at === null, 401, 'Kunci API tidak sah atau telah dibatalkan.');

        $response = $next($request);

        ApiRequest::query()->create([
            'api_client_id' => $client->id,
            'method' => $request->method(),
            'path' => mb_substr($request->path(), 0, 200),
            'status' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }
}
