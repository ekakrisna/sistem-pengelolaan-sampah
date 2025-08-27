<?php

namespace App\Services;

use App\Data\PickupFeeData;
use App\Models\PickupFee;
use App\Repositories\PickupFeeRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PickupFeeService
{
    /**
     * @var PickupFeeRepository $pickupFeeRepository
     */
    protected $pickupFeeRepository;

    /**
     * DummyClass constructor.
     *
     * @param PickupFeeRepository $pickupFeeRepository
     */
    public function __construct(PickupFeeRepository $pickupFeeRepository)
    {
        $this->pickupFeeRepository = $pickupFeeRepository;
    }

    /**
     * Get all pickupFeeRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->pickupFeeRepository->all();
    }

    /**
     * Get pickupFeeRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->pickupFeeRepository->getById($id);
    }

    /**
     * Validate pickupFeeRepository data.
     * Store to DB if there are no errors.
     *
     * @param PickupFeeData $data
     * @return String
     */
    public function save(PickupFeeData $data)
    {
        return $this->pickupFeeRepository->save($data->toArray());
    }

    /**
     * Update pickupFeeRepository data
     * Store to DB if there are no errors.
     *
     * @param PickupFeeData $data
     * @return String
     */
    public function update(PickupFeeData $data, int $id)
    {
        DB::beginTransaction();
        try {
            $pickupFeeRepository = $this->pickupFeeRepository->update($data->toArray(), $id);
            DB::commit();
            return $pickupFeeRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Delete pickupFeeRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $pickupFeeRepository = $this->pickupFeeRepository->delete($id);
            DB::commit();
            return $pickupFeeRepository;
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
        return $this->pickupFeeRepository->paginateWithFilters($filters, $pageSize);
    }
}
