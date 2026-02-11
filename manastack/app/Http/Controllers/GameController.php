<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GameResource::collection(Game::all());
    }

    /**
     * Store a newly created resource in storage>.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string'
            ]);
        $game = Game::create($validated);
        return new GameResource($game);
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        return new GameResource($game);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        $validated = $request->validate([
            'title'=> 'required|string'
        ]);
        
        $updated = $game->update($validated);
        
        if(!$updated){
            return response()->json([
                'message' => 'The game could not be updated.',
                'errors' => ['database' => 'Database error occurred.']
            ], 400);
        }

        return new GameResource($game);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        $game->delete();
    }
}
