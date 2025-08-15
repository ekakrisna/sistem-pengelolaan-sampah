<?php

namespace App\Enums\Xendit\Common;

enum CaptureMethod: string
{
    case AUTOMATIC = 'AUTOMATIC';
    case MANUAL    = 'MANUAL';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
