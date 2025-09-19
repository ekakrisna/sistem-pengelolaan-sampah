<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Enums\StatusPaymentEnum;
use App\Enums\StatusPaymentSplitRouteEnum;
use App\Enums\StatusTransactionEnum;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\PaymentSplitRoute;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PaymentRepository
{
    protected array $with = ['customer', 'transaction.transaction_items'];

    public function __construct(protected Payment $payment) {}

    public function all()
    {
        return $this->payment->newQuery()->with($this->with)->get();
    }

    public function getById(int $id): Payment
    {
        return $this->payment->newQuery()
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    public function save(array $data)
    {
        $payment = $this->payment->newQuery()->create($data);
        return $payment->load($this->with);
    }

    public function update(array $data, int $id)
    {
        $payment = $this->payment->newQuery()->findOrFail($id);
        $payment->update($data);
        return $payment->load($this->with);
    }

    public function delete(int $id)
    {
        $payment = $this->payment->newQuery()->findOrFail($id);
        $payment->delete();
        return $payment;
    }

    public function findByIdempotencyKey(string $key): ?Payment
    {
        return $this->payment->newQuery()
            ->where('idempotency_key', $key)
            ->first();
    }

    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->payment->newQuery()->with($this->with);

        // Filter by search 
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('external_id', 'like', "%$search%")
                    ->orWhere('invoice_url', 'like', "%$search%")
                    ->orWhere('xendit_data', 'like', "%$search%");
            });
        }

        // Filter by customer (name, email, phone)
        if (!empty($filters['customer'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['customer'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['customer'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['customer'] . '%');
            });
        }

        // Filter by payment status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filter by paid_at range
        if (!empty($filters['start_date'])) {
            $query->whereDate('paid_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('paid_at', '<=', $filters['end_date']);
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }

    public function createFromXenditStrict(
        Transaction $trx,
        array $resp,
        CarbonInterface $expiresAt,
        ?string $idempotencyKey = null,
        ?string $forcedForUserId = null,
        ?string $splitRuleId = null,
        array $routesPlan = []

    ): array {
        $prId        = $resp['payment_request_id'] ?? $resp['id'] ?? null;
        $businessId  = $resp['business_id'] ?? $resp['xendit_account_id'] ?? null;
        $accountId   = $forcedForUserId ?: $businessId;
        $referenceId = $resp['reference_id'] ?? $trx->number ?? null;
        $channelCode = $resp['channel_code'] ?? null;
        $requestAmt  = $resp['request_amount'] ?? (float) $trx->total;
        $desc        = $resp['description'] ?? null;

        $channelProps = is_array($resp['channel_properties'] ?? null)
            ? $resp['channel_properties']
            : [];

        $expiresAtIso = $channelProps['expires_at'] ?? null;
        $expiresAt    = $expiresAtIso
            ? CarbonImmutable::parse($expiresAtIso)
            : ($expiresAt ?: CarbonImmutable::now()->addMinutes(15));

        // Aksi bisa variatif per channel
        $actions = $resp['actions'] ?? [];
        $checkoutUrl   = $this->extractCheckoutUrlFromActions($actions);
        $vaNumbersData = $this->extractVaNumbersFromActions($actions, $channelCode);
        $qrisQrString  = $this->extractQrisFromActions($actions);

        // Map status Xendit → enum payment kamu
        $paymentStatus = $this->mapXenditStatusToPaymentStatus($resp['status'] ?? null);

        $payment = $this->payment->newQuery()->create([
            'transaction_id'            => $trx->id,
            'customer_id'               => $trx->customer_id,

            'amount'                    => (float) $requestAmt,
            'currency'                  => $trx->currency ?? 'IDR',

            'status'                    => $paymentStatus,
            'channel'                   => $channelCode,
            'method_code'               => null,
            'reference_id'              => $referenceId,
            'idempotency_key'           => $idempotencyKey,
            'xendit_account_id'         => $accountId,
            'split_rule_id'             => $splitRuleId,

            'xendit_payment_request_id' => $prId,
            'xendit_charge_id'          => null,
            'xendit_invoice_id'         => null,

            'va_numbers'                => $vaNumbersData ?: null,
            'qris_qr_string'            => $qrisQrString,
            'checkout_url'              => $checkoutUrl,
            'ewallet_info'              => null,

            'expires_at'                => $expiresAt,
            'paid_at'                   => null,
            'failure_code'              => null,
            'failure_message'           => null,
            'xendit_data'               => $resp,
        ]);

        $trx->update([
            'status'     => StatusTransactionEnum::PENDING->value,
            'due_at'     => $trx->due_at ?: $expiresAt,
            'expires_at' => $expiresAt,
            'description' => $trx->description ?: $desc,
        ]);

        foreach ($routesPlan as $r) {
            PaymentSplitRoute::create([
                'payment_id'            => $payment->id,
                'transaction_id'        => $trx->id,
                'admin_id'              => $r['admin_id'] ?? null,
                'currency'              => $r['currency'] ?? 'IDR',
                'flat_amount'           => $r['flat_amount'] ?? null,
                'percent_amount'        => $r['percent_amount'] ?? null,
                'destination_account_id' => $r['destination_account_id'],
                'reference_id'          => $r['reference_id'],
                'split_rule_id'         => $splitRuleId,
                'status'                => StatusPaymentSplitRouteEnum::APPLIED->value,
                'applied_at'            => CarbonImmutable::now(),
                'meta'                  => $r['meta'] ?? null,
            ]);
        }

        return [
            'payment'     => $payment->fresh(),
            'transaction' => $trx->fresh(['transaction_items', 'payments']),
        ];
    }

    public function applyCancellationFromGateway(Payment $payment, array $xResp, string $reason = 'cancelled_by_user'): Payment
    {
        $statusFromGateway = strtoupper((string)($xResp['status'] ?? 'CANCELLED'));

        // Map status Xendit menjadi status lokal yang tepat
        $mapped = match ($statusFromGateway) {
            'CANCELLED', 'CANCELED' => StatusPaymentEnum::CANCELED->value,
            'EXPIRED'               => StatusPaymentEnum::EXPIRED->value,
            'FAILED'                => StatusPaymentEnum::FAILED->value,
            default                 => StatusPaymentEnum::CANCELED->value,
        };

        $failureCode    = $xResp['failure_code']    ?? $reason;
        $failureMessage = $xResp['failure_message'] ?? 'Payment was cancelled.';

        $payment->update([
            'status'          => $mapped,
            'failure_code'    => $failureCode,
            'failure_message' => $failureMessage,
            'paid_at'         => null,
            'xendit_data'     => $this->mergeXenditData($payment->xendit_data ?? [], $xResp),
        ]);

        // Jika semua payment pada transaksi bukan 'SUCCEEDED', kembalikan trx ke DRAFT
        $trx = $payment->transaction()->with('payments')->first();
        if ($trx && !$trx->payments()->where('status', StatusPaymentEnum::SUCCEEDED->value)->exists()) {
            $trx->update([
                'status'     => StatusTransactionEnum::DRAFT->value,
                'due_at'     => null,
                'expires_at' => null,
            ]);
        }

        return $payment->fresh(['transaction.payments']);
    }

    protected function mergeXenditData(array $current, array $incoming): array
    {
        // Simpel: timpa kunci yang sama, simpan payload terakhir
        return array_replace_recursive($current, [
            '_last_cancel_response' => $incoming,
        ]);
    }

    protected function mapXenditStatusToPaymentStatus(
        ?string $xenditStatus
    ): string {
        $x = strtoupper((string) $xenditStatus);

        return match ($x) {
            // PR butuh aksi user (scan/masukkan VA) → waiting payment
            'REQUIRES_ACTION', 'PENDING', 'REQUIRES_PAYMENT_METHOD'
            => StatusPaymentEnum::AWAITING_PAYMENT->value,

            // Berhasil dibayar
            'SUCCEEDED', 'COMPLETED', 'CAPTURED'
            => StatusPaymentEnum::SUCCEEDED->value,

            // Gagal
            'FAILED', 'CANCELLED', 'CANCELED'
            => StatusPaymentEnum::FAILED->value,

            // Kadaluwarsa
            'EXPIRED'
            => StatusPaymentEnum::EXPIRED->value,

            default
            => StatusPaymentEnum::INITIATED->value,
        };
    }

    protected function extractCheckoutUrlFromActions(array $actions): ?string
    {
        foreach ($actions as $a) {
            // beberapa channel pakai descriptor 'WEB_CHECKOUT_URL' atau sejenis
            $type       = strtoupper((string) ($a['type'] ?? ''));
            $descriptor = strtoupper((string) ($a['descriptor'] ?? ''));
            $val        = $a['value'] ?? null;

            if (!$val) continue;

            if (
                str_contains($descriptor, 'WEB_URL')
                || str_contains($type, 'CHECKOUT')
                || filter_var($val, FILTER_VALIDATE_URL)
            ) {
                return $val;
            }
        }
        return null;
    }

    protected function extractVaNumbersFromActions(array $actions, ?string $channelCode): ?array
    {
        foreach ($actions as $a) {
            $descriptor = strtoupper((string) ($a['descriptor'] ?? ''));
            $val        = $a['value'] ?? null;

            if ($descriptor === 'VIRTUAL_ACCOUNT_NUMBER' && $val) {
                return [[
                    'number' => (string) $val,
                    'bank'   => $this->inferVaBankFromChannel($channelCode),
                ]];
            }
        }
        return null;
    }

    protected function extractQrisFromActions(array $actions): ?string
    {
        foreach ($actions as $a) {
            $descriptor = strtoupper((string) ($a['descriptor'] ?? ''));
            $val        = $a['value'] ?? null;

            if ($val && (str_contains($descriptor, 'QR_STRING') || str_contains($descriptor, 'QR_CODE'))) {
                return (string) $val;
            }
        }
        return null;
    }

    protected function inferVaBankFromChannel(?string $channelCode): ?string
    {
        if (!$channelCode) return null;
        $up = strtoupper($channelCode);

        return match (true) {
            str_contains($up, 'BCA')     => 'BCA',
            str_contains($up, 'BNI')     => 'BNI',
            str_contains($up, 'BRI')     => 'BRI',
            str_contains($up, 'MANDIRI') => 'MANDIRI',
            str_contains($up, 'PERMATA') => 'PERMATA',
            str_contains($up, 'CIMB')    => 'CIMB',
            default => null,
        };
    }
}
