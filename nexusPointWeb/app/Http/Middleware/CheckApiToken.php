<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CheckApiToken
{
    public function handle(Request $request, Closure $next)
    {
        // Si no hay token en sesión, redirigir al login
        // El token fue guardado en AuthController::login() como 'api_token'
        // (viene del campo 'access_token' del schema Token de la API)
        if (!Session::has('api_token')) {
            return redirect()->route('login')->with('error', 'Sesión expirada. Inicia sesión nuevamente.');
        }

        return $next($request);
    }
}