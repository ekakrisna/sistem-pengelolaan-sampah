<?php

namespace App\Enums;

enum UserEnum: string
{
    case admin = 'admin';
    case petugas = 'petugas';
    case customer = 'customer';
    case super_admin = 'super_admin';

}
