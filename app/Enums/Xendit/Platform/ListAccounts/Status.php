<?php

namespace App\Enums\Xendit\Platform\ListAccounts;

enum Status: string
{
    case INVITED = 'INVITED';
    case REGISTERED = 'REGISTERED';
    case AWAITING_DOCS  = 'AWAITING_DOCS';
    case LIVE  = 'LIVE';
    case SUSPENDED  = 'SUSPENDED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
