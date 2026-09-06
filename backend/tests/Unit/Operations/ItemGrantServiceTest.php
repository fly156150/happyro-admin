<?php

namespace Tests\Unit\Operations;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\Operations\ItemGrantRecordRepository;
use App\Contracts\Operations\ItemGrantRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\ItemGrantConflictException;
use App\Exceptions\ItemNotFoundException;
use App\Models\User;
use App\Services\Operations\ItemGrantService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ItemGrantServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mail_grant_validates_catalog_and_audits(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $grants = Mockery::mock(ItemGrantRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $items->expects('find')->with(670, 'server')->andReturn(['Id' => 670]);
        $grants->expects('mail')->andReturn(12);
        $audit->expects('write');

        $result = (new ItemGrantService($items, $grants, $this->records(), $audit))->mail(['item_id' => 670, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello', 'idempotency_key' => 'grant-1'], new User, new ClientContext(null, null));

        $this->assertSame(12, $result);
    }

    public function test_mail_grant_rejects_unknown_item_without_writing(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->with(999, 'server')->andReturnNull();
        $service = new ItemGrantService($items, Mockery::mock(ItemGrantRepository::class), $this->records(), Mockery::mock(AuditWriter::class));

        $this->expectException(ItemNotFoundException::class);
        $service->mail(['item_id' => 999, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello', 'idempotency_key' => 'grant-2'], new User, new ClientContext(null, null));
    }

    public function test_mail_grant_replays_the_same_request_without_sending_again(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->once()->with(670, 'server')->andReturn(['Id' => 670]);
        $grants = Mockery::mock(ItemGrantRepository::class);
        $grants->expects('mail')->once()->andReturn(12);
        $audit = Mockery::mock(AuditWriter::class);
        $audit->expects('write')->once();
        $service = new ItemGrantService($items, $grants, $this->records(), $audit);
        $data = ['item_id' => 670, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello', 'idempotency_key' => 'same-key'];

        $this->assertSame(12, $service->mail($data, new User, new ClientContext(null, null)));
        $this->assertSame(12, $service->mail($data, new User, new ClientContext(null, null)));
    }

    public function test_mail_grant_rejects_reusing_a_key_for_different_content(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->once()->andReturn(['Id' => 670]);
        $grants = Mockery::mock(ItemGrantRepository::class);
        $grants->expects('mail')->once()->andReturn(12);
        $audit = Mockery::mock(AuditWriter::class);
        $audit->expects('write')->once();
        $service = new ItemGrantService($items, $grants, $this->records(), $audit);
        $data = ['item_id' => 670, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello', 'idempotency_key' => 'reused-key'];
        $service->mail($data, new User, new ClientContext(null, null));

        $this->expectException(ItemGrantConflictException::class);
        $service->mail([...$data, 'amount' => 2], new User, new ClientContext(null, null));
    }

    private function records(): ItemGrantRecordRepository
    {
        return $this->app->make(ItemGrantRecordRepository::class);
    }
}
