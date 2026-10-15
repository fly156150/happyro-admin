<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class GameMailText implements ValidationRule
{
    // Source: happyro-server/src/common/mmo.hpp, excluding the terminating NUL.
    public function __construct(private int $maximum, private string $label) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            $fail($this->label.'包含无效字符。');
        } elseif (strlen($value) > $this->maximum) {
            $fail($this->label.'最多 '.$this->maximum.' 个 UTF-8 字节（汉字通常占 3 字节）。');
        }
    }
}
