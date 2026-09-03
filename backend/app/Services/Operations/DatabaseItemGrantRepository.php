<?php

namespace App\Services\Operations;

use App\Contracts\Operations\ItemGrantRepository;
use App\Exceptions\CharacterNotFoundException;
use Illuminate\Support\Facades\DB;

final class DatabaseItemGrantRepository implements ItemGrantRepository
{
    public function mail(array $grant): int
    {
        return DB::connection('game')->transaction(function () use ($grant): int {
            $character = DB::connection('game')->table(config('happyro.players.character_table', 'char'))->where('char_id', $grant['char_id'])->first(['char_id', 'name']);
            if (! $character) {
                throw new CharacterNotFoundException((int) $grant['char_id']);
            }
            $mailId = DB::connection('game')->table('mail')->insertGetId(['send_name' => $grant['send_name'], 'send_id' => 0, 'dest_name' => $character->name, 'dest_id' => $character->char_id, 'title' => $grant['title'], 'message' => $grant['message'], 'time' => time(), 'status' => 0, 'zeny' => 0, 'type' => 0]);
            DB::connection('game')->table('mail_attachments')->insert(['id' => $mailId, 'index' => 0, 'nameid' => $grant['item_id'], 'amount' => $grant['amount'], 'refine' => 0, 'attribute' => 0, 'identify' => 1, 'card0' => 0, 'card1' => 0, 'card2' => 0, 'card3' => 0, 'unique_id' => 0, 'bound' => $grant['bound'] ?? 0, 'enchantgrade' => 0]);

            return (int) $mailId;
        });
    }
}
