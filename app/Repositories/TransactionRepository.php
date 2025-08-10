<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Builder;

class TransactionRepository
{
    /**
     * @var Transaction
     */
    protected Transaction $transaction;
    protected array $with = [
        'pickup.schedule.village',
        'pickup.schedule.wasteType',
        'pickup.schedule.admin',
        'pickup.customer',
        'pickup.petugas',
        'payment.customer',
        'items',
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
    protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    {
        $query ??= $this->transaction->newQuery();

        if (!$user) return $query;

        $role = $user->role->value ?? $user->role->name ?? (string) $user->role ?? null;
        $role = strtolower((string) $role);

        return match ($role) {
            'customer' => $query->whereHas('payment', fn(Builder $q) => $q->where('customer_id', $user->id)),
            'petugas'  => $query->whereHas('pickup',  fn(Builder $q) => $q->where('petugas_id',  $user->id)),
            default    => $query,
        };
    }

    /**
     * Get all transaction.
     * @param ?UserData $user
     * @return Transaction $transaction
     */
    public function all(?UserData $user = null)
    {
        return $this->scopeForUser($user)
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
        return $this->scopeForUser($user)
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
        $trx = $this->scopeForUser($user)->findOrFail($id);
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
        $trx = $this->scopeForUser($user)->findOrFail($id);
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
        $query = $this->scopeForUser($user)->with($this->with);

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

    public function insertItems(int $transactionId, array $rows): void
    {
        foreach ($rows as $r) {
            $r['transaction_id'] = $transactionId;
            TransactionItem::create($r);
        }
    }
}
