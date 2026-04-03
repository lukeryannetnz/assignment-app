<?php

use App\Domains\IdentityAccess\Http\Middleware\EnsureUserIsAdmin;
use App\Domains\Tenancy\Http\Middleware\EnsureTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(static fn (Request $request): string => route(
            'identity-access.auth.login',
            absolute: false,
        ));

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'tenant' => EnsureTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
