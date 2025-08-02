<?php

namespace App\Repositories;

use App\Models\WasteType;

class WasteTypeRepository
{
    /**
     * @var WasteType
     */
    protected WasteType $wasteType;

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
        return $this->wasteType->get();
    }

    /**
     * Get wasteType by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->wasteType->find($id);
    }

    /**
     * Save WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function save(array $data)
    {
        return WasteType::create($data);
    }

    /**
     * Update WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function update(array $data, int $id)
    {
        $wasteType = $this->wasteType->find($id);
        $wasteType->update($data);
        return $wasteType;
    }

    /**
     * Delete WasteType
     *
     * @param $data
     * @return WasteType
     */
    public function delete(int $id)
    {
        $wasteType = $this->wasteType->find($id);
        $wasteType->delete();
        return $wasteType;
    }

    public function paginateWithFilters(array $filters, int $pageSize = 10)
    {
        $query = $this->wasteType->newQuery();
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
