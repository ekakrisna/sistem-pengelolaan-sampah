<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use Carbon\Carbon;

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

    public function createPaymentByChannelCategory(
        string $channelCategory,
        string $channelCode,
        int $amount,
        int $customerId,
        array $metadata = []
    ) {
        return match (strtoupper($channelCategory)) {
            'QRIS'             => $this->createQris($amount, $customerId, $metadata),
            'VIRTUAL_ACCOUNT' => $this->createVa($channelCode, $amount, $customerId, $metadata),
            'RETAIL_OUTLET'   => $this->createRetailOutlet($channelCode, $amount, $customerId, $metadata),
            'EWALLET'         => $this->createEwallet('ID_' . strtoupper($channelCode), $amount, $customerId, $metadata),
            default           => throw new \InvalidArgumentException("Unsupported channel category: $channelCategory"),
        };
    }

    protected function createQris(int $amount, int $customerId, array $metadata)
    {
        if (empty($metadata['pickup_id'])) {
            throw new \InvalidArgumentException('pickup_id is required in metadata');
        }

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

    protected function createVa(
        string $bankCode,
        int $amount,
        int $customerId,
        array $metadata
    ) {
        if (empty($metadata['pickup_id'])) {
            throw new \InvalidArgumentException('pickup_id is required in metadata for VA');
        }

        if (empty($metadata['name'])) {
            throw new \InvalidArgumentException('name is required in metadata for VA');
        }

        // sisipkan pickup_id ke external_id untuk fallback webhook
        $externalId = $this->generateExternalId('va') . '-p' . $metadata['pickup_id'];

        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->apiUrl}/callback_virtual_accounts", [
                'external_id' => $externalId,
                'bank_code' => $bankCode,
                'name' => $metadata['name'],
                'expected_amount' => $amount,
                'is_closed' => true,
                'expiration_date' => Carbon::now()->addDay()->toIso8601String(),
                'metadata' => $metadata
            ]);

        $result = $response->json();


        if (!isset($result['account_number'])) {
            throw new \Exception('VA failed: ' . json_encode($result));
        }

        // **PENTING**: simpan metadata yang kita kirim ke xendit_data agar bisa diambil webhook
        $resultToStore = $result;
        $resultToStore['metadata'] = $metadata;

        return $this->storePayment(
            strtolower($bankCode) . '_va',
            $customerId,
            $amount,
            $externalId,
            $result['account_number'],
            $resultToStore
        );
    }

    protected function createRetailOutlet(string $channelCode, int $amount, int $customerId, array $metadata)
    {
        if (empty($metadata['pickup_id'])) {
            throw new \InvalidArgumentException('pickup_id is required in metadata for VA');
        }

        if (empty($metadata['name'])) {
            throw new \InvalidArgumentException('name is required in metadata for VA');
        }

        $externalId = $this->generateExternalId('retail') . '-p' . $metadata['pickup_id'];

        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->apiUrl}/fixed_payment_code", [
                'external_id' => $externalId,
                'retail_outlet_name' => $channelCode,
                'name' => $metadata['name'],
                'expected_amount' => $amount,
            ]);

        $result = $response->json();

        if (!isset($result['payment_code'])) {
            throw new \Exception('Retail Outlet failed: ' . json_encode($result));
        }

        // **PENTING**: simpan metadata yang kita kirim ke xendit_data agar bisa diambil webhook
        $resultToStore = $result;
        $resultToStore['metadata'] = $metadata;

        return $this->storePayment(
            strtolower($channelCode),
            $customerId,
            $amount,
            $externalId,
            $result['payment_code'],
            $resultToStore
        );
    }

    protected function createEwallet(
        string $ewalletCode,
        int $amount,
        int $customerId,
        array $metadata
    ) {

        if (empty($metadata['pickup_id'])) {
            throw new \InvalidArgumentException('pickup_id is required in metadata for VA');
        }

        $externalId = $this->generateExternalId('ewallet');

        // Pastikan channel_code valid (ID_OVO, ID_DANA, dst.)
        $channelCode = $this->mapEwalletChannelCode($ewalletCode);

        $payload = [
            'reference_id'    => $externalId,
            'currency'        => 'IDR',
            'amount'          => $amount,
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code'    => $channelCode,
            'channel_properties' => $this->buildEwalletChannelProperties($channelCode, $metadata),
            'metadata'        => $metadata,
        ];

        $response = Http::withBasicAuth($this->apiKey, '')
            ->withHeaders([
                // Aman untuk semua e-wallet; Xendit rekomendasikan set di header atau di Dashboard
                'x-callback-url' => $metadata['callback_url'] ?? config('services.xendit.callback_url'),
            ])
            ->post("{$this->apiUrl}/ewallets/charges", $payload);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        $result = $response->json();

        if (empty($result['id'])) {
            throw new \Exception('E-Wallet failed: ' . json_encode($result));
        }

        // Untuk beberapa e-wallet (GoPay, DANA) ada actions.* checkout URL; OVO biasanya PENDING tanpa URL
        $invoiceUrl = $result['actions']['desktop_web_checkout_url']
            ?? $result['actions']['mobile_web_checkout_url']
            ?? '';

        return $this->storePayment(
            strtolower(str_replace('ID_', '', $channelCode)),
            $customerId,
            $amount,
            $externalId,
            $invoiceUrl,
            $result
        );
    }

    /**
     * Mapping channel_properties untuk E-Wallet
     */
    protected function buildEwalletChannelProperties(string $channelCode, array $metadata): array
    {
        // channelCode sudah bentuk ID_* di titik ini
        return match ($channelCode) {
            'ID_OVO', 'ID_GOPAY' => [
                // OVO wajib mobile_number, format E.164 (+62…)
                'mobile_number' => $metadata['phone_number'] ?? throw new \InvalidArgumentException('OVO requires phone_number (e.g., +62812xxxxxxx)'),
            ],
            'ID_DANA', 'ID_LINKAJA', 'ID_SHOPEEPAY' => [
                // Redirect URL direkomendasikan untuk wallet yang web checkout
                'success_redirect_url' => $metadata['redirect_url'],
            ],
            default => [
                'success_redirect_url' => $metadata['redirect_url'],
            ],
        };
    }

    protected function mapEwalletChannelCode(string $code): string
    {
        $map = [
            'OVO'        => 'ID_OVO',
            'DANA'       => 'ID_DANA',
            'GOPAY'      => 'ID_GOPAY',
            'LINKAJA'    => 'ID_LINKAJA',
            'SHOPEEPAY'  => 'ID_SHOPEEPAY',
            // Bisa tambah lain jika Xendit aktifkan
        ];

        $upper = strtoupper($code);
        if (isset($map[$upper])) {
            return $map[$upper];
        }

        // Sudah dalam format ID_*? biarkan lewat
        if (str_starts_with($upper, 'ID_')) {
            return $upper;
        }

        throw new \InvalidArgumentException("Unsupported e-wallet channel_code: {$code}");
    }

    /**
     * Simpan data payment ke DB
     */
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
