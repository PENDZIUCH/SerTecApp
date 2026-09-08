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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Fix para Hostinger: confiar en proxies y especificar headers explícitamente.
        // Sin esto, Laravel recibe IP=null de los proxies de Hostinger y lanza:
        // "IpUtils::checkIp4(): Argument #2 ($ip) must be of type string, null given"
        // lo que resulta en HTTP 403 al intentar hacer login POST.
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
        );

        // Esta API nunca debe devolver una redirect HTML - ver el comentario
        // en ForceJsonResponse para el bug real que esto evita (500 en vez
        // de 401/404 JSON cuando el cliente no manda Accept: application/json).
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
