<?php

namespace Tests\Feature\Operations;

use App\Contracts\GameData\ItemRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\Operations\ItemGrantRepository;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ItemGrantDeliveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inventory_delivery_identifies_equipment_for_an_online_character(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->with(1101, 'server')->andReturn(['Id' => 1101, 'Type' => 'Weapon']);
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')
            ->with(Mockery::on(fn (GameServerCommand $command): bool => $command->payload === [
                'item_id' => 1101,
                'amount' => 1,
                'identify' => true,
            ]))
            ->andReturn(new GameServerCommandResult(['char_id' => 150002, 'item_id' => 1101, 'amount' => 1]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 1101,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-grant-1',
        ])->assertCreated()->assertJsonPath('data.delivery', 'inventory')->assertJsonPath('data.result.item_id', 1101);
    }

    public function test_mail_remains_the_default_delivery_method(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'item_id' => 1101,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'mail-grant-1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'message']);
    }

    public function test_inventory_delivery_retries_after_character_offline_409(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->twice()->with(501, 'server')->andReturn(['Id' => 501, 'Type' => 'Healing']);
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->twice()->andReturnUsing(function () {
            static $attempts = 0;
            $attempts++;
            if ($attempts === 1) {
                throw new GameServerGatewayException('character_offline', 'Game server rejected the request.');
            }

            return new GameServerCommandResult(['char_id' => 150002, 'item_id' => 501, 'amount' => 1]);
        });
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 501,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-retry-1',
        ])->assertConflict()
            ->assertJsonPath('error.code', 'character_offline')
            ->assertJsonPath('error.message', '角色不在线。');
        $this->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 501,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-retry-1',
        ])->assertCreated()->assertJsonPath('data.result.item_id', 501);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'inventory-retry-1',
            'status' => 'succeeded',
        ]);
    }

    public static function invalidMailText(): array
    {
        return [
            'Chinese title' => ['title', str_repeat('中', 14), '邮件标题最多 39 个 UTF-8 字节（汉字通常占 3 字节）。'],
            'ASCII title' => ['title', str_repeat('a', 40), '邮件标题最多 39 个 UTF-8 字节（汉字通常占 3 字节）。'],
            'Chinese body' => ['message', str_repeat('中', 167), '邮件内容最多 499 个 UTF-8 字节（汉字通常占 3 字节）。'],
            'NUL' => ['title', "a\0b", '邮件标题包含无效字符。'],
        ];
    }

    #[DataProvider('invalidMailText')]
    public function test_invalid_mail_text_is_rejected_before_writing(string $field, string $value, string $message): void
    {
        $this->app->instance(ItemGrantRepository::class, Mockery::mock(ItemGrantRepository::class));
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            ...$this->mailPayload(), $field => $value,
        ])->assertUnprocessable()->assertJsonPath("errors.$field.0", $message);
        $this->assertDatabaseCount('item_grant_records', 0);
    }

    public function test_multiple_pet_eggs_are_rejected_without_creating_a_mail_or_record(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->andReturn(['Id' => 9001, 'Type' => 'PetEgg']);
        $this->app->instance(ItemRepository::class, $items);
        $this->app->instance(ItemGrantRepository::class, Mockery::mock(ItemGrantRepository::class));
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            ...$this->mailPayload(), 'amount' => 2,
        ])->assertUnprocessable()->assertJsonPath('errors.amount.0', '宠物蛋邮件每次只能发放 1 个。');
        $this->assertDatabaseCount('item_grant_records', 0);
    }

    public function test_single_pet_egg_and_boundary_utf8_text_are_sent_intact(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->andReturn(['Id' => 9001, 'Type' => 'PetEgg']);
        $this->app->instance(ItemRepository::class, $items);
        $data = [...$this->mailPayload(), 'title' => str_repeat('中', 13), 'message' => str_repeat('中', 166).'a'];
        $grants = Mockery::mock(ItemGrantRepository::class);
        $grants->expects('mail')->with(Mockery::on(fn (array $grant): bool => $grant['amount'] === 1
            && $grant['title'] === $data['title'] && $grant['message'] === $data['message']
            && $grant['send_name'] === 'ab'.str_repeat('中', 7)))->andReturn(123);
        $this->app->instance(ItemGrantRepository::class, $grants);
        $user = $this->superAdmin();
        $user->username = 'ab'.str_repeat('中', 7);
        $this->actingAs($user)->postJson('/api/operations/item-grants/mail', $data)
            ->assertCreated()->assertJsonPath('data.mail_id', 123);
        $this->assertDatabaseHas('item_grant_records', ['mail_id' => 123, 'status' => 'sent', 'amount' => 1]);
    }

    public function test_long_sender_name_is_rejected_before_writing(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->andReturn(['Id' => 9001, 'Type' => 'PetEgg']);
        $this->app->instance(ItemRepository::class, $items);
        $this->app->instance(ItemGrantRepository::class, Mockery::mock(ItemGrantRepository::class));
        $user = $this->superAdmin();
        $user->username = str_repeat('中', 8);
        $this->actingAs($user)->postJson('/api/operations/item-grants/mail', $this->mailPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('send_name');
        $this->assertDatabaseCount('item_grant_records', 0);
    }

    public function test_pet_egg_inventory_delivery_is_rejected_before_contacting_game_server(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->andReturn(['Id' => 9001, 'Type' => 'PetEgg']);
        $this->app->instance(ItemRepository::class, $items);
        $this->app->instance(GameServerGateway::class, Mockery::mock(GameServerGateway::class));
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            ...$this->mailPayload(), 'delivery' => 'inventory',
        ])->assertUnprocessable()->assertJsonPath('errors.delivery.0', '宠物蛋仅支持邮件发放，每次 1 个。');
        $this->assertDatabaseCount('game_server_commands', 0);
    }

    public function test_server_item_rejection_returns_422_instead_of_502(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->andReturn(['Id' => 501, 'Type' => 'Healing']);
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->andThrow(new GameServerGatewayException('item_not_grantable', 'Rejected'));
        $this->app->instance(GameServerGateway::class, $gateway);
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            ...$this->mailPayload(), 'delivery' => 'inventory', 'item_id' => 501,
        ])->assertUnprocessable()->assertJsonPath('error.code', 'item_not_grantable');
    }

    private function mailPayload(): array
    {
        return ['delivery' => 'mail', 'item_id' => 9001, 'char_id' => 150002, 'amount' => 1,
            'title' => '物品发放', 'message' => '测试邮件', 'idempotency_key' => 'mail-validation'];
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
