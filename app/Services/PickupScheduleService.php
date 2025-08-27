<?php

namespace App\Services;

use App\Data\PickupScheduleData;
use App\Models\PickupSchedule;
use App\Repositories\PickupScheduleRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PickupScheduleService
{
    /**
     * @var PickupScheduleRepository $pickupScheduleRepository
     */
    protected $pickupScheduleRepository;

    /**
     * DummyClass constructor.
     *
     * @param PickupScheduleRepository $pickupScheduleRepository
     */
    public function __construct(PickupScheduleRepository $pickupScheduleRepository)
    {
        $this->pickupScheduleRepository = $pickupScheduleRepository;
    }

    /**
     * Get all pickupScheduleRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->pickupScheduleRepository->all();
    }

    /**
     * Get pickupScheduleRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->pickupScheduleRepository->getById($id);
    }

    /**
     * Validate pickupScheduleRepository data.
     * Store to DB if there are no errors.
     *
     * @param PickupScheduleData $data
     * @return String
     */
    public function save(PickupScheduleData $data)
    {
        return $this->pickupScheduleRepository->save($data->toArray());
    }

    /**
     * Update pickupScheduleRepository data
     * Store to DB if there are no errors.
     *
     * @param PickupScheduleData $data
     * @return String
     */
    public function update(PickupScheduleData $data, int $id)
    {
        DB::beginTransaction();
        try {
            $pickupScheduleRepository = $this->pickupScheduleRepository
                ->update($data->toArray(), $id);
            DB::commit();
            return $pickupScheduleRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Delete pickupScheduleRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $pickupScheduleRepository = $this->pickupScheduleRepository->delete($id);
            DB::commit();
            return $pickupScheduleRepository;
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
        return $this->pickupScheduleRepository->paginateWithFilters($filters, $pageSize);
    }
}
