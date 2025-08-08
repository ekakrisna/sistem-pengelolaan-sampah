<?php

namespace App\Enums;

enum UserEnum: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Customer = 'customer';
    case SuperAdmin = 'super_admin';
}
