<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Si la petición espera JSON no redirigimos (devuelve 401)
        if ($request->expectsJson()) {
            return null;
        }

        // Si existe una ruta nombrada 'login' úsala, si no devuelve una URL segura
        if (Route::has('login')) {
            return route('login');
        }

        // Para rutas API evitamos redirecciones (null) y para web devolvemos /login
        if ($request->is('api/*')) {
            return null;
        }

        return url('/login');
    }
}
