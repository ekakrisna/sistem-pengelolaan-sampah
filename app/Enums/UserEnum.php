<?php

namespace App\Enums;

enum UserEnum: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Customer = 'customer';
    case SuperAdmin = 'super_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
