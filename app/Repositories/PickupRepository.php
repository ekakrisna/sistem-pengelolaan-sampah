<?php

namespace App\Repositories;

use App\Models\Pickup;

class PickupRepository
{
    /**
     * @var Pickup
     */
    protected Pickup $pickup;
    protected $with = [
        'schedule.village',
        'schedule.wasteType',
        'schedule.admin',
        'customer',
        'petugas',
        'transaction'
    ];

    /**
     * Pickup constructor.
     *
     * @param Pickup $pickup
     */
    public function __construct(Pickup $pickup)
    {
        $this->pickup = $pickup;
    }

    /**
     * Get all pickup.
     *
     * @return Pickup $pickup
     */
    public function all()
    {
        return $this->pickup->with($this->with)->get();
    }

    /**
     * Get pickup by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->pickup->with($this->with)->findOrFail($id);
    }

    /**
     * Save Pickup
     *
     * @param $data
     * @return Pickup
     */
    public function save(array $data)
    {
        return Pickup::create($data)->load($this->with);
    }

    /**
     * Update Pickup
     *
     * @param $data
     * @return Pickup
     */
    public function update(array $data, int $id)
    {
        $pickup = $this->pickup->findOrFail($id);
        $pickup->update($data);
        return $pickup->load($this->with);
    }

    /**
     * Delete Pickup
     *
     * @param $data
     * @return Pickup
     */
    public function delete(int $id)
    {
        $pickup = $this->pickup->findOrFail($id);
        $pickup->delete();
        return $pickup;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->pickup->newQuery();
        $query->with($this->with);

        // Search by village (through schedule.village)
        if (!empty($filters['village'])) {
            $query->whereHas('schedule.village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Search by waste type
        if (!empty($filters['waste_type'])) {
            $query->whereHas('schedule.wasteType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['waste_type'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['waste_type'] . '%');
            });
        }

        // Filter by admin
        if (!empty($filters['admin'])) {
            $query->whereHas('schedule.admin', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['admin'] . '%');
            });
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by customer
        if (!empty($filters['customer'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['customer'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['customer'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['customer'] . '%');
            });
        }

        // Filter by petugas
        if (!empty($filters['petugas'])) {
            $query->whereHas('petugas', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['petugas'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['petugas'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['petugas'] . '%');
            });
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }
}
