<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PetugasController extends Controller
{
    /**
     * Display a listing of petugas.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $pageSize = (int) $request->input('page_size', 10);

        $query = User::where('role', 'petugas');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $petugas = $query->with(['province', 'city', 'district', 'village'])
            ->paginate($pageSize)
            ->appends($request->only(['search', 'page', 'page_size']));

        $provinces = Province::with('cities')->get();

        return Inertia::render('Admin/Petugas/Index', [
            'petugas' => $petugas,
            'provinces' => $provinces,
            'filters' => $request->all(['search', 'page', 'page_size']),
        ]);
    }


    /**
     * Store a newly created petugas.
     */
    public function store(Request $request): RedirectResponse
    {

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20'],
                'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
                'city_id' => ['nullable', 'integer', 'exists:cities,id'],
                'district_id' => ['nullable', 'integer', 'exists:districts,id'],
                'village_id' => ['nullable', 'integer', 'exists:villages,id'],
                'address_detail' => ['nullable', 'string', 'max:255'],
            ]);

            DB::beginTransaction();

            $user = User::create([
                ...$validated,
                'role' => 'petugas',
                'password' => bcrypt('password'),
            ]);

            dd($user);


            DB::commit();

            return Redirect::route('admin.petugas.index')
                ->with('success', 'Petugas berhasil ditambahkan.');
        } catch (ValidationException $e) {
            // Rollback the transaction for validation exceptions
            DB::rollBack();

            // Return back with validation errors
            return Redirect::back()->withErrors($e->validator)->withInput();
        } catch (Throwable $e) {
            DB::rollBack();

            return Redirect::route('admin.petugas.index')
                ->with('error', 'Terjadi kesalahan saat menambahkan petugas: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified petugas.
     */
    public function update(Request $request, User $user): RedirectResponse
    {

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'phone' => ['required', 'string', 'max:20'],
                'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
                'city_id' => ['nullable', 'integer', 'exists:cities,id'],
                'district_id' => ['nullable', 'integer', 'exists:districts,id'],
                'village_id' => ['nullable', 'integer', 'exists:villages,id'],
                'address_detail' => ['nullable', 'string', 'max:255'],
            ]);

            DB::beginTransaction();

            $user->update($validated);

            DB::commit();

            return Redirect::route('admin.petugas.index')
                ->with('success', 'Petugas diperbarui.');
        } catch (Throwable $e) {
            DB::rollBack();

            return Redirect::route('admin.petugas.index')
                ->with('error', 'Terjadi kesalahan saat memperbarui petugas: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified petugas.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);

            $user->delete();

            DB::commit();

            return Redirect::route('admin.petugas.index')
                ->with('success', 'Petugas berhasil dihapus.');
        } catch (Throwable $e) {
            DB::rollBack();

            return Redirect::route('admin.petugas.index')
                ->with('error', 'Terjadi kesalahan saat menghapus petugas: ' . $e->getMessage());
        }
    }
}
