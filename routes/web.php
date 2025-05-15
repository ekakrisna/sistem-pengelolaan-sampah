<?php

use App\Http\Controllers\Admin\PetugasController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::prefix('api')->name('api.')->group(function () {
    Route::get('districts/{city}', [LocationController::class, 'districts'])->name('districts');
    Route::get('villages/{district}', [LocationController::class, 'villages'])->name('villages');
});

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('petugas', PetugasController::class)->only([
            'index',
            'store',
            'update',
            'destroy'
        ]);
    });
});


require __DIR__ . '/auth.php';
