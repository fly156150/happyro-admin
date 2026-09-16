<?php

namespace App\Http\Controllers\Operations;

use App\Contracts\Operations\ItemGrantRecordRepository;
use App\Data\Auth\ClientContext;
use App\Data\GameServer\OperationActor;
use App\Exceptions\CharacterNotFoundException;
use App\Exceptions\GameServerGatewayException;
use App\Exceptions\ItemGrantConflictException;
use App\Exceptions\ItemNotFoundException;
use App\Http\Requests\Operations\GrantCharacterZenyRequest;
use App\Services\Operations\GrantCharacterInventoryItemService;
use App\Services\Operations\GrantCharacterZenyService;
use App\Services\Operations\ItemGrantService;
use App\Support\GameServerErrorMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ItemGrantController
{
    public function __construct(
        private readonly ItemGrantService $grants,
        private readonly ItemGrantRecordRepository $records,
        private readonly GrantCharacterZenyService $grantZeny,
        private readonly GrantCharacterInventoryItemService $grantInventory,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->merge(['delivery' => $request->input('delivery', 'mail')]);
        $data = $request->validate(['delivery' => ['required', 'string', 'in:mail,inventory'], 'item_id' => ['required', 'integer', 'min:1'], 'char_id' => ['required', 'integer', 'min:1'], 'amount' => ['required', 'integer', 'min:1', 'max:30000'], 'title' => ['required_if:delivery,mail', 'nullable', 'string', 'max:45'], 'message' => ['required_if:delivery,mail', 'nullable', 'string', 'max:500'], 'bound' => ['nullable', 'boolean'], 'idempotency_key' => ['required', 'string', 'max:64']]);
        try {
            $context = new ClientContext($request->ip(), (string) $request->userAgent());
            if (($data['delivery'] ?? 'mail') === 'inventory') {
                $result = $this->grantInventory->grant($data, $request->user(), $context);

                return response()->json(['data' => ['delivery' => 'inventory', 'result' => $result], 'success' => true], 201);
            }
            $mailId = $this->grants->mail($data, $request->user(), $context);
        } catch (ItemNotFoundException) {
            return response()->json(['message' => __('messages.item_not_found')], 422);
        } catch (CharacterNotFoundException) {
            return response()->json(['message' => __('messages.character_not_found')], 404);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        } catch (ItemGrantConflictException $exception) {
            return response()->json(['message' => __("messages.{$exception->reason}")], 409);
        } catch (GameServerGatewayException $exception) {
            $status = in_array($exception->errorCode, ['character_offline', 'inventory_full', 'inventory_overweight', 'item_amount_exceeded', 'command_not_replayable'], true) ? 409 : 502;

            return $this->gatewayError($exception, $status);
        }

        return response()->json(['data' => ['mail_id' => $mailId], 'success' => true], 201);
    }

    public function storeZeny(GrantCharacterZenyRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $result = $this->grantZeny->grant(
                $data['idempotency_key'],
                $data['char_id'],
                $data['amount'],
                OperationActor::admin($request->user()),
            );
        } catch (GameServerGatewayException $exception) {
            $status = in_array($exception->errorCode, ['character_offline', 'zeny_amount_exceeded', 'command_not_replayable'], true) ? 409 : 502;

            return $this->gatewayError($exception, $status);
        }

        return response()->json(['data' => $result, 'success' => true]);
    }

    public function index(Request $request): JsonResponse
    {
        $records = $this->records->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json(['data' => $records->items(), 'meta' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage(), 'total' => $records->total()], 'success' => true]);
    }

    private function gatewayError(GameServerGatewayException $exception, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $exception->errorCode, 'message' => GameServerErrorMessage::from($exception)]], $status);
    }
}
