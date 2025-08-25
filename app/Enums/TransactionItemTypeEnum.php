<?php

namespace App\Enums;

enum TransactionItemTypeEnum: string
{
    case PICKUP = 'pickup';
    case SURCHARGE = 'surcharge';
    case DISCOUNT = 'discount';
    case TAX = 'tax';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
