<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $role = Auth::user()->role;

        return match ($role) {
            'super_admin' => Inertia::render('Dashboard/SuperAdmin'),
            'admin'    => Inertia::render('Dashboard/Admin'),
            'petugas'  => Inertia::render('Dashboard/Petugas'),
            'customer' => Inertia::render('Dashboard/Customer'),
            default    => abort(403, 'No dashboard available for this role.')
        };
    }
}
