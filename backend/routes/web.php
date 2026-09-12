<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\GameData\ItemController;
use App\Http\Controllers\GameData\MonsterController;
use App\Http\Controllers\GameData\WorldDataController;
use App\Http\Controllers\Operations\GameServerCommandController;
use App\Http\Controllers\Operations\ItemGrantController;
use App\Http\Controllers\Operations\ItemGrantItemController;
use App\Http\Controllers\Operations\ItemGrantTargetController;
use App\Http\Controllers\Players\LoginLogController;
use App\Http\Controllers\Players\PlayerAccountController;
use App\Http\Controllers\Players\PlayerCharacterController;
use App\Http\Controllers\Settings\GameDataSettingController;
use App\Http\Controllers\Settings\GameServerSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/auth')->group(function () {
    Route::post('/login', [SessionController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [SessionController::class, 'show']);
        Route::post('/logout', [SessionController::class, 'destroy']);
    });
});

Route::prefix('api/players')->middleware(['auth:sanctum', 'permission:players.view'])->group(function () {
    Route::get('/accounts', [PlayerAccountController::class, 'index']);
    Route::post('/accounts', [PlayerAccountController::class, 'store'])->middleware('permission:players.edit');
    Route::get('/characters', [PlayerCharacterController::class, 'index']);
    Route::get('/characters/{charId}', [PlayerCharacterController::class, 'show'])->whereNumber('charId');
    Route::get('/login-logs', [LoginLogController::class, 'index']);
    Route::patch('/accounts/{accountId}', [PlayerAccountController::class, 'update'])->middleware('permission:players.edit');
    Route::post('/accounts/{accountId}/password', [PlayerAccountController::class, 'password'])->middleware('permission:players.edit');
    Route::post('/accounts/batch', [PlayerAccountController::class, 'batch'])->middleware('permission:players.edit');
    Route::delete('/accounts/{accountId}', [PlayerAccountController::class, 'destroy'])->middleware('permission:players.edit');
});

Route::prefix('api/game-data')->middleware(['auth:sanctum', 'permission:game-data.view'])->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{itemId}', [ItemController::class, 'show'])->whereNumber('itemId');
    Route::get('/items/{itemId}/icon', [ItemController::class, 'icon'])->whereNumber('itemId');
    Route::get('/items/{itemId}/illustration', [ItemController::class, 'illustration'])->whereNumber('itemId');
    Route::get('/monsters', [MonsterController::class, 'index']);
    Route::get('/monsters/{monsterId}', [MonsterController::class, 'show'])->whereNumber('monsterId');
    Route::get('/monsters/{monsterId}/image', [MonsterController::class, 'image'])->whereNumber('monsterId');
    Route::get('/maps', [WorldDataController::class, 'maps']);
    Route::get('/maps/{map}/image', [WorldDataController::class, 'mapImage'])->where('map', '[a-z0-9_@-]+');
    Route::get('/npcs', [WorldDataController::class, 'npcs']);
    Route::get('/npcs/{spriteId}/image', [WorldDataController::class, 'npcImage'])->whereNumber('spriteId');
});

Route::prefix('api/settings')->middleware(['auth:sanctum', 'permission:settings.manage'])->group(function () {
    Route::get('/game-data', [GameDataSettingController::class, 'show']);
    Route::put('/game-data', [GameDataSettingController::class, 'update']);
    Route::get('/game-data/history', [GameDataSettingController::class, 'history']);
    Route::get('/game-settings', [GameServerSettingController::class, 'show']);
    Route::put('/game-settings', [GameServerSettingController::class, 'update']);
    Route::get('/game-settings/history', [GameServerSettingController::class, 'history']);
});

Route::prefix('api/operations')->middleware(['auth:sanctum', 'permission:operations.item-grant'])->group(function () {
    Route::get('/item-grant-items', [ItemGrantItemController::class, 'index']);
    Route::get('/item-grant-targets', [ItemGrantTargetController::class, 'index']);
    Route::post('/item-grants/mail', [ItemGrantController::class, 'store']);
    Route::post('/zeny-grants', [ItemGrantController::class, 'storeZeny']);
    Route::get('/item-grants', [ItemGrantController::class, 'index']);
});

Route::prefix('api/operations/game-control')->middleware(['auth:sanctum', 'permission:operations.game-control'])->group(function () {
    Route::get('/capabilities', [GameServerCommandController::class, 'capabilities']);
    Route::get('/battle-config', [GameServerCommandController::class, 'battleConfig']);
    Route::post('/commands', [GameServerCommandController::class, 'store']);
    Route::get('/commands/{commandId}', [GameServerCommandController::class, 'show']);
});
