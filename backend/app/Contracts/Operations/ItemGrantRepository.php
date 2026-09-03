<?php

namespace App\Contracts\Operations;

interface ItemGrantRepository
{
    /** @param array<string, mixed> $grant */
    public function mail(array $grant): int;
}
