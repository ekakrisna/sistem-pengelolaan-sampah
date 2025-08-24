<?php

namespace App\Enums;

enum StatusPaymentEnum: string
{
    case PENDING = 'pending';
    case SETTLING = 'settling';
    case PAID = 'paid';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
