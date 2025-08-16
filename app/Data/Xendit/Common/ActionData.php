<?php

namespace App\Data\Xendit\Common;

use Spatie\LaravelData\Data;

class ActionData extends Data
{
    public function __construct(
        public string $type,       // e.g. REDIRECT_CUSTOMER
        public string $value,      // e.g. xendit.co/example
        public ?string $descriptor = null // e.g. WEB_URL
    ) {}
}
