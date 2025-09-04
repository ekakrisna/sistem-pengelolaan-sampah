<?php

namespace App\Data\Xendit\Common;

use App\Data\Xendit\Common\Card\BillingInformationData;
use App\Data\Xendit\Common\Card\CardDetailsData;
use App\Data\Xendit\Common\Card\RecurringConfigurationData;
use Spatie\LaravelData\Data;

class ChannelPropsData extends Data
{
    public function __construct(
        public ?string $expires_at,
        public ?string $success_return_url,
        public ?string $failure_return_url,
        public ?string $cancel_return_url,
        public ?string $account_mobile_number,
        public ?string $display_name,
        public ?string $mid_label,
        public ?CardDetailsData $card_details = null,
        public ?bool $skip_three_ds,
        public ?string $card_on_file_type,
        public ?BillingInformationData $billing_information = null,
        public ?string $statement_descriptor,
        public ?RecurringConfigurationData $recurring_configuration = null,
        public ?string $payer_name,
        public ?string $virtual_account_number
    ) {}

    public static function rules(): array
    {
        return [
            'expires_at'            => ['nullable', 'string', 'max:50'],
            'success_return_url'    => ['nullable', 'url'],
            'failure_return_url'    => ['nullable', 'url'],
            'cancel_return_url'     => ['nullable', 'url'],
            'account_mobile_number' => ['nullable', 'string', 'max:20'],
            'display_name'          => ['nullable', 'string', 'max:100'],
            'mid_label'             => ['nullable', 'string', 'max:255'],
            'card_details'          => ['nullable', 'array'],
            'skip_three_ds'         => ['nullable', 'boolean'],
            'card_on_file_type'     => ['nullable', 'string', 'max:100'],
            'billing_information'   => ['nullable', 'array'],
            'statement_descriptor'  => ['nullable', 'string', 'max:255'],
            'recurring_configuration' => ['nullable', 'array'],
            'payer_name'            => ['nullable', 'string', 'max:100'],
            'virtual_account_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'expires_at'              => $this->expires_at,
            'success_return_url'      => $this->success_return_url,
            'failure_return_url'      => $this->failure_return_url,
            'cancel_return_url'       => $this->cancel_return_url,
            'account_mobile_number'   => $this->account_mobile_number,
            'display_name'            => $this->display_name,
            'mid_label'               => $this->mid_label,
            'card_details'            => $this->card_details?->toArray(),
            'skip_three_ds'           => $this->skip_three_ds,
            'card_on_file_type'       => $this->card_on_file_type,
            'billing_information'     => $this->billing_information?->toArray(),
            'statement_descriptor'    => $this->statement_descriptor,
            'recurring_configuration' => $this->recurring_configuration?->toArray(),
            'payer_name'              => $this->payer_name,
            'virtual_account_number'   => $this->virtual_account_number,
        ], fn($value) => !is_null($value));
    }
}
