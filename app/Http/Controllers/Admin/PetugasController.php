<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PetugasController extends Controller
{
    /**
     * Display a listing of petugas.
     */
    public function index(Request $request): Response
    {
        $pageSize = (int) $request->input('page_size', 10); // default 10
        $petugas = User::where('role', 'petugas')
            ->select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->orderBy('name')
            ->paginate($pageSize)
            ->appends($request->only(['page', 'page_size'])); // untuk navigasi tetap membawa param

        return Inertia::render('Admin/Petugas/Index', [
            'petugas' => $petugas,
        ]);
    }


    /**
     * Store a newly created petugas.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        User::create([
            ...$validated,
            'role' => 'petugas',
            'password' => bcrypt('password'), // default password
        ]);

        return redirect()
            ->route('admin.petugas.index')
            ->with('success', 'Petugas berhasil ditambahkan.');
    }
}
