<?php

namespace App\Enums;

enum PickupEnum: string
{
    case scheduled = 'scheduled';
    case completed = 'completed';
    case canceled = 'canceled';

}
