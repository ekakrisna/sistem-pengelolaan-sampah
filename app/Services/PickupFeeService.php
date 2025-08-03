<?php

namespace App\Services;

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
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->pickupFeeRepository->save($data);
    }

    /**
     * Update pickupFeeRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $pickupFeeRepository = $this->pickupFeeRepository->update($data, $id);
            DB::commit();
            return $pickupFeeRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
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
        return $this->pickupFeeRepository->paginateWithFilters($filters, $pageSize);
    }
}
