<?php

namespace App\Data\Xendit\Pay\Card;

use Spatie\LaravelData\Data;

class RecurringConfigurationData extends Data
{
    public function __construct(
        public ?string $recurring_expiry = null,
        public ?int $recurring_frequency = null,
    ) {}

    public static function rules(): array
    {
        return [
            'recurring_expiry'    => ['nullable', 'string', 'max:20'],
            'recurring_frequency' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
