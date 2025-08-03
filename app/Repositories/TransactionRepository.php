<?php

namespace App\Repositories;

use App\Models\Transaction;

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
        'payment.customer'
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
     * Get all transaction.
     *
     * @return Transaction $transaction
     */
    public function all()
    {
        return $this->transaction->with($this->with)->get();
    }

    /**
     * Get transaction by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->transaction->with($this->with)->findOrFail($id);
    }

    /**
     * Save Transaction
     *
     * @param $data
     * @return Transaction
     */
    public function save(array $data)
    {
        return Transaction::create($data)->load($this->with);
    }

    /**
     * Update Transaction
     *
     * @param $data
     * @return Transaction
     */
    public function update(array $data, int $id)
    {
        $transaction = $this->transaction->findOrFail($id);
        $transaction->update($data);
        return $transaction->load($this->with);
    }

    /**
     * Delete Transaction
     *
     * @param $data
     * @return Transaction
     */
    public function delete(int $id)
    {
        $transaction = $this->transaction->findOrFail($id);
        $transaction->delete();
        return $transaction;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->transaction->newQuery();
        $query->with($this->with);

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
}
