<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use App\Models\Transaction;

class XenditPaymentService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.xendit.api_key');
        $this->apiUrl = config('services.xendit.api_url');
    }

    public function getPaymentChannels(): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("{$this->apiUrl}/payment_channels");

        if (!$response->ok()) {
            throw new \Exception('Failed to fetch payment channels: ' . $response->body());
        }

        return $response->json();
    }

    public function createPayment(string $method, int $amount, int $customerId, array $metadata = [])
    {
        return match ($method) {
            'qris' => $this->createQris($amount, $customerId, $metadata),
            'bca_va' => $this->createVa('BCA', $amount, $customerId, $metadata),
            'bni_va' => $this->createVa('BNI', $amount, $customerId, $metadata),
            'bri_va' => $this->createVa('BRI', $amount, $customerId, $metadata),
            'mandiri_va' => $this->createVa('MANDIRI', $amount, $customerId, $metadata),
            'gopay' => $this->createEwallet('ID_OVO', $amount, $customerId, $metadata),
            'ovo' => $this->createEwallet('ID_OVO', $amount, $customerId, $metadata),
            'dana' => $this->createEwallet('ID_DANA', $amount, $customerId, $metadata),
            default => throw new \InvalidArgumentException("Unsupported payment method: $method"),
        };
    }

    protected function createQris(int $amount, int $customerId, array $metadata)
    {
        $externalId = $this->generateExternalId('qris');
        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->apiUrl}/qr_codes", [
                'external_id' => $externalId,
                'type' => 'DYNAMIC',
                'amount' => $amount,
                'callback_url' => $metadata['callback_url'] ?? config('services.xendit.callback_url'),
                'metadata' => $metadata,
            ]);

        $result = $response->json();
        if (!isset($result['qr_string'])) {
            throw new \Exception('QRIS failed: ' . json_encode($result));
        }

        return $this->storePayment('qris', $customerId, $amount, $externalId, $result['qr_string'], $result);
    }

    protected function createVa(string $bankCode, int $amount, int $customerId, array $metadata)
    {
        $externalId = $this->generateExternalId('va');
        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->apiUrl}/callback_virtual_accounts", [
                'external_id' => $externalId,
                'bank_code' => $bankCode,
                'name' => $metadata['name'] ?? 'Customer',
                'expected_amount' => $amount,
                'is_closed' => true,
                'expiration_date' => now()->addDays(1)->toIso8601String(),
                'callback_url' => $metadata['callback_url'] ?? config('services.xendit.callback_url'),
            ]);

        $result = $response->json();
        if (!isset($result['account_number'])) {
            throw new \Exception('VA failed: ' . json_encode($result));
        }

        return $this->storePayment(strtolower($bankCode) . '_va', $customerId, $amount, $externalId, $result['account_number'], $result);
    }

    protected function createEwallet(string $ewalletType, int $amount, int $customerId, array $metadata)
    {
        $externalId = $this->generateExternalId('ewallet');
        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->apiUrl}/ewallets/charges", [
                'reference_id' => $externalId,
                'currency' => 'IDR',
                'amount' => $amount,
                'checkout_method' => 'ONE_TIME_PAYMENT',
                'channel_code' => $ewalletType,
                'channel_properties' => [
                    'mobile_number' => $metadata['phone_number'] ?? '08123456789',
                ],
                'metadata' => $metadata,
            ]);

        $result = $response->json();
        if (!isset($result['actions']['desktop_web_checkout_url'])) {
            throw new \Exception("E-Wallet failed: " . json_encode($result));
        }

        return $this->storePayment(strtolower(str_replace('ID_', '', $ewalletType)), $customerId, $amount, $externalId, $result['actions']['desktop_web_checkout_url'], $result);
    }

    protected function storePayment(string $method, int $customerId, int $amount, string $externalId, string $invoiceUrl, array $data)
    {
        return Payment::create([
            'customer_id' => $customerId,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => $method,
            'external_id' => $externalId,
            'invoice_url' => $invoiceUrl,
            'xendit_data' => $data,
        ]);
    }

    protected function generateExternalId(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . uniqid();
    }
}
