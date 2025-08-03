<?php

namespace App\Services;

use App\Models\Pickup;
use App\Repositories\PickupRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PickupService
{
    /**
     * @var PickupRepository $pickupRepository
     */
    protected $pickupRepository;

    /**
     * DummyClass constructor.
     *
     * @param PickupRepository $pickupRepository
     */
    public function __construct(PickupRepository $pickupRepository)
    {
        $this->pickupRepository = $pickupRepository;
    }

    /**
     * Get all pickupRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->pickupRepository->all();
    }

    /**
     * Get pickupRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->pickupRepository->getById($id);
    }

    /**
     * Validate pickupRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->pickupRepository->save($data);
    }

    /**
     * Update pickupRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $pickupRepository = $this->pickupRepository->update($data, $id);
            DB::commit();
            return $pickupRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
        }
    }

    /**
     * Delete pickupRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $pickupRepository = $this->pickupRepository->delete($id);
            DB::commit();
            return $pickupRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to delete post data');
        }
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10)
    {
        return $this->pickupRepository->paginateWithFilters($filters, $pageSize);
    }
}
