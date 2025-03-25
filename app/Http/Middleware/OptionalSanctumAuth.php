<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Log::info('OptionalSanctumAuth middleware started');
        // Log::info('Request headers: ' . json_encode($request->headers->all()));

        if ($token = $request->bearerToken()) {
            // Log::info('Bearer token found: ' . $token);
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->tokenable) {
                Auth::login($accessToken->tokenable);
                // Log::info('User authenticated: ' . Auth::id());
            } else {
                Log::info('Invalid or expired token');
            }
        } else {
            Log::info('No Bearer token present');
        }

        // Log::info('OptionalSanctumAuth middleware completed');
        return $next($request);
    }
}
