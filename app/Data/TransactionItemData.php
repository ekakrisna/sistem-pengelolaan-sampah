<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TransactionItemData extends Data
{
    public ?int $id;
    public ?int $pickup_fee_id;
    public ?string $description;
    public float|int|string $unit_amount;
    public int $qty;
    public float|int|string|null $line_total;
    public ?array $meta;

    // public function __construct(
    //     public ?int    $pickup_fee_id,          // opsional (relasi ke fee tabel kamu)
    //     public ?string $description,            // deskripsi item (wajib di UX, tapi opsional di API)
    //     public float|int|string $unit_amount,   // 10000 atau "10000.00"
    //     public int     $qty,                    // minimal 1
    //     public ?array  $meta,                   // bebas (json)
    // ) {}

    public static function rules(): array
    {
        return [
            'pickup_fee_id' => ['nullable', 'integer'],
            'description'   => ['nullable', 'string'],
            'unit_amount'   => ['required', 'numeric', 'min:0'],
            'qty'           => ['required', 'integer', 'min:1'],
            'meta'          => ['nullable', 'array'],
        ];
    }

    public function lineTotal(): float
    {
        return (float) $this->qty * (float) $this->unit_amount;
    }
}
