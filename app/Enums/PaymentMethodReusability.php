<?php
// app/Enums/PaymentMethodReusability.php
namespace App\Enums;

enum PaymentMethodReusability: string
{
    case MULTIPLE_USE = 'MULTIPLE_USE';
    case ONE_TIME_USE = 'ONE_TIME_USE';
}
