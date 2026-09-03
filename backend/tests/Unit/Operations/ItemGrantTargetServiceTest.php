<?php

namespace Tests\Unit\Operations;

use App\Contracts\Operations\ItemGrantTargetRepository;
use App\Services\Operations\ItemGrantTargetService;
use Mockery;
use Tests\TestCase;

final class ItemGrantTargetServiceTest extends TestCase
{
    public function test_search_delegates_to_repository_with_result_limit(): void
    {
        $repository = Mockery::mock(ItemGrantTargetRepository::class);
        $expected = [['char_id' => 10, 'name' => 'Poring', 'username' => 'alice']];
        $repository->expects('search')->with('Poring', 20)->andReturn($expected);

        $this->assertSame($expected, (new ItemGrantTargetService($repository))->search('Poring'));
    }
}
