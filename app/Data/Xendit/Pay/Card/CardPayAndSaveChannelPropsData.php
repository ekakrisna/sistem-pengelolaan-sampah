<?php

namespace App\Data\Xendit\Pay\Card;

use Spatie\LaravelData\Data;

class CardPayAndSaveChannelPropsData extends Data
{
    public function __construct(
        public ?string $mid_label = null,
        public CardDetailsData $card_details,
        public ?bool $skip_three_ds = null,
        public ?string $card_on_file_type = null,
        public ?string $failure_return_url = null,
        public ?string $success_return_url = null,
        public ?BillingInformationData $billing_information = null,
        public ?string $statement_descriptor = null,
        public ?RecurringConfigurationData $recurring_configuration = null,
    ) {}

    public static function rules(): array
    {
        return [
            'mid_label'             => ['nullable', 'string', 'max:255'],
            'card_details'          => ['required', 'array'],
            'skip_three_ds'         => ['nullable', 'boolean'],
            'card_on_file_type'     => ['nullable', 'string', 'max:100'],
            'failure_return_url'    => ['nullable', 'url'],
            'success_return_url'    => ['nullable', 'url'],
            'billing_information'   => ['nullable', 'array'],
            'statement_descriptor'  => ['nullable', 'string', 'max:255'],
            'recurring_configuration' => ['nullable', 'array'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'mid_label'              => $this->mid_label,
            'card_details'           => $this->card_details->toArray(),
            'skip_three_ds'          => $this->skip_three_ds,
            'card_on_file_type'      => $this->card_on_file_type,
            'failure_return_url'     => $this->failure_return_url,
            'success_return_url'     => $this->success_return_url,
            'billing_information'    => $this->billing_information?->toArray(),
            'statement_descriptor'   => $this->statement_descriptor,
            'recurring_configuration' => $this->recurring_configuration?->toArray(),
        ], static fn($v) => $v !== null && $v !== '' && $v !== []);
    }
}
