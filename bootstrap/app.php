<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'active_user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'n8n.webhook' => \App\Http\Middleware\VerifyN8nWebhookSecret::class,
            'plan_active' => \App\Http\Middleware\EnsurePlanIsActive::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\EnsurePlanIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
