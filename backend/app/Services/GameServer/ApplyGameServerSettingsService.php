<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\GameServer\GameServerSettingRepository;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Data\GameServer\OperationActor;
use App\Models\GameServerSettingRevision;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

final readonly class ApplyGameServerSettingsService
{
    public function __construct(
        private PrepareGameServerSettingsService $prepare,
        private GameServerSettingRevisionRepository $revisions,
        private GameServerSettingRepository $settings,
        private GameServerConfigWriter $writer,
        private SubmitGameServerCommandService $submit,
        private ExecuteGameServerCommandService $execute,
        private GameServerGateway $gateway,
    ) {}

    /** @param array<string, int> $changes */
    public function apply(array $changes, ?string $remark, User $operator): GameServerSettingRevision
    {
        return $this->applyForActor($changes, $remark, OperationActor::admin($operator));
    }

    /** @param array<string, int> $changes */
    public function applyForGameAccount(array $changes, ?string $remark, int $accountId): GameServerSettingRevision
    {
        return $this->applyForActor($changes, $remark, OperationActor::gameAccount($accountId));
    }

    /** @param array<string, int> $changes */
    private function applyForActor(array $changes, ?string $remark, OperationActor $actor): GameServerSettingRevision
    {
        $snapshot = $this->writer->snapshot();
        $revision = null;

        try {
            $revision = $this->prepare->prepare($changes, $remark, $actor);
            $command = $this->submit->submitForActor(new GameServerCommandRequest(
                (string) Str::uuid(),
                GameServerCommandType::BattleConfigApply,
                'server',
                'primary',
                ['changes' => array_map(
                    static fn (int $value, string $key): array => ['key' => $key, 'value' => $value],
                    $changes,
                    array_keys($changes),
                )],
            ), $actor);
            $this->execute->execute($command->command->id);
            $actual = $this->gateway->battleConfig();
            foreach ($changes as $key => $value) {
                if (($actual[$key] ?? null) !== $value) {
                    throw new \RuntimeException('Game server configuration did not match the requested values.');
                }
            }

            $this->settings->syncApplied('primary', $changes, $revision->getKey());

            return $this->revisions->markApplied($revision->getKey());
        } catch (Throwable $exception) {
            $this->writer->restore($snapshot);
            if ($revision !== null) {
                $this->revisions->markFailed($revision->getKey());
            }

            throw $exception;
        }
    }
}
