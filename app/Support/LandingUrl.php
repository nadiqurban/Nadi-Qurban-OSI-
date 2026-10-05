<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Where to send a user right after logging in. The "intended" URL is remembered
 * per browser, so it may belong to whoever was logged in before (e.g. a Super
 * Admin on /pengguna). Only follow it when this user may open it; otherwise land
 * on their first allowed module (route "home").
 */
class LandingUrl
{
    public static function after(User $user): string
    {
        $intended = session()->pull('url.intended');

        return is_string($intended) && self::canOpen($user, $intended) ? $intended : route('home');
    }

    public static function canOpen(User $user, string $url): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($url));
        } catch (Throwable) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'can:')) {
                $ability = explode(',', substr($middleware, 4))[0];

                if (! $user->can($ability)) {
                    return false;
                }
            }
        }

        return true;
    }
}
