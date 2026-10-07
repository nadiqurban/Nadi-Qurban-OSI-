<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $idle = $request->boolean('idle');
        $isAgent = $user?->isAgent() ?? false;

        if ($user) {
            Audit::log('logout', $idle ? 'Log keluar automatik (tidak aktif 30 minit)' : 'Log keluar', $user, causer: $user, logName: 'auth');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($isAgent ? 'agent.login' : 'login')->with(
            $idle ? 'warning' : 'status',
            $idle ? 'Sesi anda tamat selepas 30 minit tidak aktif. Sila log masuk semula.' : 'Anda telah log keluar.',
        );
    }
}
