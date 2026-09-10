<?php

use App\Http\Controllers\AdventureTools\AdventureItemController;
use App\Http\Controllers\AdventureTools\AdventureToolController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'happyro-admin-backend',
    ]);
});

Route::prefix('adventure-tools')->middleware('game.session')->group(function (): void {
    Route::middleware('throttle:adventure-tools-read')->group(function (): void {
        Route::get('/bootstrap', [AdventureToolController::class, 'bootstrap']);
        Route::get('/character', [AdventureToolController::class, 'character']);
        Route::get('/game-rules', [AdventureToolController::class, 'gameRules']);
        Route::get('/items', [AdventureItemController::class, 'index']);
        Route::get('/items/{itemId}', [AdventureItemController::class, 'show'])->whereNumber('itemId');
    });
    Route::middleware('throttle:adventure-tools-assets')->group(function (): void {
        Route::get('/items/{itemId}/icon', [AdventureItemController::class, 'icon'])->whereNumber('itemId');
        Route::get('/items/{itemId}/illustration', [AdventureItemController::class, 'illustration'])->whereNumber('itemId');
    });
    Route::middleware('throttle:adventure-tools-action')->group(function (): void {
        Route::post('/character/commands', [AdventureToolController::class, 'maintain']);
        Route::put('/game-rules', [AdventureToolController::class, 'applyGameRules']);
        Route::post('/items/grants', [AdventureItemController::class, 'grant']);
        Route::post('/currency/zeny/grants', [AdventureItemController::class, 'grantZeny']);
    });
});
