<?php

namespace App\Data\Xendit\Common;

use Spatie\LaravelData\Data;
use Illuminate\Validation\Rule;
use App\Enums\Xendit\Common\ItemType;

class ItemData extends Data
{
    public function __construct(
        public string $reference_id,
        public ItemType $type,
        public string $name,
        public float $net_unit_amount,
        public int $quantity,
        public string $category,
        public ?string $url = null,
        public ?string $image_url = null,
        public ?string $subcategory = null,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?ItemShippingInformationData $shipping_information = null,
    ) {}

    public static function rules(): array
    {
        return [
            'reference_id'        => ['required', 'string', 'min:1', 'max:255'],
            'type'                => ['required', Rule::in(ItemType::values())],
            'name'                => ['required', 'string', 'min:1', 'max:255'],
            'net_unit_amount'     => ['required', 'numeric', 'min:0'],
            'quantity'            => ['required', 'integer', 'min:1'],
            'category'            => ['required', 'string', 'max:255'],

            'url'                 => ['nullable', 'url'],
            'image_url'           => ['nullable', 'url'],
            'subcategory'         => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:255'],
            'metadata'            => ['nullable', 'array'],
            'shipping_information' => ['nullable', 'array'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'reference_id'        => $this->reference_id,
            'type'                => $this->type->value,
            'name'                => $this->name,
            'net_unit_amount'     => $this->net_unit_amount,
            'quantity'            => $this->quantity,
            'url'                 => $this->url,
            'image_url'           => $this->image_url,
            'category'            => $this->category,
            'subcategory'         => $this->subcategory,
            'description'         => $this->description,
            'metadata'            => $this->metadata,
            'shipping_information' => $this->shipping_information?->toArray(),
        ], static fn($v) => $v !== null && $v !== '');
    }
}
