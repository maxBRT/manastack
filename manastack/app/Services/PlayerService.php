<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Game;
use App\Models\Player;

class PlayerService
{
    /**
     * @return array{player: Player, client: Client, created: bool}
     */
    public function findOrCreate(Game $game, string $clientId): array
    {
        $client = $game->clients()->where('client_id', $clientId)->first();

        if ($client) {
            return ['player' => $client->player, 'client' => $client, 'created' => false];
        }

        $player = $game->players()->create();
        $client = $player->clients()->create(['client_id' => $clientId]);

        return ['player' => $player, 'client' => $client, 'created' => true];
    }

    public function find(Game $game, string $playerId): Player
    {
        return $game->players()->findOrFail($playerId);
    }
}
