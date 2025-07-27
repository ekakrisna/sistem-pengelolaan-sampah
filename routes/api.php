<?php

use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::apiResource('/users', App\Http\Controllers\API\UserController::class);

Route::apiResource('/pickups', App\Http\Controllers\API\PickupController::class);

Route::apiResource('/pickup_schedules', App\Http\Controllers\API\PickupScheduleController::class);

Route::apiResource('/waste_types', App\Http\Controllers\API\WasteTypeController::class);

Route::apiResource('/transactions', App\Http\Controllers\API\TransactionController::class);

Route::apiResource('/payments', App\Http\Controllers\API\PaymentController::class);

Route::get('districts/{city}', [LocationController::class, 'districts'])->name('districts');
Route::get('villages/{district}', [LocationController::class, 'villages'])->name('villages');
