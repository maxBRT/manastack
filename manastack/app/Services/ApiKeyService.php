<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Game;
use Illuminate\Support\Str;

class ApiKeyService
{
    /**
     * Generate a new API key for a game.
     *
     * @return array{api_key: ApiKey, plain_text_key: string}
     */
    public function create(Game $game, string $name): array
    {
        $plainTextKey = Str::random(40);

        $apiKey = $game->apiKeys()->create([
            'key' => hash('sha256', $plainTextKey),
            'name' => $name,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_text_key' => $plainTextKey,
        ];
    }

    public function resolveGameFromKey(string $plainTextKey): ?Game
    {
        $hashedKey = hash('sha256', $plainTextKey);

        $apiKey = ApiKey::query()
            ->where('key', $hashedKey)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $apiKey) {
            return null;
        }

        $apiKey->update(['last_used_at' => now()]);

        return $apiKey->game;
    }

    public function updateLastUsedAt(string $plainText): void
    {
        $hashedKey = hash('sha256', $plainText);
        $apiKey = ApiKey::query()
            ->where('key', $hashedKey)
            ->first();
        $apiKey->update(['last_used_at' => now()]);
    }
}
