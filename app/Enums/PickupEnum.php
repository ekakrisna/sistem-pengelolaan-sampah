<?php

namespace App\Enums;

enum PickupEnum: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
