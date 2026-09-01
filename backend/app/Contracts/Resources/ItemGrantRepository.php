<?php

namespace App\Contracts\Resources;

interface ItemGrantRepository
{
    /** @param array<string, mixed> $grant */
    public function mail(array $grant): int;
}
