<?php

namespace Tests\Unit\Resources;

use App\Contracts\Resources\ItemCatalog;
use Tests\TestCase;

final class LocalItemCatalogTest extends TestCase
{
    public function test_catalog_reads_local_renewal_snapshot(): void
    {
        /** @var ItemCatalog $catalog */
        $catalog = app(ItemCatalog::class);
        $result = $catalog->search('Gold_Coin_Moneybag', null, 1, 10);

        $this->assertSame(1, $result['total']);
        $this->assertSame(670, $result['data'][0]['Id']);
        $this->assertSame('Gold_Coin_Moneybag', $result['data'][0]['AegisName']);
        $this->assertSame('金币袋', $result['data'][0]['names']['zh-CN']);
        $this->assertSame('Bag of Gold Coins', $result['data'][0]['names']['en-US']);
        $this->assertNotNull($catalog->iconPath(670));
    }

    public function test_description_removes_client_formatting_codes(): void
    {
        /** @var ItemCatalog $catalog */
        $catalog = app(ItemCatalog::class);
        $description = $catalog->description(501);

        $this->assertNotEmpty($description);
        $this->assertStringNotContainsString('^', implode('', $description));
        $this->assertNotContains('_', $description);
    }
}
