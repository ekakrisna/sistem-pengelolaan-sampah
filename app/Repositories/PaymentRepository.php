<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    /**
     * @var Payment
     */
    protected Payment $payment;

    /** @var array<string> */
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
     * Scope query berdasarkan role user:
     * - super_admin/admin : full access
     * - customer          : payment miliknya (customer_id = $user->id)
     * - petugas           : payment yang terkait pickup di transaction dimana pickup.petugas_id = $user->id
     */
    protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    {
        $query ??= $this->payment->newQuery();

        if (!$user) {
            return $query; // biarkan bebas; kalau mau, bisa diubah ke abort(403).
        }

        $role = $user->role->value ?? $user->role->name ?? (string) $user->role ?? null;
        $role = strtolower((string) $role);

        return match ($role) {
            'customer' => $query->where('customer_id', $user->id),

            'petugas'  => $query->whereHas('transaction.pickup', function (Builder $q) use ($user) {
                $q->where('petugas_id', $user->id);
            }),

            // admin/super_admin (atau role lain) tanpa restriksi
            default    => $query,
        };
    }

    /**
     * Get all payment.
     *
     * @return Payment $payment
     */
    public function all(?UserData $user = null)
    {
        return $this->scopeForUser($user)
            ->with($this->with)
            ->get();
    }


    /**
     * Get payment by id
     *
     * @param $id
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
    public function update(array $data, int $id, ?UserData $user = null)
    {
        $payment = $this->scopeForUser($user)->findOrFail($id);
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
        $payment = $this->scopeForUser($user)->findOrFail($id);
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
        $query = $this->scopeForUser($user)->with($this->with);

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
