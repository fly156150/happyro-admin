<?php

namespace Tests\Unit\Operations;

use App\Contracts\GameData\ItemRepository;
use App\Data\GameData\ItemQuery;
use App\Services\Operations\ItemGrantItemService;
use Mockery;
use Tests\TestCase;

final class ItemGrantItemServiceTest extends TestCase
{
    public function test_search_uses_server_catalog_and_returns_select_options(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('search')
            ->with(Mockery::on(fn (ItemQuery $query): bool => $query->query === '苹果'
                && $query->range === 'server'
                && $query->perPage === 20))
            ->andReturn(['data' => [[
                'Id' => 512,
                'AegisName' => 'Apple',
                'names' => ['zh-CN' => '苹果', 'en-US' => 'Apple'],
            ]], 'total' => 1]);

        $this->assertSame([[
            'item_id' => 512,
            'aegis_name' => 'Apple',
            'names' => ['zh-CN' => '苹果', 'en-US' => 'Apple'],
        ]], (new ItemGrantItemService($items))->search('苹果'));
    }
}
