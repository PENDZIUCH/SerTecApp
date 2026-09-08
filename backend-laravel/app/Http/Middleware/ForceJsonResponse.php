<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Esta es una API pura (routes/api.php) - nunca debe devolver una redirect
 * HTML. Sin esto, un request sin header "Accept: application/json" (curl
 * plano, un bot, cualquier cliente que no lo mande) hace que el manejo de
 * excepciones "web" de Laravel intente redirect()->guest(route('login')),
 * y esa ruta no existe en esta app (solo la de Filament) -> 500 en vez de
 * un 401/404 JSON limpio. Forzando el Accept acá, toda excepción bajo
 * /api/* se resuelve siempre como JSON.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
