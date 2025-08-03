<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    /**
     * @var Payment
     */
    protected Payment $payment;
    protected array $with = ['customer', 'transactions'];

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
     * Get all payment.
     *
     * @return Payment $payment
     */
    public function all()
    {
        return $this->payment->with($this->with)->get();
    }

    /**
     * Get payment by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->payment->with($this->with)->findOrFail($id);
    }

    /**
     * Save Payment
     *
     * @param $data
     * @return Payment
     */
    public function save(array $data)
    {
        return Payment::create($data)->load($this->with);
    }

    /**
     * Update Payment
     *
     * @param $data
     * @return Payment
     */
    public function update(array $data, int $id)
    {
        $payment = $this->payment->findOrFail($id);
        $payment->update($data);
        return $payment->load($this->with);
    }

    /**
     * Delete Payment
     *
     * @param $data
     * @return Payment
     */
    public function delete(int $id)
    {
        $payment = $this->payment->findOrFail($id);
        $payment->delete();
        return $payment;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->payment->newQuery();
        $query->with($this->with);

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
}
