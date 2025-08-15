<?php

namespace App\Data\Xendit\Pay\Common;

use Spatie\LaravelData\Data;

class TokenChannelPropsData extends Data
{
    public function __construct(
        public ?string $success_return_url = null,
        public ?string $failure_return_url = null,
    ) {}

    public static function rules(): array
    {
        return [
            'success_return_url' => ['nullable', 'url'],
            'failure_return_url' => ['nullable', 'url'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'success_return_url' => $this->success_return_url,
            'failure_return_url' => $this->failure_return_url,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
