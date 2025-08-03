<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\SuperAdmin\PaymentController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupFeeController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupScheduleController;
use App\Http\Controllers\Api\V1\SuperAdmin\TransactionController;
use App\Http\Controllers\Api\V1\SuperAdmin\UserController;
use App\Http\Controllers\Api\V1\SuperAdmin\WasteTypeController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

Route::prefix(config('app.api.version'))
    ->name('api.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');

            Route::middleware(['role:super_admin'])->group(function () {
                Route::apiResource('/users', UserController::class);
                Route::apiResource('/waste_types', WasteTypeController::class);
                Route::apiResource('/pickup_schedules', PickupScheduleController::class);
                Route::apiResource('/pickups', PickupController::class);
                Route::apiResource('/pickup_fees', PickupFeeController::class);
                Route::apiResource('/transactions', TransactionController::class);
                Route::apiResource('/payments', PaymentController::class);
            });

            // Route::prefix('payments')->group(function () {
            //     Route::post('/qris', [PaymentController::class, 'createQris']);
            //     Route::post('/ovo', [PaymentController::class, 'createOvo']);
            //     Route::post('/gopay', [PaymentController::class, 'createGopay']);
            //     Route::post('/dana', [PaymentController::class, 'createDana']);
            //     Route::post('/bca-va', [PaymentController::class, 'createBcaVa']);
            // });
        });
    });

Route::fallback(function ($e) {
    return errorResponse(
        name: 'Error::RequestError::NotFound',
        message: "The route $e could not be found.",
        statusCode: Response::HTTP_NOT_FOUND
    );
});

// Route::apiResource('/pickups', App\Http\Controllers\API\PickupController::class);

// Route::apiResource('/pickup_schedules', App\Http\Controllers\API\PickupScheduleController::class);

// Route::apiResource('/transactions', App\Http\Controllers\API\TransactionController::class);

// Route::apiResource('/payments', App\Http\Controllers\API\PaymentController::class);

// Route::get('districts/{city}', [LocationController::class, 'districts'])->name('districts');
// Route::get('villages/{district}', [LocationController::class, 'villages'])->name('villages');