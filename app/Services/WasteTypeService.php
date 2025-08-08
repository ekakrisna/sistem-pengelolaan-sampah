<?php

namespace App\Services;

use App\Repositories\WasteTypeRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WasteTypeService
{
    /**
     * @var WasteTypeRepository $wasteTypeRepository
     */
    protected $wasteTypeRepository;

    /**
     * DummyClass constructor.
     *
     * @param WasteTypeRepository $wasteTypeRepository
     */
    public function __construct(WasteTypeRepository $wasteTypeRepository)
    {
        $this->wasteTypeRepository = $wasteTypeRepository;
    }

    /**
     * Get all wasteTypeRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->wasteTypeRepository->all();
    }

    /**
     * Get wasteTypeRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->wasteTypeRepository->getById($id);
    }

    /**
     * Validate wasteTypeRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->wasteTypeRepository->save($data);
    }

    /**
     * Update wasteTypeRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $wasteTypeRepository = $this->wasteTypeRepository->update($data, $id);
            DB::commit();
            return $wasteTypeRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Delete wasteTypeRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $wasteTypeRepository = $this->wasteTypeRepository->delete($id);
            DB::commit();
            return $wasteTypeRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10)
    {
        return $this->wasteTypeRepository->paginateWithFilters($filters, $pageSize);
    }
}
