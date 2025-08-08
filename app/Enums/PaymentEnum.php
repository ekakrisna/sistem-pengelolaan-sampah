<?php

namespace App\Enums;

enum PaymentEnum: string
{
    case pending = 'pending';
    case paid = 'paid';
    case failed = 'failed';
    case settling = 'settling';
}
