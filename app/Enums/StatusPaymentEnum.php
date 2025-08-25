<?php

namespace App\Enums;

enum StatusPaymentEnum: string
{
    case INITIATED = 'initiated';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
    case REFUND_PENDING = 'refund_pending';
    case REFUNDED = 'refunded';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
