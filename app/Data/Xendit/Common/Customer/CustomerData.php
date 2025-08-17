<?php

namespace App\Data\Xendit\Common\Customer;

use App\Enums\Xendit\Common\CustomerType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class CustomerData extends Data
{
    public function __construct(
        public string $reference_id,

        #[WithCast(EnumCast::class)]
        public CustomerType $type,

        public ?string $email = null,
        public ?string $mobile_number = null,
        public ?string $phone_number = null,
        public ?CustomerIndividualDetailData $individual_detail = null,
    ) {}

    public static function rules(): array
    {
        return [
            'reference_id'     => ['required', 'string', 'max:255'],
            'type'             => ['required', Rule::in(CustomerType::values())],
            'email'            => ['nullable', 'email', 'max:255'],
            'mobile_number'    => ['nullable', 'string', 'max:30'],
            'phone_number'     => ['nullable', 'string', 'max:30'],
            'individual_detail' => ['required', 'array'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'reference_id'     => $this->reference_id,
            'type'             => $this->type->value,
            'email'            => $this->email,
            'mobile_number'    => $this->mobile_number,
            'phone_number'     => $this->phone_number,
            'individual_detail' => $this->individual_detail?->toArray(),
        ], static fn($v) => $v !== null && $v !== '');
    }
}
