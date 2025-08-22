<?php

namespace App\Data\Xendit\PaymentRequest;

use Spatie\LaravelData\Data;

class PaymentRequestListQueryData extends Data
{
    public function __construct(
        public ?string $id = null,
        public ?string $reference_id = null,
        public ?string $customer_id = null,
        public ?int $limit = 10,
        public ?string $after_id = null,
        public ?string $before_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'id'           => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'customer_id'  => ['nullable', 'string', 'max:255'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'after_id'     => ['nullable', 'string', 'max:255'],
            'before_id'    => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Build query array yang siap dipakai ke HTTP client.
     * Menghapus null/'' tapi mempertahankan nilai 0.
     */
    public function toQuery(): array
    {
        $q = [
            'id'           => $this->id,
            'reference_id' => $this->reference_id,
            'customer_id'  => $this->customer_id,
            'limit'        => $this->limit ?? 10,
            'after_id'     => $this->after_id,
            'before_id'    => $this->before_id,
        ];

        return array_filter($q, static fn($v) => $v !== null && $v !== '');
    }
}
