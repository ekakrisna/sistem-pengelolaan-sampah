<?php

namespace App\Data\Xendit\Pay\Card\Response;

use App\Data\Xendit\Pay\Card\BillingInformationData;
use App\Data\Xendit\Pay\Card\RecurringConfigurationData;
use Spatie\LaravelData\Data;

class CardPayResponseChannelPropsData extends Data
{
    public function __construct(
        public ?string $mid_label = null,
        public ?CardResponseDetailsData $card_details = null,
        public ?bool $skip_three_ds = null,
        public ?string $card_on_file_type = null,
        public ?string $failure_return_url = null,
        public ?string $success_return_url = null,
        public ?BillingInformationData $billing_information = null,
        public ?string $statement_descriptor = null,
        public ?RecurringConfigurationData $recurring_configuration = null,
    ) {}
}
