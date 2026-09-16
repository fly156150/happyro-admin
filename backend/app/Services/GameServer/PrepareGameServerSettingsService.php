<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Data\GameServer\OperationActor;
use App\Models\GameServerSettingRevision;
use InvalidArgumentException;
use Throwable;

final readonly class PrepareGameServerSettingsService
{
    public function __construct(
        private GameServerSettingRegistry $registry,
        private GameServerSettingRevisionRepository $revisions,
        private GameServerConfigWriter $writer,
    ) {}

    /** @param array<string, int> $changes */
    public function prepare(array $changes, OperationActor $actor): GameServerSettingRevision
    {
        if ($changes === []) {
            throw new InvalidArgumentException('At least one setting change is required.');
        }

        foreach ($changes as $key => $value) {
            if (! is_string($key) || ! is_int($value)) {
                throw new InvalidArgumentException('Setting changes must contain integer values.');
            }
            $this->registry->validate($key, $value);
        }

        $revision = $this->revisions->create('primary', $changes, $actor);
        try {
            $this->writer->write($changes);
        } catch (Throwable $exception) {
            $this->revisions->markFailed($revision->getKey());

            throw $exception;
        }

        return $revision;
    }
}
