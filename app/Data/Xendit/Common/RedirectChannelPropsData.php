<?php

namespace App\Data\Xendit\Common;

use Spatie\LaravelData\Data;

class RedirectChannelPropsData extends Data
{
    public function __construct(
        public ?string $expires_at = null,
        public ?string $success_return_url = null,
        public ?string $failure_return_url = null,
        public ?string $cancel_return_url = null,
    ) {}
}
