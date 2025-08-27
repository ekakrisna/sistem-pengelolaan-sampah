<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\PickupFee;
use App\Models\PickupSchedule;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionRepository
{
    /**
     * @var Transaction
     */
    protected Transaction $transaction;
    protected array $with = [
        'payments',
        'customer',
        'transaction_items',
    ];

    /**
     * Transaction constructor.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Scope by user role:
     * - customer : via payment.customer_id = user->id
     * - petugas  : via pickup.petugas_id   = user->id
     * - admin/super_admin/others : no restriction
     */
    // protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    // {
    //     $query ??= $this->transaction->newQuery();

    //     if (!$user) return $query;

    //     $role = $user->role->value ?? $user->role->name ?? (string) $user->role ?? null;
    //     $role = strtolower((string) $role);

    //     return match ($role) {
    //         'customer' => $query->whereHas('payment', fn(Builder $q) => $q->where('customer_id', $user->id)),
    //         'petugas'  => $query->whereHas('pickup',  fn(Builder $q) => $q->where('petugas_id',  $user->id)),
    //         default    => $query,
    //     };
    // }

    /**
     * Get all transaction.
     * @param ?UserData $user
     * @return Transaction $transaction
     */
    public function all(?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->get();
    }

    /**
     * Get transaction by id
     *
     * @param $id
     * @param ?UserData $user
     * @return mixed
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Save Transaction
     *
     * @param $data
     * @return Transaction
     */
    public function save(array $data)
    {
        $transaction = $this->transaction->newQuery()->create($data);
        return $transaction->load($this->with);
    }

    /**
     * Update Transaction
     *
     * @param $data
     * @param $id
     * @param ?UserData $user
     * @return Transaction
     */
    public function update(array $data, int $id, ?UserData $user = null)
    {
        $trx = $$this->transaction->newQuery()->findOrFail($id);
        $trx->update($data);
        return $trx->load($this->with);
    }

    /**
     * Delete Transaction
     *
     * @param $data
     * @param ?UserData $user
     * @return Transaction
     */
    public function delete(int $id, ?UserData $user = null)
    {
        $trx = $$this->transaction->newQuery()->findOrFail($id);
        $trx->delete();
        return $trx;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @param ?UserData $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * Pagination + Filters (role-aware)
     *
     * Supported filters:
     * - search           : cari di payment.external_id / invoice_url
     * - status           : payment.status
     * - payment_method   : payment.payment_method
     * - customer         : nama/email/phone customer
     * - petugas          : nama/email/phone petugas
     * - village          : pickup.schedule.village.name
     * - waste_type       : pickup.schedule.wasteType (name/description)
     * - admin            : pickup.schedule.admin (name/email)
     * - start_date/end_date : by transaction.created_at
     * - order_by         : oldest|desc (default desc)
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10, ?UserData $user = null)
    {
        $query = $$this->transaction->newQuery()->with($this->with);

        // Filter by village
        if (!empty($filters['village'])) {
            $query->whereHas('pickup.schedule.village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Filter by waste type
        if (!empty($filters['waste_type'])) {
            $query->whereHas('pickup.schedule.wasteType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['waste_type'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['waste_type'] . '%');
            });
        }

        // Filter by admin
        if (!empty($filters['admin'])) {
            $query->whereHas('pickup.schedule.admin', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['admin'] . '%');
            });
        }

        // Filter by payment status
        if (!empty($filters['status'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('payment_method', $filters['payment_method']);
            });
        }

        // Filter by transaction date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }

    /** ---------------- NEW: Draft Cart Helpers ---------------- */

    public function getDraftCart(int $customerId): ?Transaction
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->where('customer_id', $customerId)
            ->where('status', 'draft')
            ->latest('id')
            ->first();
    }

    public function createDraftCart(int $customerId, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($customerId, $meta) {
            // Lock agar tidak balapan bikin 2 cart
            $existing = $this->getDraftCart($customerId);
            if ($existing) {
                return $existing;
            }

            $trx = $this->transaction->newQuery()->create([
                'customer_id' => $customerId,
                'number'      => $this->generateNumber(),
                'status'      => 'draft',
                'subtotal'    => 0,
                'discount_amount' => 0,
                'tax_amount'  => 0,
                'total'       => 0,
                'currency'    => 'IDR',
                'meta'        => $meta ?: null,
            ]);

            return $trx->load($this->with);
        });
    }

    public function getOrCreateDraftCart(int $customerId, array $meta = []): Transaction
    {
        $cart = $this->getDraftCart($customerId);
        return $cart ?: $this->createDraftCart($customerId, $meta);
    }

    /** ---------------- NEW: Cart Items Helpers ---------------- */
    public function addItemToCart(int $transactionId, array $itemData, int $currentUserId): TransactionItem
    {
        return DB::transaction(function () use ($transactionId, $itemData, $currentUserId) {
            /** @var Transaction $trx */
            $trx = $this->transaction->newQuery()
                ->where('id', $transactionId)
                ->where('customer_id', $currentUserId)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            // Validasi kepemilikan user_address (jika diisi)
            if (!empty($itemData['user_address_id'])) {
                $owned = UserAddress::query()
                    ->where('id', $itemData['user_address_id'])
                    ->where('user_id', $currentUserId)
                    ->exists();

                if (!$owned) {
                    throw new \InvalidArgumentException('Address not found or not owned by the current user.');
                }
            }


            $itemType = $itemData['item_type'] ?? 'pickup';

            // Jika item pickup, pickup_fee_id wajib & unit_amount HARUS dari tabel pickup_fees
            if ($itemType === 'pickup') {
                if (empty($itemData['pickup_fee_id'])) {
                    throw new \InvalidArgumentException('pickup_fee_id is required for pickup item.');
                }

                /** @var PickupFee $fee */
                $fee = PickupFee::query()->findOrFail($itemData['pickup_fee_id']);

                // misal pastikan village & waste_type cocok
                if ($itemData['pickup_schedule_id'] ?? null) {
                    $schedule = PickupSchedule::findOrFail($itemData['pickup_schedule_id']);
                    if (
                        $schedule->village_code !== $fee->village_code ||
                        (int)$schedule->waste_type_id !== (int)$fee->waste_type_id
                    ) {
                        throw new \InvalidArgumentException('Pickup fee and schedule are not compatible.');
                    }
                }

                // (Opsional tapi disarankan) Pastikan schedule ada, jika dikirim
                if (!empty($itemData['pickup_schedule_id'])) {
                    PickupSchedule::query()->findOrFail($itemData['pickup_schedule_id']);
                }

                $qty        = max(1, (int)($itemData['qty'] ?? 1));
                $unitAmount = (float) $fee->amount;
                $lineTotal  = $unitAmount * $qty;

                $payload = [
                    'item_type'           => 'pickup',
                    'user_address_id'     => $itemData['user_address_id'] ?? null, // kolom di DB: customer_address_id (atau sesuaikan rename)
                    'pickup_schedule_id'  => $itemData['pickup_schedule_id'] ?? null,
                    'pickup_fee_id'       => $fee->id,
                    'description'         => $itemData['description'] ?? null,
                    'unit_amount'         => $unitAmount,
                    'qty'                 => $qty,
                    'line_total'          => $lineTotal,
                    'meta'                => $itemData['meta'] ?? null,
                ];
            } else {
                // Non-pickup (surcharge/discount/tax/other): izinkan nilai custom
                if (!isset($itemData['unit_amount'])) {
                    throw new \InvalidArgumentException('unit_amount is required for non-pickup item.');
                }
                $qty        = max(1, (int)($itemData['qty'] ?? 1));
                $unitAmount = (float) $itemData['unit_amount'];
                $lineTotal  = $itemData['line_total'] ?? ($unitAmount * $qty);

                $payload = [
                    'item_type'           => $itemType,
                    'user_address_id'     => $itemData['user_address_id'] ?? null,
                    'pickup_schedule_id'  => $itemData['pickup_schedule_id'] ?? null,
                    'pickup_fee_id'       => $itemData['pickup_fee_id'] ?? null,
                    'description'         => $itemData['description'] ?? null,
                    'unit_amount'         => $unitAmount,
                    'qty'                 => $qty,
                    'line_total'          => $lineTotal,
                    'meta'                => $itemData['meta'] ?? null,
                ];
            }

            $item = $trx->transaction_items()->create($payload);

            $this->recalculateTotals($trx);

            return $item->fresh();
        });
    }

    public function removeItemFromCart(int $transactionId, int $itemId, int $currentUserId): bool
    {
        return DB::transaction(function () use ($transactionId, $itemId, $currentUserId) {
            /** @var Transaction $trx */
            $trx = $this->transaction->newQuery()
                ->where('id', $transactionId)
                ->where('customer_id', $currentUserId)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            $deleted = $trx->transaction_items()
                ->where('id', $itemId)
                ->delete();

            $this->recalculateTotals($trx);

            return (bool) $deleted;
        });
    }

    /** ---------------- Helpers ---------------- */

    protected function recalculateTotals(Transaction $trx): void
    {
        $subtotal = $trx->transaction_items()->sum('line_total');
        $trx->update([
            'subtotal' => $subtotal,
            'total'    => $subtotal - $trx->discount_amount + $trx->tax_amount,
        ]);
    }

    protected function generateNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . Str::upper(Str::random(5));
    }
}
