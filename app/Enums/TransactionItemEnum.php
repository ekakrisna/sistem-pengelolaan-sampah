<?php

namespace App\Enums;

enum TransactionItemEnum: string
{
    case pickup = 'pickup';
    case surcharge = 'surcharge';
    case discount = 'discount';
    case tax = 'tax';
    case other = 'other';

}
