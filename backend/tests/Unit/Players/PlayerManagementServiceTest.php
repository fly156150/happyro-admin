<?php

namespace Tests\Unit\Players;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Players\PlayerAccountRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\PlayerAccountNotFoundException;
use App\Models\User;
use App\Services\Players\PlayerManagementService;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery;
use Tests\TestCase;

final class PlayerManagementServiceTest extends TestCase
{
    public function test_update_writes_audit_and_delegates_to_repository(): void
    {
        $repository = Mockery::mock(PlayerAccountRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $account = (object) ['account_id' => 7];
        $repository->expects('update')->with(7, ['state' => 1])->andReturn($account);
        $audit->expects('write')->with('player.updated', Mockery::type(ClientContext::class), Mockery::type(User::class), null, ['account_id' => 7, 'fields' => ['state']]);
        $result = (new PlayerManagementService($repository, $audit, Mockery::mock(Hasher::class)))->update(7, ['state' => 1], new User, new ClientContext('127.0.0.1', 'test'));
        $this->assertSame($account, $result);
    }

    public function test_register_delegates_and_audits(): void
    {
        $repository = Mockery::mock(PlayerAccountRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $account = (object) ['account_id' => 9];
        $repository->expects('create')->with(['userid' => 'test'])->andReturn($account);
        $audit->expects('write')->with('player.registered', Mockery::type(ClientContext::class), Mockery::type(User::class), null, ['account_id' => 9]);
        $result = (new PlayerManagementService($repository, $audit, Mockery::mock(Hasher::class)))->register(['userid' => 'test'], new User, new ClientContext(null, null));
        $this->assertSame($account, $result);
    }

    public function test_reset_password_hashes_when_configured(): void
    {
        config(['happyro.players.password_hash' => true]);
        $repository = Mockery::mock(PlayerAccountRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('make')->with('secret')->andReturn('hashed');
        $repository->expects('update')->with(7, ['user_pass' => 'hashed'])->andReturn((object) ['account_id' => 7]);
        $audit->expects('write')->twice();
        (new PlayerManagementService($repository, $audit, $hasher))->resetPassword(7, 'secret', new User, new ClientContext(null, null));
    }

    public function test_batch_delete_deduplicates_ids_and_ignores_missing_accounts(): void
    {
        $repository = Mockery::mock(PlayerAccountRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $repository->expects('delete')->with(7)->andReturn(1);
        $repository->expects('delete')->with(8)->andThrow(new PlayerAccountNotFoundException(8));
        $audit->expects('write')->with('player.batch_delete', Mockery::type(ClientContext::class), Mockery::type(User::class), null, ['account_ids' => [7, 7, 8], 'count' => 1]);

        $result = (new PlayerManagementService($repository, $audit, Mockery::mock(Hasher::class)))->batch('delete', [7, 7, 8], new User, new ClientContext(null, null));

        $this->assertSame(1, $result);
    }
}
