<?php

namespace App\Enums\Xendit\Common;

enum ItemType: string
{
    case DIGITAL_PRODUCT  = 'DIGITAL_PRODUCT';
    case PHYSICAL_PRODUCT = 'PHYSICAL_PRODUCT';
    case DIGITAL_SERVICE  = 'DIGITAL_SERVICE';
    case PHYSICAL_SERVICE = 'PHYSICAL_SERVICE';
    case FEE              = 'FEE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
