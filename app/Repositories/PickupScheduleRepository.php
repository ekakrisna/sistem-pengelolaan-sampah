<?php

namespace App\Repositories;

use App\Models\PickupSchedule;

class PickupScheduleRepository
{
    /**
     * @var PickupSchedule
     */
    protected PickupSchedule $pickupSchedule;
    protected array $with = ['wasteType', 'pickups', 'admin', 'village'];

    /**
     * PickupSchedule constructor.
     *
     * @param PickupSchedule $pickupSchedule
     */
    public function __construct(PickupSchedule $pickupSchedule)
    {
        $this->pickupSchedule = $pickupSchedule;
    }

    /**
     * Get all pickupSchedule.
     *
     * @return PickupSchedule $pickupSchedule
     */
    public function all()
    {
        return $this->pickupSchedule->with($this->with)->get();
    }

    /**
     * Get pickupSchedule by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->pickupSchedule->with($this->with)->findOrFail($id);
    }

    /**
     * Save PickupSchedule
     *
     * @param $data
     * @return PickupSchedule
     */
    public function save(array $data)
    {
        $pickupSchedule = $this->pickupSchedule->newQuery()->create($data);
        return $pickupSchedule->load($this->with);
    }

    /**
     * Update PickupSchedule
     *
     * @param $data
     * @return PickupSchedule
     */
    public function update(array $data, int $id)
    {
        $pickupSchedule = $this->pickupSchedule->findOrFail($id);
        $pickupSchedule->update($data);
        return $pickupSchedule->load($this->with);
    }

    /**
     * Delete PickupSchedule
     *
     * @param $data
     * @return PickupSchedule
     */
    public function delete(int $id)
    {
        $pickupSchedule = $this->pickupSchedule->findOrFail($id);
        $pickupSchedule->delete();
        return $pickupSchedule;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->pickupSchedule->newQuery();
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

        // Filter by multiple day_of_week
        if (!empty($filters['day_of_week'])) {
            $days = is_array($filters['day_of_week'])
                ? $filters['day_of_week']
                : explode(',', $filters['day_of_week']);

            $query->whereIn('day_of_week', array_map('strtolower', $days));
        }

        // Filter by start pickup time
        if (!empty($filters['start_pickup_time'])) {
            $query->where('start_pickup_time', '>=', $filters['start_pickup_time']);
        }

        // Filter by end pickup time
        if (!empty($filters['end_pickup_time'])) {
            $query->where('end_pickup_time', '<=', $filters['end_pickup_time']);
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
