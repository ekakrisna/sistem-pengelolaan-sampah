<?php

namespace App\Repositories;

use App\Models\WasteType;

class WasteTypeRepository
{
    /**
     * @var WasteType
     */
    protected WasteType $wasteType;
    protected array $with = ['user', 'pickup_fees', 'pickup_schedules'];

    /**
     * WasteType constructor.
     *
     * @param WasteType $wasteType
     */
    public function __construct(WasteType $wasteType)
    {
        $this->wasteType = $wasteType;
    }

    /**
     * Get all wasteType.
     *
     * @return WasteType $wasteType
     */
    public function all()
    {
        return $this->wasteType->with($this->with)->get();
    }

    /**
     * Get wasteType by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->wasteType->with($this->with)->findOrFail($id);
    }

    /**
     * Save WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function save(array $data)
    {
        $wasteType = $this->wasteType->newQuery()->create($data);
        return $wasteType->load($this->with);
    }

    /**
     * Update WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function update(array $data, int $id)
    {
        $wasteType = $this->wasteType->findOrFail($id);
        $wasteType->update($data);
        return $wasteType->load($this->with);
    }

    /**
     * Delete WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function delete(int $id)
    {
        $wasteType = $this->wasteType->findOrFail($id);
        $wasteType->delete();
        return $wasteType;
    }

    public function paginateWithFilters(array $filters, int $pageSize = 10)
    {
        $query = $this->wasteType->newQuery();
        $query->with($this->with);

        // Search admin name
        if (!empty($filters['admin'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['admin']}%")
                    ->orWhere('email', 'like', "%{$filters['admin']}%")
                    ->orWhere('phone', 'like', "%{$filters['admin']}%");
            });
        }

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }
}
