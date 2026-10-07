<?php

use App\Http\Middleware\CanonicalHost;
use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CanonicalHost::class);
        $middleware->web(append: [EnsureAccountIsUsable::class, SecurityHeaders::class]);
        $middleware->api(append: [SecurityHeaders::class]);

        // Gateway callbacks are verified by RSA signature instead of a CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('ejen', 'ejen/*') ? route('agent.login') : route('login'));
        // First module the user may view — not /dashboard, which may be "Tiada" for their role.
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
