<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerApiController extends Controller
{
    public function __construct(public PlayerService $playerService) {}

    public function store(Request $request): PlayerResource|JsonResponse
    {
        $request->validate([
            'client_id' => 'required|string|max:255',
        ]);

        $game = $request->attributes->get('game');
        ['player' => $player, 'created' => $created] = $this->playerService->findOrCreate($game, $request->input('client_id'));

        $player->load('clients');
        $resource = new PlayerResource($player);

        return $created
            ? $resource->response()->setStatusCode(201)
            : $resource->response()->setStatusCode(200);
    }

    public function show(Request $request, string $playerId): PlayerResource
    {
        $game = $request->attributes->get('game');

        $player = $this->playerService->find($game, $playerId);

        $player->load('clients');

        return new PlayerResource($player);
    }
}
