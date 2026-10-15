<?php

namespace Tests\Feature\Operations;

use App\Contracts\GameData\ItemRepository;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MailAttachmentDeliveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function attachmentCases(): array
    {
        return [
            'two weapons' => ['Weapon', 2, [], [1, 1]],
            'five armors' => ['Armor', 5, [], [1, 1, 1, 1, 1]],
            'pet armor' => ['PetArmor', 2, [], [1, 1]],
            'shadow gear' => ['ShadowGear', 2, [], [1, 1]],
            'single egg' => ['PetEgg', 1, [], [1]],
            'unique IDs' => ['Usable', 2, ['UniqueId' => true], [1, 1]],
            'stackable consumables' => ['Healing', 30000, [], [30000]],
            'stackable cards' => ['Card', 10, [], [10]],
        ];
    }

    #[DataProvider('attachmentCases')]
    public function test_delivery_writes_protocol_safe_attachments(string $type, int $amount, array $flags, array $expected): void
    {
        $this->prepareMailDatabase($type, $flags);
        $data = $this->payload($amount);
        $mailId = $this->postJson('/api/operations/item-grants/mail', $data)->assertCreated()->json('data.mail_id');
        $attachments = DB::connection('game')->table('mail_attachments')->where('id', $mailId)->orderBy('index')->get();
        $this->assertSame($expected, $attachments->pluck('amount')->all());
        $this->assertSame(range(0, count($expected) - 1), $attachments->pluck('index')->all());
        $this->assertSame(array_fill(0, count($expected), 1101), $attachments->pluck('nameid')->all());
        $this->assertDatabaseHas('item_grant_records', ['mail_id' => $mailId, 'status' => 'sent', 'amount' => $amount]);

        // Retrying the same request must not add a second mail or another set of attachments.
        $this->postJson('/api/operations/item-grants/mail', $data)->assertCreated()->assertJsonPath('data.mail_id', $mailId);
        $this->assertSame(1, DB::connection('game')->table('mail')->count());
        $this->assertSame(count($expected), DB::connection('game')->table('mail_attachments')->count());
    }

    public function test_six_equipment_pieces_are_rejected_without_partial_delivery(): void
    {
        $this->prepareMailDatabase('Weapon');
        $this->postJson('/api/operations/item-grants/mail', $this->payload(6))->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', '此物品每封邮件最多发放 5 件，请分批发放。');
        $this->assertSame(0, DB::connection('game')->table('mail')->count());
        $this->assertSame(0, DB::connection('game')->table('mail_attachments')->count());
        $this->assertDatabaseCount('item_grant_records', 0);
    }

    private function payload(int $amount): array
    {
        return ['delivery' => 'mail', 'item_id' => 1101, 'char_id' => 10, 'amount' => $amount,
            'title' => '测试发放', 'message' => '领取测试', 'idempotency_key' => 'attachment-test'];
    }

    private function prepareMailDatabase(string $type, array $flags = []): void
    {
        config(['database.connections.game' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('game');
        Schema::connection('game')->create('char', function (Blueprint $table): void {
            $table->integer('char_id')->primary();
            $table->string('name');
        });
        Schema::connection('game')->create('mail', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['send_name', 'dest_name', 'title', 'message'] as $field) {
                $table->string($field);
            }
            foreach (['send_id', 'dest_id', 'time', 'status', 'zeny', 'type'] as $field) {
                $table->integer($field);
            }
        });
        Schema::connection('game')->create('mail_attachments', function (Blueprint $table): void {
            foreach (['id', 'index', 'nameid', 'amount', 'refine', 'attribute', 'identify', 'card0', 'card1', 'card2', 'card3', 'unique_id', 'bound', 'enchantgrade'] as $field) {
                $table->integer($field);
            }
            $table->primary(['id', 'index']);
        });
        DB::connection('game')->table('char')->insert(['char_id' => 10, 'name' => 'Tester']);
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->once()->with(1101, 'server')->andReturn(['Id' => 1101, 'Type' => $type, 'Flags' => $flags]);
        $this->app->instance(ItemRepository::class, $items);
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create(['username' => 'mailtester']);
        $user->roles()->attach($role);
        $this->actingAs($user);
    }
}
