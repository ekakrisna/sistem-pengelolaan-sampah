<?php

namespace App\Data\Xendit\Platform\CreateAccount;

use Spatie\LaravelData\Data;

final class Configurations extends Data
{

    public function __construct(
        public ?bool $has_dashboard = null,
        public ?bool $has_withdrawal = null,
        public ?bool $payment_settings_follow_platform = null,
    ) {}

    public static function rules(): array
    {
        return [
            'payment_settings_follow_platform' => ['nullable', 'boolean'],
            'has_withdrawal' => ['nullable', 'boolean'],
            'has_dashboard' => ['nullable', 'boolean'],
        ];
    }

    public function toArray(): array
    {
        return [
            'payment_settings_follow_platform' => $this->payment_settings_follow_platform,
            'has_withdrawal' => $this->has_withdrawal,
            'has_dashboard' => $this->has_dashboard,
        ];
    }

    public function toPayload(): array
    {
        return array_filter([
            'has_dashboard' => $this->has_dashboard,
            'has_withdrawal' => $this->has_withdrawal,
            'payment_settings_follow_platform' => $this->payment_settings_follow_platform,
        ], static fn($v) => $v !== null);
    }
}
