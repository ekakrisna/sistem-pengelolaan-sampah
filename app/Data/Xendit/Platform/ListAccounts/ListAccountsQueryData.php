<?php

namespace App\Data\Xendit\Platform\ListAccounts;

use App\Enums\Xendit\Platform\CreateCustomer\Type;
use App\Enums\Xendit\Platform\ListAccounts\Status;
use Spatie\LaravelData\Data;

final class ListAccountsQueryData extends Data
{
    /**
     * @param string[]|null         $email
     * @param (Status|string)[]|null $status
     */
    public function __construct(
        public ?string $email = null,   // e.g. ["a@ex.com","b@ex.com"]
        public ?string $status = null,  // e.g. ["LIVE","REGISTERED"] atau [Status::LIVE, Status::REGISTERED]
        public ?string $type = null,    // enum tunggal OK (Xendit: MANAGED|OWNED|CUSTOM)
        public ?string $public_profile_business_name = null,
        public ?string $created_gte = null,
        public ?string $created_lte = null,
        public ?string $updated_gte = null,
        public ?string $updated_lte = null,
        public ?int $limit = 10,
        public ?string $before_id = null,
        public ?string $after_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'email'    => ['nullable', 'string'],
            'status'    => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'public_profile_business_name' => ['nullable', 'string', 'max:255'],

            'created_gte' => ['nullable', 'string'],
            'created_lte' => ['nullable', 'string'],
            'updated_gte' => ['nullable', 'string'],
            'updated_lte' => ['nullable', 'string'],

            'limit'     => ['nullable', 'integer', 'min:1', 'max:50'],
            'before_id' => ['nullable', 'string', 'max:255'],
            'after_id'  => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Susun query params sesuai spesifikasi Xendit */
    public function toQuery(): array
    {
        $q = [
            'email'  => $this->email ?: null,
            'status' => $this->status,
            'type'   => $this->type,
            'public_profile.business_name' => $this->public_profile_business_name,

            'created[gte]' => $this->created_gte,
            'created[lte]' => $this->created_lte,
            'updated[gte]' => $this->updated_gte,
            'updated[lte]' => $this->updated_lte,

            'limit'     => $this->limit,
            'before_id' => $this->before_id,
            'after_id'  => $this->after_id,
        ];

        return array_filter($q, static fn($v) => !($v === null || $v === [] || $v === ''));
    }
}
