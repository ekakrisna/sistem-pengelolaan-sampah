<?php

namespace App\Enums\Xendit\Common;

enum ChannelCode: string
{
    // Cards
    case CARDS = 'CARDS';

        // QR
    case QRIS = 'QRIS';

        // Virtual Accounts (contoh umum; tambahkan sesuai kebutuhanmu)
    case BNI_VIRTUAL_ACCOUNT = 'BNI_VIRTUAL_ACCOUNT';
    case BRI_VIRTUAL_ACCOUNT = 'BRI_VIRTUAL_ACCOUNT';
    case MANDIRI_VIRTUAL_ACCOUNT = 'MANDIRI_VIRTUAL_ACCOUNT';
    case PERMATA_VIRTUAL_ACCOUNT = 'PERMATA_VIRTUAL_ACCOUNT';
    case BCA_VIRTUAL_ACCOUNT = 'BCA_VIRTUAL_ACCOUNT';

        // E-wallet
    case OVO = 'OVO';
    case DANA = 'DANA';
    case SHOPEEPAY = 'SHOPEEPAY';
    case LINKAJA = 'LINKAJA';

    case ALFAMART = 'ALFAMART';
    case INDOMARET = 'INDOMARET';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
