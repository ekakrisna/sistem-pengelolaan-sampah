<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Enums\PickupEnum;
use App\Enums\UserEnum;
use App\Models\Pickup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PickupRepository
{
    protected Pickup $pickup;

    /** @var array<string> */
    protected array $with = [
        'schedule.village',
        'schedule.wasteType',
        'schedule.admin',
        'customer',
        'petugas',
        'transaction',
    ];

    public function __construct(Pickup $pickup)
    {
        $this->pickup = $pickup;
    }

    protected function isAdmin(?UserData $user): bool
    {
        $role = $user?->role?->value ?? $user?->role?->name ?? null;
        return in_array($role, [UserEnum::Admin->value, UserEnum::SuperAdmin->value], true);
    }

    /**
     * Helper: apply role-based scope to a query.
     * - admin  : no restriction
     * - customer: restrict by customer_id
     * - petugas : restrict by petugas_id
     */
    protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    {
        $query = ($query ?? $this->pickup->newQuery());

        if (!$user) {
            // Tidak ada user => block akses kecuali kamu memang ingin public.
            // Boleh diganti abort(403) atau biarkan tanpa filter sesuai kebutuhan.
            return $query; // <-- kalau ingin block non-logged-in, ubah ke: abort(403);
        }

        $role = $user->role->value ?? $user->role->name ?? null;

        return match ($role) {
            'customer' => $query->where('customer_id', $user->id),
            'petugas'  => $query->where('petugas_id', $user->id),
            // admin atau role lain bebas akses; tambahkan case lain bila perlu
            default    => $query,
        };
    }

    /**
     * List all (admin only realistically). Tambahkan parameter user jika perlu role filtering.
     */
    public function all(?UserData $user = null)
    {
        return $this->scopeForUser($user, $this->pickup->newQuery())
            ->with($this->with)
            ->get();
    }

    /**
     * Get pickup by id (role-aware).
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->scopeForUser($user, $this->pickup->newQuery())
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Save Pickup
     */
    public function save(array $data, ?UserData $user = null): Pickup
    {
        if ($this->isAdmin($user)) {
            if (empty($data['customer_id'])) {
                throw new \InvalidArgumentException('customer_id is required for admin.');
            }
        } else {
            unset($data['customer_id'], $data['petugas_id']);

            if ($user?->role?->value === UserEnum::Customer->value) {
                $data['customer_id'] = $user->id;
            } elseif ($user?->role?->value === UserEnum::Petugas->value) {
                $data['petugas_id'] = $user->id;
            } else {
                abort(403, 'Unauthorized');
            }
        }

        $pickup = $this->pickup->newQuery()->create($data);

        return $pickup->load($this->with);
    }


    /**
     * Update Pickup (role-aware).
     */
    public function update(array $data, int $id, ?UserData $user = null): Pickup
    {
        $pickup = $this->scopeForUser($user, $this->pickup->newQuery())
            ->whereKey($id)
            ->firstOrFail();

        if ($user) {
            unset($data['customer_id'], $data['petugas_id']);
            if ($user->role->value === UserEnum::Customer->value) {
                $data['customer_id'] = $user->id;
            } elseif ($user->role->value === UserEnum::Petugas->value) {
                $data['petugas_id'] = $user->id;
            }
        }

        if (array_key_exists('status', $data)) {
            if ($data['status'] instanceof PickupEnum) {
            } elseif (is_string($data['status'])) {
                $enum = PickupEnum::tryFrom($data['status']);
                if ($enum) {
                    $data['status'] = $enum;
                }
            }
        }

        $pickup->fill($data)->save();

        return $pickup->fresh()->load($this->with);
    }

    /**
     * Delete Pickup (role-aware).
     */
    public function delete(int $id, ?UserData $user = null): Pickup
    {
        $pickup = $this->scopeForUser($user, $this->pickup->newQuery())
            ->whereKey($id)
            ->firstOrFail();

        $pickup->delete();

        return $pickup;
    }

    /**
     * Pagination + Filters (role-aware).
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10, ?UserData $user = null)
    {
        $query = $this->scopeForUser($user, $this->pickup->newQuery())
            ->with($this->with);

        // Search by village
        if (!empty($filters['village'])) {
            $query->whereHas('schedule.village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Search by waste type
        if (!empty($filters['waste_type'])) {
            $qf = '%' . $filters['waste_type'] . '%';
            $query->whereHas('schedule.wasteType', function ($q) use ($qf) {
                $q->where('name', 'like', $qf)
                    ->orWhere('description', 'like', $qf);
            });
        }

        // Filter by admin (relasi schedule.admin)
        if (!empty($filters['admin'])) {
            $qf = '%' . $filters['admin'] . '%';
            $query->whereHas('schedule.admin', function ($q) use ($qf) {
                $q->where('name', 'like', $qf)
                    ->orWhere('email', 'like', $qf)
                    ->orWhere('phone', 'like', $qf);
            });
        }

        // Filter by status (support enum cast)
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            if ($status instanceof PickupEnum) {
                $query->where('status', $status);
            } elseif (is_string($status)) {
                $enum = PickupEnum::tryFrom($status);
                $query->where('status', $enum ? $enum : $status);
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by customer
        if (!empty($filters['customer'])) {
            $qf = '%' . $filters['customer'] . '%';
            $query->whereHas('customer', function ($q) use ($qf) {
                $q->where('name', 'like', $qf)
                    ->orWhere('email', 'like', $qf)
                    ->orWhere('phone', 'like', $qf);
            });
        }

        // Filter by petugas
        if (!empty($filters['petugas'])) {
            $qf = '%' . $filters['petugas'] . '%';
            $query->whereHas('petugas', function ($q) use ($qf) {
                $q->where('name', 'like', $qf)
                    ->orWhere('email', 'like', $qf)
                    ->orWhere('phone', 'like', $qf);
            });
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }
}
