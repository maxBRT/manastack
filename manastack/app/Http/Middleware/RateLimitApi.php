<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use phpseclib3\Math\BinaryField\Integer;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts= 5): Response
    {
        $key = 'ratelimit-key:' . $request->header('X-API-Key');
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)){
            $retryAfter = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests.',
                'retry_after_seconds' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        RateLimiter::hit($key, 2);

        return $next($request);
    }
}