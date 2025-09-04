<?php

namespace App\Data;

use App\Enums\Xendit\Common\ChannelCode;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ChannelExpiry extends Data
{
    public function __construct(
        //
    ) {}

    /**
     * Default expiry per channel (sesuaikan bila perlu).
     * - QR/E-Wallet: 15 menit
     * - VA: 24 jam
     * - Retail Outlet: 72 jam
     * - Cards: 15 menit
     * - Fallback: 60 menit
     */
    public static function defaultExpiresAt(ChannelCode $channel): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($channel) {
            // QRIS & e-wallets
            ChannelCode::DANA,
            ChannelCode::OVO,
            ChannelCode::SHOPEEPAY,
            ChannelCode::LINKAJA,
            ChannelCode::ASTRAPAY,
            ChannelCode::JENIUSPAY,
            ChannelCode::SAKUKU => $now->addMinutes(15),

            // Virtual Accounts
            ChannelCode::BCA_VIRTUAL_ACCOUNT,
            ChannelCode::BNI_VIRTUAL_ACCOUNT,
            ChannelCode::BRI_VIRTUAL_ACCOUNT,
            ChannelCode::MANDIRI_VIRTUAL_ACCOUNT,
            ChannelCode::PERMATA_VIRTUAL_ACCOUNT,
            ChannelCode::CIMB_VIRTUAL_ACCOUNT => $now->addHours(24),

            // Retail Outlets
            ChannelCode::ALFAMART,
            ChannelCode::INDOMARET => $now->addHours(72),

            // Cards
            ChannelCode::CARDS => $now->addMinutes(15),

            // Default
            default => $now->addMinutes(60),
        };
    }
}
