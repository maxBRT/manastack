<?php

use App\Http\Controllers\GameApiController;
use App\Http\Controllers\PlayerApiController;
use App\Http\Controllers\SaveApiController;
use Illuminate\Support\Facades\Route;

// Games
// Route::get('/games', [GameApiController::class, 'index']);
// Route::get('/games/{game}', [GameApiController::class, 'show']);
// Route::post('/games', [GameApiController::class, 'store']);
// Route::put('/games/{game}', [GameApiController::class, 'update']);
// Route::delete('/games/{game}', [GameApiController::class, 'destroy']);

// Player & Save routes (authenticated via API key)
Route::middleware('auth.apikey', 'ratelimit')->group(function () {
    Route::post('/players', [PlayerApiController::class, 'store']);
    Route::get('/players/{player}', [PlayerApiController::class, 'show']);
    Route::get('/saves/{client_id}', [SaveApiController::class, 'index']);
    Route::post('/saves/{client_id}', [SaveApiController::class, 'store']);
    Route::get('/saves/{client_id}/{save}', [SaveApiController::class, 'show']);
    Route::put('/saves/{client_id}/{save}', [SaveApiController::class, 'update']);
    Route::delete('/saves/{client_id}/{save}', [SaveApiController::class, 'destroy']);
});
