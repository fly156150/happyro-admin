<?php

namespace Tests\Unit\Operations;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\Operations\ItemGrantRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\ItemNotFoundException;
use App\Models\User;
use App\Services\Operations\ItemGrantService;
use Mockery;
use Tests\TestCase;

final class ItemGrantServiceTest extends TestCase
{
    public function test_mail_grant_validates_catalog_and_audits(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $grants = Mockery::mock(ItemGrantRepository::class);
        $audit = Mockery::mock(AuditWriter::class);
        $items->expects('find')->with(670, 'server')->andReturn(['Id' => 670]);
        $grants->expects('mail')->andReturn(12);
        $audit->expects('write');

        $result = (new ItemGrantService($items, $grants, $audit))->mail(['item_id' => 670, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello'], new User, new ClientContext(null, null));

        $this->assertSame(12, $result);
    }

    public function test_mail_grant_rejects_unknown_item_without_writing(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->with(999, 'server')->andReturnNull();
        $service = new ItemGrantService($items, Mockery::mock(ItemGrantRepository::class), Mockery::mock(AuditWriter::class));

        $this->expectException(ItemNotFoundException::class);
        $service->mail(['item_id' => 999, 'char_id' => 3, 'amount' => 1, 'title' => 'Gift', 'message' => 'Hello'], new User, new ClientContext(null, null));
    }
}
