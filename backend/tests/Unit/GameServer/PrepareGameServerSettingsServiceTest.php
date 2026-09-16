<?php

namespace Tests\Unit\GameServer;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Data\GameServer\OperationActor;
use App\Models\GameServerSettingRevision;
use App\Models\User;
use App\Services\GameServer\GameServerSettingRegistry;
use App\Services\GameServer\PrepareGameServerSettingsService;
use Mockery;
use Tests\TestCase;

final class PrepareGameServerSettingsServiceTest extends TestCase
{
    public function test_validates_persists_and_writes_changes(): void
    {
        $revision = new GameServerSettingRevision(['revision' => 1]);
        $revisions = Mockery::mock(GameServerSettingRevisionRepository::class);
        $revisions->expects('create')->once()->andReturn($revision);
        $writer = Mockery::mock(GameServerConfigWriter::class);
        $writer->expects('write')->once()->with(['base_exp_rate' => 200]);

        $result = (new PrepareGameServerSettingsService(new GameServerSettingRegistry, $revisions, $writer))
            ->prepare(['base_exp_rate' => 200], OperationActor::admin(User::factory()->make(['id' => 1])));

        $this->assertSame($revision, $result);
    }

    public function test_marks_revision_failed_when_config_write_fails(): void
    {
        $revision = new GameServerSettingRevision(['revision' => 1]);
        $revision->id = 7;
        $revisions = Mockery::mock(GameServerSettingRevisionRepository::class);
        $revisions->expects('create')->once()->andReturn($revision);
        $revisions->expects('markFailed')->once()->with(7)->andReturn($revision);
        $writer = Mockery::mock(GameServerConfigWriter::class);
        $writer->expects('write')->once()->andThrow(new \RuntimeException('write failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('write failed');

        (new PrepareGameServerSettingsService(new GameServerSettingRegistry, $revisions, $writer))
            ->prepare(['base_exp_rate' => 200], OperationActor::admin(User::factory()->make(['id' => 1])));
    }
}
