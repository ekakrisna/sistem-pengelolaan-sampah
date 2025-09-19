<?php

namespace App\Enums;

enum XenditStatusPaymentEnum: string
{
    case ACCEPTING_PAYMENTS = 'ACCEPTING_PAYMENTS';
    case REQUIRES_ACTION = 'REQUIRES_ACTION';
    case AUTHORIZED = 'AUTHORIZED';
    case CANCELED = 'CANCELED';
    case EXPIRED = 'EXPIRED';
    case SUCCEEDED = 'SUCCEEDED';
    case FAILED = 'FAILED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
