<?php

namespace App\Http\Controllers\Settings;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Data\Auth\ClientContext;
use App\Http\Requests\Settings\UpdateGameDataSettingRequest;
use App\Services\GameData\UpdateGameDataSettingService;
use Illuminate\Http\JsonResponse;

final readonly class GameDataSettingController
{
    public function __construct(
        private GameDataSettingRepository $settings,
        private UpdateGameDataSettingService $updateSettings,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => [
            ...$this->settings->current()->toArray(),
            'available' => $this->settings->available(),
        ], 'success' => true]);
    }

    public function update(UpdateGameDataSettingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $versions = $this->updateSettings->update(
            $data['clientVersion'], $data['serverVersion'], $request->user(),
            new ClientContext($request->ip(), (string) $request->userAgent()),
        );

        return response()->json(['data' => $versions->toArray(), 'success' => true]);
    }
}
