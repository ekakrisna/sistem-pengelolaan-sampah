<?php

namespace App\Services\Xendits\PaymentChannel;

use App\Services\Xendits\Http\XenditService;

class PaymentChannelService extends XenditService
{
    /**
     * Get available payment channels
     *
     * @return array
     */
    public function getPaymentChannels(): array
    {
        return $this->get("/payment_channels");
    }
}
