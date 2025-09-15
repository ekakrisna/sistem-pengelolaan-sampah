<?php

namespace App\Enums;

enum StatusPaymentSplitRouteEnum: string
{
    case PLANNED = 'planned';
    case APPLIED = 'applied';
    case SETTLED = 'settled';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
