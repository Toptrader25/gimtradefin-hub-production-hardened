<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // THIS LINE was the actual missing piece flagged in review — in
        // Laravel 11, routes/api.php is never loaded unless registered
        // here. Without it, every endpoint built so far (opportunities,
        // companies, auth, everything) would 404 on a real server
        // regardless of how correct the route definitions themselves are.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 'auth' and 'throttle' are Laravel's built-in middleware
        // aliases — no extra registration needed for the auth:sanctum
        // and throttle:5,1 middleware already used in routes/api.php.
        //
        // 'reviewer' is NOT built in — it's our own role-check
        // middleware (app/Http/Middleware/EnsureUserIsReviewer.php).
        // Forgetting this line would mean ->middleware('reviewer') in
        // routes/api.php silently does nothing, the same class of bug
        // as the missing api: routing before this file existed.
        $middleware->alias([
            'reviewer' => \App\Http\Middleware\EnsureUserIsReviewer::class,
            'admin'    => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
