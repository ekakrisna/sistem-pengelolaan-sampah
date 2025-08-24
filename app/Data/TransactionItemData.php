<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TransactionItemData extends Data
{

    public function __construct(
        public ?int $id,
        public ?int $pickup_fee_id,
        public ?string $description,
        public float|int $unit_amount,
        public int $qty,
        public float|int|null $line_total,
        public ?array $meta,
    ) {}

    public static function rules(): array
    {
        return [
            'pickup_fee_id' => ['nullable', 'integer'],
            'description'   => ['nullable', 'string'],
            'unit_amount'   => ['required', 'numeric', 'min:0'],
            'qty'           => ['required', 'integer', 'min:1'],
            'line_total'    => ['nullable', 'numeric', 'min:0'],
            'meta'          => ['nullable', 'array'],
        ];
    }

    public function lineTotal(): float
    {
        return (float) $this->qty * (float) $this->unit_amount;
    }
}
