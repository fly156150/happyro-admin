<?php

namespace App\Data\GameServer;

use Carbon\CarbonImmutable;

final readonly class GameServerCommand
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $result
     */
    public function __construct(
        public string $id,
        public string $idempotencyKey,
        public GameServerCommandType $type,
        public GameServerCommandStatus $status,
        public ?string $targetType,
        public ?string $targetId,
        public array $payload,
        public ?array $result,
        public ?string $errorCode,
        public ?string $errorMessage,
        public ?int $requestedBy,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
