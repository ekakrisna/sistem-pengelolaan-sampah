<?php

namespace App\Enums;

enum PaymentEnum: string
{
    case initiated = 'initiated';
    case awaiting_payment = 'awaiting_payment';
    case succeeded = 'succeeded';
    case failed = 'failed';
    case expired = 'expired';
    case canceled = 'canceled';
    case refund_pending = 'refund_pending';
    case refunded = 'refunded';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
