<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = config('cors');

        $response = $next($request);

        $origin = $request->headers->get('Origin');

        if (in_array($origin, $config['allowed_origins'])) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $config['allowed_methods']));
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $config['allowed_headers']));
            $response->headers->set('Access-Control-Allow-Credentials', $config['supports_credentials']);

            if ($request->getMethod() === 'OPTIONS') {
                $response->headers->set('Access-Control-Max-Age', $config['max_age']);
                return $response;
            }

            // Handle preflight requests
            if ($request->isMethod('OPTIONS')) {
                return response()->json('OK', 200, $response->headers->all());
            }
        }

        return $response;
    }
}
