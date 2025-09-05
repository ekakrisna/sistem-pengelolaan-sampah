<?php

namespace App\Enums;

enum TransactionEnum: string
{
    case draft = 'draft';
    case pending = 'pending';
    case paid = 'paid';
    case partially_paid = 'partially_paid';
    case expired = 'expired';
    case canceled = 'canceled';
    case refunded = 'refunded';
}
