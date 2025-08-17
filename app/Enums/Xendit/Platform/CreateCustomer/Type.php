<?php

namespace App\Enums\Xendit\Platform\CreateCustomer;

enum Type: string
{
    case MANAGED = 'MANAGED';
    case OWNED   = 'OWNED';
    case CUSTOM  = 'CUSTOM';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
