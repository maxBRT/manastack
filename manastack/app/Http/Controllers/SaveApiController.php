<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaveRequest;
use App\Http\Requests\UpdateSaveRequest;
use App\Http\Resources\SaveResource;
use App\Models\Player;
use App\Services\SaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaveApiController extends Controller
{
    public function __construct(public SaveService $saveService) {}

    private function resolvePlayer(Request $request, string $clientId): Player
    {
        $game = $request->attributes->get('game');

        return Player::whereHas('clients', fn ($q) => $q->where('client_id', $clientId))
            ->where('game_id', $game->id)
            ->firstOrFail();
    }

    public function index(Request $request, string $clientId): AnonymousResourceCollection
    {
        $player = $this->resolvePlayer($request, $clientId);

        return SaveResource::collection($this->saveService->listForPlayer($player));
    }

    public function store(StoreSaveRequest $request, string $clientId): SaveResource
    {
        $player = $this->resolvePlayer($request, $clientId);

        $save = $this->saveService->create($player, $request->validated());

        return new SaveResource($save);
    }

    public function show(Request $request, string $clientId, string $saveId): SaveResource
    {
        $player = $this->resolvePlayer($request, $clientId);

        return new SaveResource($this->saveService->find($player, $saveId));
    }

    public function update(UpdateSaveRequest $request, string $clientId, string $saveId): SaveResource
    {
        $player = $this->resolvePlayer($request, $clientId);

        return new SaveResource($this->saveService->update($player, $saveId, $request->validated()));
    }

    public function destroy(Request $request, string $clientId, string $saveId): JsonResponse
    {
        $player = $this->resolvePlayer($request, $clientId);

        $this->saveService->delete($player, $saveId);

        return response()->json(null, 204);
    }
}
