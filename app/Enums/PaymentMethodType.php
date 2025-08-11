<?php
// app/Enums/PaymentMethodType.php
namespace App\Enums;

enum PaymentMethodType: string
{
    case CARD          = 'CARD';
    case EWALLET       = 'EWALLET';
    case DIRECT_DEBIT  = 'DIRECT_DEBIT';
    // (yang lain ada di SDK, tapi PM create umumnya 3 ini)
}
