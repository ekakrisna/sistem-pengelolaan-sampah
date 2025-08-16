<?php

namespace App\Enums\Xendit\Common;

enum CustomerType: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case BUSINESS   = 'BUSINESS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
