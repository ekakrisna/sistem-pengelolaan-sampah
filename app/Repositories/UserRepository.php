<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * @var User
     */
    protected User $user;
    protected array $with = ['province', 'district', 'city', 'village'];
    /**
     * User constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get all user.
     *
     * @return User $user
     */
    public function all()
    {
        return $this->user->get();
    }

    /**
     * Get user by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->user->with($this->with)
            ->findOrFail($id);
    }

    /**
     * Save User
     *
     * @param $data
     * @return User
     */
    public function save(array $data)
    {
        return User::create($data)->load($this->with);
    }

    /**
     * Update User
     *
     * @param $data
     * @return User
     */
    public function update(array $data, int $id)
    {
        $user = $this->user->findOrFail($id);

        $filtered = array_filter($data, fn($value) => !is_null($value));

        if (isset($filtered['password'])) {
            $filtered['password'] = Hash::make($filtered['password']);
        }

        $user->update($filtered);
        return $user->load($this->with);
    }

    /**
     * Delete User
     *
     * @param $data
     * @return User
     */
    public function delete(int $id)
    {
        $user = $this->user->find($id);
        $user->delete();
        return $user;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10)
    {
        $query = $this->user->newQuery();
        $query->with($this->with);

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Role
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Status
        if (array_key_exists('status', $filters)) {
            $query->where('status', (bool) $filters['status']);
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }
}
