<?php

namespace App\Enums\Xendit\Common;

enum Country: string
{
    case ID = 'ID';
    case PH = 'PH';
    case VN = 'VN';
    case TH = 'TH';
    case SG = 'SG';
    case MY = 'MY';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
