<?php

namespace App\Data\GameServer;

use InvalidArgumentException;
use JsonException;

final readonly class GameServerCommandRequest
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $idempotencyKey,
        public GameServerCommandType $type,
        public ?string $targetType,
        public ?string $targetId,
        public array $payload,
    ) {
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new InvalidArgumentException('The idempotency key must contain between 1 and 64 bytes.');
        }

        if (($targetType === null) !== ($targetId === null)) {
            throw new InvalidArgumentException('The target type and target ID must be provided together.');
        }

        if ($targetType !== null && ($targetType === '' || strlen($targetType) > 32)) {
            throw new InvalidArgumentException('The target type must contain between 1 and 32 bytes.');
        }

        if ($targetId !== null && ($targetId === '' || strlen($targetId) > 64)) {
            throw new InvalidArgumentException('The target ID must contain between 1 and 64 bytes.');
        }
    }

    /** @throws JsonException */
    public function fingerprint(?int $requestedBy): string
    {
        $data = self::normalize([
            'type' => $this->type->value,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'payload' => $this->payload,
            'requested_by' => $requestedBy,
        ]);

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::normalize(...), $value);
        }

        ksort($value);

        return array_map(self::normalize(...), $value);
    }
}
