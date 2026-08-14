<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta el acceso al panel de administración a cualquiera que no sea admin.
 *
 * Es la primera barrera, a nivel de ruta. Las Policies siguen actuando por debajo
 * sobre cada modelo: si mañana un cliente pudiera entrar a una vista compartida,
 * BookingPolicy igual le impediría tocar reservas ajenas.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Necesitás permisos de administrador.');

        return $next($request);
    }
}
