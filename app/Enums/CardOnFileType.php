<?php
// app/Enums/CardOnFileType.php
namespace App\Enums;

enum CardOnFileType: string
{
    case CUSTOMER_UNSCHEDULED = 'CUSTOMER_UNSCHEDULED';
    case MERCHANT_UNSCHEDULED = 'MERCHANT_UNSCHEDULED';
    case RECURRING            = 'RECURRING';
}
