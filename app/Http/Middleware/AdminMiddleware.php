<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica se o usuário está autenticado e se é administrador
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        // Caso contrário, aborta com erro 403 (acesso negado)
        abort(403, 'Acesso não autorizado.');
    }
}
