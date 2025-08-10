<?php

namespace App\Services\Xendit\Customer;

use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\PatchCustomer;
use Xendit\XenditSdkException;

class CustomerUpdateService
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
     * Update Xendit Customer
     *
     * @param  string      $customerId
     * @param  array       $data           Field yang ingin di-update (partial ok)
     * @param  string|null $forUserId
     * @return array
     */
    public function update(string $customerId, array $data, ?string $forUserId = null): array
    {
        // Hanya masukkan field yang didukung patch (abaikan reference_id)
        $payload = $this->filterNullRecursive([
            'type'           => $data['type']           ?? null, // INDIVIDUAL / BUSINESS (opsional)
            'given_names'    => $data['given_names']    ?? null,
            'middle_name'    => $data['middle_name']    ?? null,
            'surname'        => $data['surname']        ?? null,
            'email'          => $data['email']          ?? null,
            'mobile_number'  => $data['mobile_number']  ?? null, // +62...
            'phone_number'   => $data['phone_number']   ?? null,
            'nationality'    => $data['nationality']    ?? null, // "ID"
            'date_of_birth'  => $data['date_of_birth']  ?? null, // "YYYY-MM-DD"
            'description'    => $data['description']    ?? null,
            'addresses'      => $data['addresses']      ?? null, // array of Address objects
            'metadata'       => $data['metadata']       ?? null, // array
        ]);

        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $req = new PatchCustomer($payload);
            $res = $this->api->updateCustomer($customerId, $forUserId, $req);
            return $this->toArray($res);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit updateCustomer failed: {$e->getMessage()}");
        }
    }

    /** Utils */
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
