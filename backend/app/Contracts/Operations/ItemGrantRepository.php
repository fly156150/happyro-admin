<?php

namespace App\Contracts\Operations;

interface ItemGrantRepository
{
    /** @param array{item_id: int, char_id: int, amount: int, send_name: string, title: string, message: string, attachment_amounts: list<int>, bound?: bool} $grant */
    public function mail(array $grant): int;
}
