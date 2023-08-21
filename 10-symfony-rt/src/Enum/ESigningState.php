<?php

namespace App\Enum;

enum ESigningState: int
{
    case PENDING = 0;
    case SOLD = 1;
    case LOST = 2;
    case INACTIVATED = 3;

    public static function values(): array
    {
        return array_map(fn($state) => $state->value, static::cases());
    }
}
