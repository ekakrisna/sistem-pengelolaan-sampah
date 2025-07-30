<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('{version}')
    ->name('api.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');

            Route::apiResource('/users', UserController::class);
        });
    })
    ->where(['version' => 'v[0-9]+']);


// Route::apiResource('/pickups', App\Http\Controllers\API\PickupController::class);

// Route::apiResource('/pickup_schedules', App\Http\Controllers\API\PickupScheduleController::class);

// Route::apiResource('/waste_types', App\Http\Controllers\API\WasteTypeController::class);

// Route::apiResource('/transactions', App\Http\Controllers\API\TransactionController::class);

// Route::apiResource('/payments', App\Http\Controllers\API\PaymentController::class);

// Route::get('districts/{city}', [LocationController::class, 'districts'])->name('districts');
// Route::get('villages/{district}', [LocationController::class, 'villages'])->name('villages');
