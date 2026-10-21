<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class]);

        // Midtrans cannot send a CSRF token; the handler verifies signature_key instead.
        $middleware->validateCsrfTokens(except: ['payments/midtrans/notification']);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Trust X-Forwarded-* from any proxy (e.g. ngrok over https) in local only.
        // .env is not loaded yet when this callback runs, so check after boot.
        app()->booted(function ($app) use ($middleware) {
            if ($app->isLocal()) {
                $middleware->trustProxies(at: '*');
            }
        });

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
