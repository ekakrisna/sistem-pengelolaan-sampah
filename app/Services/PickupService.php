<?php

namespace App\Services;

use App\Data\PickupData;
use App\Data\UserData;
use App\Repositories\PickupRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    public function getAll(?UserData $user)
    {
        return $this->pickupRepository->all($user);
    }

    /**
     * Get pickupRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->pickupRepository->getById($id, $user);
    }

    /**
     * Validate pickupRepository data.
     * Store to DB if there are no errors.
     *
     * @param PickupData $data
     * @param UserData $user
     * @return String
     */
    public function save(PickupData $data, ?UserData $user = null)
    {
        return $this->pickupRepository->save($data->toArray(), $user);
    }

    /**
     * Update pickupRepository data
     * Store to DB if there are no errors.
     *
     * @param PickupData $data
     * @param $id
     * @param UserData $user
     * @return String
     */
    public function update(PickupData $data, int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $pickupRepository = $this->pickupRepository->update($data->toArray(), $id, $user);
            DB::commit();
            return $pickupRepository;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    /**
     * Delete pickupRepository by id.
     *
     * @param $id
     * @param UserData $user
     * @return String
     */
    public function deleteById(int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $pickupRepository = $this->pickupRepository->delete($id, $user);
            DB::commit();
            return $pickupRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @param UserData|null $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10, ?UserData $user = null)
    {
        return $this->pickupRepository->paginateWithFilters($filters, $pageSize, $user);
    }
}
