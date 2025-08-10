<?php

namespace App\Services\Xendit\Customer;

use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\XenditSdkException;

class CustomerCreateService
{
    protected CustomerApi $api;
    protected ?string $defaultForUserId;

    public function __construct()
    {
        $apiKey = config('services.xendit.api_key')
            ?: env('XENDIT_API_KEY')
            ?: env('XENDIT_SECRET_KEY');

        if (empty($apiKey)) {
            throw new \RuntimeException('Xendit API key is empty. Set services.xendit.api_key or XENDIT_API_KEY.');
        }

        Configuration::setXenditKey($apiKey);
        $this->api = new CustomerApi();
        $this->defaultForUserId = config('services.xendit.for_user_id') ?: null;
    }

    /**
     * Create Xendit Customer
     *
     * @param  array       $data  // lihat contoh payload di bawah
     * @param  string|null $idempotencyKey
     * @param  string|null $forUserId
     * @return array
     */
    public function create(array $data, ?string $idempotencyKey = null, ?string $forUserId = null): array
    {
        // reference_id optional → generate kalau kosong
        $data['reference_id'] = $this->ensureReferenceId($data['reference_id'] ?? null, 'CUST');

        // Hapus null values agar payload bersih
        $payload = $this->filterNullRecursive([
            'reference_id'   => $data['reference_id'] ?? null,
            'type'           => $data['type'] ?? null,              // optional: INDIVIDUAL / BUSINESS
            'given_names'    => $data['given_names'] ?? null,
            'middle_name'    => $data['middle_name'] ?? null,
            'surname'        => $data['surname'] ?? null,
            'email'          => $data['email'] ?? null,
            'mobile_number'  => $data['mobile_number'] ?? null,     // format +62...
            'nationality'    => $data['nationality'] ?? null,       // e.g. "ID"
            'date_of_birth'  => $data['date_of_birth'] ?? null,     // "YYYY-MM-DD"
            'description'    => $data['description'] ?? null,
            'phone_number'   => $data['phone_number'] ?? null,
            'addresses'      => $data['addresses'] ?? null,         // array of Address objects
            'metadata'       => $data['metadata'] ?? null,          // array
        ]);

        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();
        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $req = new \Xendit\Customer\CustomerRequest($payload);
            $res = $this->api->createCustomer($idempotencyKey, $forUserId, $req);

            // Normalize ke array supaya aman di consumer
            return $this->toArray($res);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit create customer failed: {$e->getMessage()}");
        }
    }

    protected function ensureReferenceId(?string $ref, string $prefix): string
    {
        $ref = trim((string) $ref);
        return $ref !== '' ? $ref : $this->generateExternalId($prefix);
    }

    // mengikuti pola yang kamu minta
    protected function generateExternalId(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . uniqid();
    }

    protected function filterNullRecursive(array $data): array
    {
        $filtered = array_filter($data, static fn($v) => !is_null($v));
        foreach ($filtered as $k => $v) {
            if (is_array($v)) {
                $filtered[$k] = self::filterNullRecursive($v);
            }
        }
        return $filtered;
    }

    protected function toArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
