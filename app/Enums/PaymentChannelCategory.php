<?php

namespace App\Enums;

enum PaymentChannelCategory: string
{
    case EWALLET = 'EWALLET';
    case QRIS = 'QRIS';
    case VIRTUAL_ACCOUNT = 'VIRTUAL_ACCOUNT';
    case DIRECT_DEBIT_PH = 'DIRECT_DEBIT_PH';
    case OVER_THE_COUNTER = 'OVER_THE_COUNTER';
    case CARDS = 'CARDS';
}
