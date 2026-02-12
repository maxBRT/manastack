<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(public ApiKeyService $apiKeyService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (! $apiKey) {
            return response()->json(['message' => 'API key is required.'], 401);
        }

        $game = $this->apiKeyService->resolveGameFromKey($apiKey);

        if (! $game) {
            return response()->json(['message' => 'Invalid or expired API key.'], 401);
        }

        $request->merge(['game' => $game]);
        $request->attributes->set('game', $game);
        
        $this->apiKeyService->updateLastUsedAt($apiKey);
        return $next($request);
    }
}
