<?php

namespace Tests\Unit\GameServer;

use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Data\GameServer\OperationActor;
use App\Models\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GameServerCommandRequestTest extends TestCase
{
    public function test_fingerprint_is_independent_of_associative_key_order(): void
    {
        $first = new GameServerCommandRequest(
            'request-1',
            GameServerCommandType::MonsterSpawn,
            'character',
            '42',
            ['count' => 1, 'monster' => ['id' => 1002, 'name' => 'Poring']],
        );
        $second = new GameServerCommandRequest(
            'request-1',
            GameServerCommandType::MonsterSpawn,
            'character',
            '42',
            ['monster' => ['name' => 'Poring', 'id' => 1002], 'count' => 1],
        );

        $operator = new User;
        $operator->id = 7;
        $actor = OperationActor::admin($operator);

        $this->assertSame($first->fingerprint($actor), $second->fingerprint($actor));
    }

    public function test_target_type_and_id_must_be_provided_together(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GameServerCommandRequest(
            'request-1',
            GameServerCommandType::MonsterSpawn,
            'character',
            null,
            [],
        );
    }
}
