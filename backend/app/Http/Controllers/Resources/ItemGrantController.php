<?php

namespace App\Http\Controllers\Resources;

use App\Data\Auth\ClientContext;
use App\Exceptions\CharacterNotFoundException;
use App\Exceptions\ItemNotFoundException;
use App\Services\Resources\ItemGrantService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ItemGrantController
{
    public function __construct(private readonly ItemGrantService $grants) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['item_id' => ['required', 'integer', 'min:1'], 'char_id' => ['required', 'integer', 'min:1'], 'amount' => ['required', 'integer', 'min:1', 'max:30000'], 'title' => ['required', 'string', 'max:45'], 'message' => ['required', 'string', 'max:500'], 'bound' => ['nullable', 'boolean']]);
        try {
            $mailId = $this->grants->mail($data, $request->user(), new ClientContext($request->ip(), (string) $request->userAgent()));
        } catch (ItemNotFoundException) {
            return response()->json(['message' => __('messages.item_not_found')], 422);
        } catch (CharacterNotFoundException) {
            return response()->json(['message' => __('messages.character_not_found')], 404);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => ['mail_id' => $mailId], 'success' => true], 201);
    }
}
