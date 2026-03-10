<?php

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
        // Force Doppler Secrets Load
        if (class_exists(\App\Services\Infrastructure\DopplerService::class)) {
            (new \App\Services\Infrastructure\DopplerService())->boot();
        }
        
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->append(\App\Http\Middleware\FraudControlMiddleware::class);
        $middleware->append(\App\Http\Middleware\BusinessGroupGuard::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booting(function () {
        (new \App\Services\Infrastructure\DopplerService())->boot();
    })
    ->create();
