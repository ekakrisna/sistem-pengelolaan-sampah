<?php

namespace App\Enums;

enum PickupEnum: string
{
    case scheduled = 'scheduled';
    case assigned = 'assigned';
    case completed = 'completed';
    case canceled = 'canceled';

}
