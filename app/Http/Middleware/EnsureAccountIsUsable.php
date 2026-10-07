<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * For every authenticated request:
 *  - suspended accounts are logged out immediately;
 *  - users flagged `must_change_password` can only reach the change-password screen;
 *  - sales agents (role Ejen) are kept inside the Portal Ejen;
 *  - `last_seen_at` is refreshed (at most once a minute) for "Akses Terakhir".
 */
class EnsureAccountIsUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('warning', 'Akaun anda telah digantung. Sila hubungi pentadbir sistem.');
        }

        if ($user->must_change_password && ! $request->routeIs('password.force', 'logout', 'livewire.*')) {
            return redirect()->route('password.force');
        }

        // Sales agents only use the Portal Ejen among the signed-in (staff) pages; public pages
        // such as their own booking link (/tempah/{nama}), /jejak and receipts stay open to them.
        if ($user->isAgent() && $this->isStaffPage($request) && ! $request->routeIs('agent.*', 'logout', 'livewire.*', 'password.force')) {
            return redirect()->route('agent.portal');
        }

        if (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute())) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }

    /** Pages behind the "auth" middleware (the staff app); public pages have none. */
    private function isStaffPage(Request $request): bool
    {
        return in_array('auth', $request->route()?->gatherMiddleware() ?? [], true);
    }
}
