<?php

namespace Tests\Unit\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\GameServer\GameServerSettingRepository;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Models\GameServerSettingRevision;
use App\Models\User;
use App\Services\GameServer\ApplyGameServerSettingsService;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\GameServerSettingRegistry;
use App\Services\GameServer\PrepareGameServerSettingsService;
use App\Services\GameServer\SubmitGameServerCommandService;
use Mockery;
use Tests\TestCase;

final class ApplyGameServerSettingsServiceTest extends TestCase
{
    public function test_restores_snapshot_when_command_fails(): void
    {
        $writer = Mockery::mock(GameServerConfigWriter::class);
        $writer->expects('snapshot')->andReturn('old');
        $writer->expects('write')->once()->with(['base_exp_rate' => 200]);
        $writer->expects('restore')->with('old');
        $revision = new GameServerSettingRevision;
        $revision->id = 1;
        $revisions = Mockery::mock(GameServerSettingRevisionRepository::class);
        $revisions->expects('create')->andReturn($revision);
        $revisions->expects('markFailed')->with(1);
        $prepare = new PrepareGameServerSettingsService(new GameServerSettingRegistry, $revisions, $writer);
        $commandRepository = Mockery::mock(GameServerCommandRepository::class);
        $commandRepository->expects('submit')->andThrow(new \RuntimeException('unavailable'));
        $submit = new SubmitGameServerCommandService($commandRepository);
        $execute = new ExecuteGameServerCommandService(Mockery::mock(GameServerCommandRepository::class), Mockery::mock(GameServerGateway::class));

        $service = new ApplyGameServerSettingsService($prepare, $revisions, Mockery::mock(GameServerSettingRepository::class), $writer, $submit, $execute, Mockery::mock(GameServerGateway::class));
        $this->expectException(\RuntimeException::class);
        $service->apply(['base_exp_rate' => 200], 'test', User::factory()->make(['id' => 1]));
    }
}
