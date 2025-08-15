<?php

namespace App\Enums\Xendit\Common;

enum Currency: string
{
    case IDR = 'IDR';
    case PHP = 'PHP';
    case VND = 'VND';
    case THB = 'THB';
    case SGD = 'SGD';
    case MYR = 'MYR';
    case USD = 'USD';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
