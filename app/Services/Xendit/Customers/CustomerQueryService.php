<?php

namespace App\Services\Xendit\Customers;

use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\XenditSdkException;

class CustomerQueryService
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

    /** GET /customers/{id} */
    public function getById(string $customerId, ?string $forUserId = null): array
    {
        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $res = $this->api->getCustomer($customerId, $forUserId);
            return $this->toArray($res);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit get customer failed: {$e->getMessage()}");
        }
    }

    /**
     * GET /customers?reference_id=...
     * Catatan: endpoint list memang berdasarkan reference_id. (Xendit docs)
     */
    public function listByReferenceId(
        string $referenceId,
        ?string $forUserId = null
        // kalau butuh nanti bisa tambah: ?int $limit = null, ?string $afterId = null, ?string $beforeId = null
    ): array {
        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $res = $this->api->getCustomerByReferenceID($referenceId, $forUserId);

            return $this->toArray($res);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit list customers by reference_id failed: {$e->getMessage()}");
        }
    }

    /** Utilities */
    protected function toArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
