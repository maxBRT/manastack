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
    Route::get('/players/{player}/saves', [SaveApiController::class, 'index']);
    Route::post('/players/{player}/saves', [SaveApiController::class, 'store']);
    Route::get('/players/{player}/saves/{save}', [SaveApiController::class, 'show']);
    Route::put('/players/{player}/saves/{save}', [SaveApiController::class, 'update']);
    Route::delete('/players/{player}/saves/{save}', [SaveApiController::class, 'destroy']);
});
