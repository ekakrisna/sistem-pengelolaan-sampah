<?php

namespace App\Enums;

enum PickupScheduleEnum: string
{
    case monday = 'monday';
    case tuesday = 'tuesday';
    case wednesday = 'wednesday';
    case thursday = 'thursday';
    case friday = 'friday';
    case saturday = 'saturday';
    case sunday = 'sunday';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
