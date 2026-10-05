<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Outside local/testing, permanently redirect GET/HEAD requests on any other host
 * (e.g. the *.on-forge.com alias) to the APP_URL host. Other methods (webhooks,
 * API writes) pass through untouched so callbacks never break on a redirect.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (
            $canonical
            && ! app()->environment('local', 'testing')
            && $request->isMethodSafe()
            && ! $request->is('up')
            && strcasecmp($request->getHost(), $canonical) !== 0
        ) {
            return redirect()->away(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
