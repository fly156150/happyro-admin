<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommand;
use App\Exceptions\GameServerGatewayException;
use Throwable;

final class ExecuteGameServerCommandService
{
    public function __construct(
        private readonly GameServerCommandRepository $commands,
        private readonly GameServerGateway $gateway,
    ) {}

    /** @throws Throwable */
    public function execute(string $id): GameServerCommand
    {
        $command = $this->commands->markRunning($id);

        try {
            $result = $this->gateway->execute($command);
        } catch (GameServerGatewayException $exception) {
            if ($exception->outcomeUnknown) {
                $this->commands->markIndeterminate($id, $exception->errorCode, $exception->getMessage());
            } else {
                $this->commands->markFailed($id, $exception->errorCode, $exception->getMessage());
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->commands->markIndeterminate($id, 'unexpected_error', 'The game server command outcome could not be confirmed.');

            throw $exception;
        }

        return $this->commands->markSucceeded($id, $result->data);
    }
}
