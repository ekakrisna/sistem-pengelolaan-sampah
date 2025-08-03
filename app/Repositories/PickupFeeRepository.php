<?php

namespace App\Repositories;

use App\Models\PickupFee;

class PickupFeeRepository
{
    /**
     * @var PickupFee
     */
    protected PickupFee $pickupFee;
    protected $with = [
        'village',
        'wasteType',
        'admin'
    ];

    /**
     * PickupFee constructor.
     *
     * @param PickupFee $pickupFee
     */
    public function __construct(PickupFee $pickupFee)
    {
        $this->pickupFee = $pickupFee;
    }

    /**
     * Get all pickupFee.
     *
     * @return PickupFee $pickupFee
     */
    public function all()
    {
        return $this->pickupFee->with($this->with)->get();
    }

    /**
     * Get pickupFee by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->pickupFee->with($this->with)->findOrFail($id);
    }

    /**
     * Save PickupFee
     *
     * @param $data
     * @return PickupFee
     */
    public function save(array $data)
    {
        return PickupFee::create($data)->load($this->with);
    }

    /**
     * Update PickupFee
     *
     * @param $data
     * @return PickupFee
     */
    public function update(array $data, int $id)
    {
        $pickupFee = $this->pickupFee->findOrFail($id);
        $pickupFee->update($data);
        return $pickupFee->load($this->with);
    }

    /**
     * Delete PickupFee
     *
     * @param $data
     * @return PickupFee
     */
    public function delete(int $id)
    {
        $pickupFee = $this->pickupFee->findOrFail($id);
        $pickupFee->delete();
        return $pickupFee;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->pickupFee->newQuery();
        $query->with($this->with);

        // Search by village
        if (!empty($filters['village'])) {
            $query->whereHas('village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Search by waste type
        if (!empty($filters['waste_type'])) {
            $query->whereHas('wasteType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['waste_type'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['waste_type'] . '%');
            });
        }

        // Filter by admin
        if (!empty($filters['admin'])) {
            $query->whereHas('admin', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['admin'] . '%');
            });
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }
}
