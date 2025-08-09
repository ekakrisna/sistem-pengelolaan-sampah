<?php

namespace App\Services;

use App\Data\UserData;
use App\Enums\UserEnum;
use App\Models\PickupFee;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Xendit\Configuration;
use Xendit\PaymentRequest\PaymentRequestApi;
use Xendit\PaymentRequest\PaymentRequestParameters;

class PaymentRequestService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected PaymentService $paymentService
    ) {
        if (!config('services.xendit.api_key')) {
            throw new InvalidArgumentException('Xendit API key not configured.');
        }
        Configuration::setXenditKey(config('services.xendit.api_key'));
    }

    /**
     * Create Payment via Payment Request API, store to DB (payments).
     *
     * $data:
     * - customer_id: int (required)
     * - amount: int (required)
     * - method: 'EWALLET'|'QR_CODE'|'VIRTUAL_ACCOUNT'|'TOKENIZED' (required)
     * - channel_code: e.g. 'SHOPEEPAY','OVO','DANA','GOPAY' (EWALLET), 'QRIS' (QR_CODE), 'BCA' (VA)
     * - ewallet_phone: +62... (for OVO/GOPAY)
     * - success_return_url: url (for ewallet redirect)
     * - va_customer_name: string (for VA)
     * - va_expires_at: ISO8601 string (optional)
     * - metadata: array (pickup_id, draft_items, etc)
     * - for_user_id: string|null (xenPlatform subaccount)
     * - idempotency_key: string|null
     * - with_split_rule: string|null
     * - payment_method_id: string (for tokenized payments)
     */
    public function create(array $data, ?UserData $actor = null)
    {
        // ===== Role-aware customer_id =====
        if ($actor && strtolower((string)($actor->role->value ?? $actor->role ?? '')) === UserEnum::Customer->value) {
            $data['customer_id'] = (int) $actor->id;
        }

        // ===== Validasi wajib =====
        if (empty($data['customer_id']) || !is_numeric($data['customer_id'])) {
            throw new InvalidArgumentException('customer_id is required');
        }
        if (empty($data['pickup_id']) || !is_numeric($data['pickup_id']) || (int)$data['pickup_id'] <= 0) {
            throw new InvalidArgumentException('pickup_id is required and must be positive integer.');
        }
        if (empty($data['method'])) {
            throw new InvalidArgumentException('method is required (EWALLET | QR_CODE | VIRTUAL_ACCOUNT | TOKENIZED)');
        }
        if (empty($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
            throw new InvalidArgumentException('items is required and must be a non-empty array.');
        }
        foreach ($data['items'] as $i => $item) {
            if (empty($item['pickup_fee_id']) || !is_numeric($item['pickup_fee_id']) || (int)$item['pickup_fee_id'] <= 0) {
                throw new InvalidArgumentException("items[$i].pickup_fee_id is required and must be positive integer.");
            }
            if (empty($item['qty']) || !is_numeric($item['qty']) || (int)$item['qty'] <= 0) {
                throw new InvalidArgumentException("items[$i].qty is required and must be positive integer.");
            }
        }

        // ===== Hitung total dari pickup_fees (override amount agar akurat) =====
        $feeIds = array_unique(array_map(fn($i) => (int)$i['pickup_fee_id'], $data['items']));
        $fees   = PickupFee::whereIn('id', $feeIds)->get()->keyBy('id');

        $grand   = '0.00';
        $draftItems = [];
        foreach ($data['items'] as $row) {
            $fee   = $fees[(int)$row['pickup_fee_id']] ?? null;
            if (!$fee) {
                throw new InvalidArgumentException("pickup_fee_id {$row['pickup_fee_id']} not found.");
            }
            $qty   = (int)$row['qty'];
            $unit  = number_format((float)$fee->amount, 2, '.', '');
            $line  = bcmul($unit, (string)$qty, 2);
            $grand = bcadd($grand, $line, 2);

            $draftItems[] = [
                'pickup_fee_id' => $fee->id,
                'description'   => $fee->description,
                'unit_amount'   => $unit,
                'qty'           => $qty,
                'line_total'    => $line,
                'meta'          => [
                    'waste_type_id' => $fee->waste_type_id,
                    'village_code'  => $fee->village_code,
                    'admin_id'      => $fee->admin_id,
                ],
            ];
        }

        $amountCalc = (int) round((float)$grand); // PR butuh integer
        $method     = strtoupper((string)$data['method']);
        $meta       = $data['metadata'] ?? [];
        $refId      = $data['reference_id'] ?? ('pr-ref-' . now()->format('YmdHis') . '-' . Str::random(6));
        $idemKey    = $data['idempotency_key'] ?? ('pr-' . Str::uuid());
        $forUser    = $data['for_user_id'] ?? null;
        $split      = $data['with_split_rule'] ?? null;

        // Siapkan payload PR
        $payload = [
            'reference_id' => $refId,
            'amount'       => $amountCalc,
            'currency'     => 'IDR',
            'country'      => 'ID',
            'metadata'     => array_merge($meta, [
                'pickup_id'   => (int)$data['pickup_id'],
                'draft_items' => array_map(fn($x) => [
                    'pickup_fee_id' => $x['pickup_fee_id'],
                    'description'   => $x['description'],
                    'unit_amount'   => $x['unit_amount'],
                    'qty'           => $x['qty'],
                    'line_total'    => $x['line_total'],
                ], $draftItems),
            ]),
        ];

        // Build block sesuai method
        $payload['payment_method'] = match ($method) {
            'EWALLET'         => $this->buildEwalletBlock($data),
            'QR_CODE'         => $this->buildQrBlock($data),
            'VIRTUAL_ACCOUNT' => $this->buildVaBlock($data),
            'TOKENIZED'       => $this->buildTokenizedBlock($data),
            default           => throw new InvalidArgumentException("Unsupported method: {$method}"),
        };

        // ===== Call Xendit PR =====
        $api  = new PaymentRequestApi();
        $resp = $api->createPaymentRequest(
            $idemKey,
            $forUser,
            $split,
            new PaymentRequestParameters($payload)
        );

        $arr = json_decode(json_encode($resp), true);

        // Derive method label for DB
        $dbMethod = match ($method) {
            'EWALLET'          => strtolower($arr['payment_method']['ewallet']['channel_code'] ?? $data['channel_code'] ?? 'ewallet'),
            'QR_CODE'          => 'qris',
            'VIRTUAL_ACCOUNT'  => strtolower($arr['payment_method']['virtual_account']['channel_code'] ?? $data['channel_code'] ?? 'va') . '_va',
            'TOKENIZED'        => 'ewallet_tokenized',
            default            => 'payment_request',
        };

        // Checkout URL / VA number (best effort)
        $invoiceUrl = $arr['actions']['desktop_web_checkout_url']
            ?? $arr['actions']['mobile_web_checkout_url']
            ?? ($arr['payment_method']['virtual_account']['channel_properties']['account_number'] ?? '')
            ?? '';

        // Map status PR -> our status
        $status = $this->mapPrStatus($arr['status'] ?? 'PENDING');

        // ===== Simpan Payment + Draft Transaction & Items atomically =====
        return DB::transaction(function () use ($data, $actor, $amountCalc, $dbMethod, $refId, $invoiceUrl, $arr, $draftItems) {
            // Simpan Payment (pending)
            /** @var Payment $payment */
            $payment = $this->paymentService->save([
                'customer_id'    => (int)$data['customer_id'],
                'amount'         => $amountCalc,
                'status'         => 'pending',
                'payment_method' => $dbMethod,
                'external_id'    => $arr['id'] ?? $refId,
                'invoice_url'    => $invoiceUrl,
                'xendit_data'    => $arr,
            ], $actor);

            // Buat DRAFT transaction + items (wajib)
            $trx = Transaction::create([
                'payment_id'  => $payment->id,
                'pickup_id'   => (int)$data['pickup_id'],
                'total'       => $amountCalc, // sama dengan grand total
                'description' => 'Draft created at payment request',
            ]);

            foreach ($draftItems as $row) {
                $trx->items()->create([
                    'pickup_fee_id' => $row['pickup_fee_id'],
                    'description'   => $row['description'],
                    'unit_amount'   => $row['unit_amount'],
                    'qty'           => $row['qty'],
                    'line_total'    => $row['line_total'],
                    'meta'          => $row['meta'],
                ]);
            }

            // Tambahkan penanda ke payment.xendit_data -> metadata
            $xd = $payment->xendit_data ?? [];
            $xd['metadata']                = $xd['metadata'] ?? [];
            $xd['metadata']['pickup_id']   = (int)$data['pickup_id'];
            $xd['metadata']['transaction_id'] = $trx->id;
            $xd['metadata']['draft_items'] = array_map(fn($x) => [
                'pickup_fee_id' => $x['pickup_fee_id'],
                'description'   => $x['description'],
                'unit_amount'   => $x['unit_amount'],
                'qty'           => $x['qty'],
                'line_total'    => $x['line_total'],
            ], $draftItems);

            $payment->update(['xendit_data' => $xd]);

            return $payment->fresh('transactions');
        });
    }

    public function getByPaymentRequestId(string $prId, ?string $forUserId = null): array
    {
        $api  = new PaymentRequestApi();
        $resp = $api->getPaymentRequestByID($prId, $forUserId);
        return json_decode(json_encode($resp), true);
    }

    public function getCaptures(string $prId, ?string $forUserId = null, int $limit = 50): array
    {
        $api  = new PaymentRequestApi();
        $resp = $api->getPaymentRequestCaptures($prId, $forUserId, $limit);
        return json_decode(json_encode($resp), true);
    }

    protected function buildEwalletBlock(array $d): array
    {
        $this->assertHas($d, ['channel_code']);
        $code = strtoupper($d['channel_code']);

        $props = match ($code) {
            'OVO', 'GOPAY' => [
                'mobile_number'     => $d['ewallet_phone'] ?? throw new InvalidArgumentException('ewallet_phone (+62...) required for OVO/GOPAY'),
                'success_return_url' => $d['success_return_url'] ?? url('/payment/success'),
            ],
            default => [
                'success_return_url' => $d['success_return_url'] ?? url('/payment/success'),
            ],
        };

        return [
            'type'        => 'EWALLET',
            'reusability' => 'ONE_TIME_USE',
            'ewallet'     => [
                'channel_code'       => $code,
                'channel_properties' => $props,
            ],
        ];
    }

    protected function buildQrBlock(array $d): array
    {
        return [
            'type'        => 'QR_CODE',
            'reusability' => 'ONE_TIME_USE',
            'qr_code'     => [
                'channel_code' => 'QRIS',
            ],
        ];
    }

    protected function buildVaBlock(array $d): array
    {
        $this->assertHas($d, ['channel_code', 'va_customer_name']);
        $props = [
            'customer_name' => $d['va_customer_name'],
            'expires_at'    => $d['va_expires_at'] ?? now()->addDay()->toIso8601String(),
        ];

        return [
            'type'          => 'VIRTUAL_ACCOUNT',
            'reusability'   => 'ONE_TIME_USE',
            'reference_id'  => 'va-ref-' . Str::random(8),
            'virtual_account' => [
                'channel_code'       => strtoupper($d['channel_code']),
                'channel_properties' => $props,
            ],
        ];
    }

    protected function buildTokenizedBlock(array $d): array
    {
        $this->assertHas($d, ['payment_method_id']); // id dari account linking
        return [
            'payment_method_id' => $d['payment_method_id'],
        ];
    }

    // ===================== helpers =====================

    protected function mapPrStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED', 'SUCCEEDED', 'PAID', 'SETTLED' => 'paid',
            'REQUIRES_ACTION', 'PENDING', 'AWAITING_PAYMENT', 'REQUIRES_AUTHORIZATION' => 'pending',
            'FAILED', 'CANCELLED', 'VOIDED', 'EXPIRED' => 'failed',
            default => 'pending',
        };
    }

    protected function assertHas(array $arr, array $keys): void
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $arr) || $arr[$k] === null || $arr[$k] === '') {
                throw new InvalidArgumentException("{$k} is required");
            }
        }
    }
}
