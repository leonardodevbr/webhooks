<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Configuração de CORS
        $response->headers->set('Access-Control-Allow-Origin', 'https://webhook.leoontech.com');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, postman-token');

        // Se a requisição for OPTIONS (preflight), retorna 204 diretamente
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 204);
        }

        return $response;
    }
}
