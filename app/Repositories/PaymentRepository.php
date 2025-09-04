<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Enums\StatusPaymentEnum;
use App\Enums\StatusTransactionEnum;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    /**
     * @var Payment
     */
    protected Payment $payment;

    /** @var array<string> */
    protected array $with = ['customer', 'transaction.items'];

    /**
     * Payment constructor.
     *
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Scope query berdasarkan role user:
     * - super_admin/admin : full access
     * - customer          : payment miliknya (customer_id = $user->id)
     * - petugas           : payment yang terkait pickup di transaction dimana pickup.petugas_id = $user->id
     */
    // protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    // {
    //     $query ??= $this->payment->newQuery();

    //     if (!$user) {
    //         return $query; // biarkan bebas; kalau mau, bisa diubah ke abort(403).
    //     }

    //     $role = $user->role->value ?? $user->role->name ?? (string) $user->role ?? null;
    //     $role = strtolower((string) $role);

    //     return match ($role) {
    //         'customer' => $query->where('customer_id', $user->id),

    //         'petugas'  => $query->whereHas('transaction.pickup', function (Builder $q) use ($user) {
    //             $q->where('petugas_id', $user->id);
    //         }),

    //         // admin/super_admin (atau role lain) tanpa restriksi
    //         default    => $query,
    //     };
    // }

    /**
     * Get all payment.
     *
     * @return Payment $payment
     */
    public function all(?UserData $user = null)
    {
        return $this->payment->newQuery()->with($this->with)->get();
    }


    /**
     * Get payment by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->payment->newQuery()
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Save Payment
     *
     * @param $data
     * @return Payment
     */
    public function save(array $data)
    {
        $payment = $this->payment->newQuery()->create($data);
        return $payment->load($this->with);
    }

    /**
     * Update Payment
     *
     * @param $data
     * @return Payment
     */
    public function update(array $data, int $id, ?UserData $user = null)
    {
        // $payment = $this->scopeForUser($user)->findOrFail($id);
        $payment = $this->payment->newQuery()->findOrFail($id);
        $payment->update($data);
        return $payment->load($this->with);
    }

    /**
     * Delete Payment
     *
     * @param $data
     * @return Payment
     */
    public function delete(int $id, ?UserData $user = null)
    {
        // $payment = $this->scopeForUser($user)->findOrFail($id);
        $payment = $this->payment->newQuery()->findOrFail($id);
        $payment->delete();
        return $payment;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10, ?UserData $user = null)
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

    /**
     * Buat record Payment dari respons Xendit & ubah transaksi -> pending + due/expires.
     *
     * @return array{0:\App\Models\Payment,1:\App\Models\Transaction}
     */
    public function createFromXenditStrict(
        Transaction $trx,
        array $resp,
        CarbonInterface $expiresAt
    ): array {
        // --- Ambil field dari response (top-level) ---
        $prId        = $resp['payment_request_id'] ?? $resp['id'] ?? null;
        $businessId  = $resp['business_id'] ?? $resp['xendit_account_id'] ?? null;
        $referenceId = $resp['reference_id'] ?? $trx->number ?? null;
        $channelCode = $resp['channel_code'] ?? null;
        $requestAmt  = $resp['request_amount'] ?? (float) $trx->total;
        $desc        = $resp['description'] ?? null;

        $channelProps = is_array($resp['channel_properties'] ?? null)
            ? $resp['channel_properties']
            : [];

        // Prefer expires_at dari response → fallback ke parameter/now+15m
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

        // --- Create Payment ---
        /** @var \App\Models\Payment $payment */
        $payment = $this->payment->newQuery()->create([
            'transaction_id'            => $trx->id,
            'customer_id'               => $trx->customer_id,

            'amount'                    => (float) $requestAmt,
            'currency'                  => $trx->currency ?? 'IDR',

            'status'                    => $paymentStatus,                // mapped enum
            'channel'                   => $channelCode,                  // simpan apa adanya
            'method_code'               => null,                          // optional
            'reference_id'              => $referenceId,
            'idempotency_key'           => null,                          // set di Http layer jika perlu
            'xendit_account_id'         => $businessId,

            'xendit_payment_request_id' => $prId,
            'xendit_charge_id'          => null,
            'xendit_invoice_id'         => null,

            'va_numbers'                => $vaNumbersData ?: null,        // array of {number, bank?}
            'qris_qr_string'            => $qrisQrString,
            'checkout_url'              => $checkoutUrl,
            'ewallet_info'              => null,

            'expires_at'                => $expiresAt,
            'paid_at'                   => null,
            'failure_code'              => null,
            'failure_message'           => null,
            'xendit_data'               => $resp,
        ]);

        // --- Update Transaction: pending + sinkron due/expires ---
        $trx->update([
            'status'     => StatusTransactionEnum::PENDING->value,
            'due_at'     => $trx->due_at ?: $expiresAt,
            'expires_at' => $expiresAt,
            // optional, simpan description dari resp bila belum ada
            'description' => $trx->description ?: $desc,
        ]);

        return [
            'payment'     => $payment->fresh(),
            'transaction' => $trx->fresh(['transaction_items', 'payments']),
        ];
    }

    /**
     * Map status Payment Request Xendit → StatusPaymentEnum (kamu).
     */
    protected function mapXenditStatusToPaymentStatus(?string $xenditStatus): string
    {
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

    /**
     * Ambil checkout URL dari actions (jika ada).
     * Payment Request sering mengembalikan link web/deeplink untuk ewallet/cards.
     */
    protected function extractCheckoutUrlFromActions(array $actions): ?string
    {
        foreach ($actions as $a) {
            // beberapa channel pakai descriptor 'WEB_CHECKOUT_URL' atau sejenis
            $type       = strtoupper((string) ($a['type'] ?? ''));
            $descriptor = strtoupper((string) ($a['descriptor'] ?? ''));
            $val        = $a['value'] ?? null;

            if (!$val) continue;

            if (
                str_contains($descriptor, 'CHECKOUT_URL')
                || str_contains($type, 'CHECKOUT')
                || filter_var($val, FILTER_VALIDATE_URL)
            ) {
                return $val;
            }
        }
        return null;
    }

    /**
     * Ambil VA number dari actions.
     * Contoh input: [{ type: 'PRESENT_TO_CUSTOMER', descriptor: 'VIRTUAL_ACCOUNT_NUMBER', value: '8808...' }]
     */
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

    /**
     * Ambil QR string dari actions (untuk QRIS dynamic).
     * Beberapa implementasi mengirim descriptor 'QR_STRING' atau 'QR_CODE'.
     */
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

    /**
     * Kira-kira bank dari channel_code (BNI_VIRTUAL_ACCOUNT → BNI).
     */
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
