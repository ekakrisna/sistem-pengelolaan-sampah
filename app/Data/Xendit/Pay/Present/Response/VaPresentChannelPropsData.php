<?php

namespace App\Data\Xendit\Pay\Present\Response;

use Spatie\LaravelData\Data;

class VaPresentChannelPropsData extends Data
{
    public function __construct(
        public ?string $expires_at = null
    ) {}
}
