<?php

namespace App\Enums;

enum PickupFeeEnum: string
{
    case day = 'day';
    case week = 'week';
    case month = 'month';
    case year = 'year';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
