<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\CatalogController;
use App\Http\Controllers\Api\V1\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\V1\Customer\PickupController as CustomerPickupController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\Customer\TransactionController as CustomerTransactionController;
use App\Http\Controllers\Api\V1\SuperAdmin\PaymentController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupFeeController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupScheduleController;
use App\Http\Controllers\Api\V1\SuperAdmin\TransactionController;
use App\Http\Controllers\Api\V1\SuperAdmin\UserController;
use App\Http\Controllers\Api\V1\SuperAdmin\WasteTypeController;
use App\Http\Controllers\Api\V1\Xendit\PaymentController as XenditPaymentController;
use App\Http\Controllers\Api\V1\Xendit\WebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

Route::prefix(config('app.api.version'))
    ->name('api.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        Route::prefix('payments')->group(function () {
            Route::post('/callback', [WebhookController::class, 'handle']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');

            Route::middleware(['role:super_admin'])->prefix('super_admin')->name('super_admin.')->group(function () {
                Route::apiResource('/users', UserController::class);
                Route::apiResource('/waste_types', WasteTypeController::class);
                Route::apiResource('/pickup_schedules', PickupScheduleController::class);
                Route::apiResource('/pickups', PickupController::class);
                Route::apiResource('/pickup_fees', PickupFeeController::class);
                Route::apiResource('/transactions', TransactionController::class);
                Route::apiResource('/payments', PaymentController::class);
            });

            Route::prefix('payments')->group(function () {
                Route::post('/', [XenditPaymentController::class, 'create']);
                Route::get('/channels', [XenditPaymentController::class, 'channels']);
            });

            Route::middleware(['role:customer,super_admin'])->prefix('customer')->name('customer.')->group(function () {
                // Profile
                Route::prefix('profile')->name('profile.')->group(function () {
                    Route::get('/', [ProfileController::class, 'show']);
                    Route::put('/', [ProfileController::class, 'update']);
                });

                // Catalog (lihat jadwal & fee)
                Route::get('pickup-schedules', [CatalogController::class, 'schedules']);
                Route::get('pickup-fees', [CatalogController::class, 'fees']);

                // Pickups (request & list)
                Route::prefix('pickups')->name('pickups.')->group(function () {
                    Route::get('/', [CustomerPickupController::class, 'index']);
                    Route::post('/', [CustomerPickupController::class, 'store']);
                    Route::get('{id}', [CustomerPickupController::class, 'show']);
                    Route::delete('{id}', [CustomerPickupController::class, 'cancel']);
                });

                // Payments
                Route::get('payment-channels', [CustomerPaymentController::class, 'channels']);
                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::get('/', [CustomerPaymentController::class, 'index']);
                    Route::get('{id}', [CustomerPaymentController::class, 'show']);
                    Route::post('/', [CustomerPaymentController::class, 'create']);
                });

                // Transactions
                Route::prefix('transactions')->name('transactions.')->group(function () {
                    Route::get('/', [CustomerTransactionController::class, 'index']);
                    Route::get('{id}', [CustomerTransactionController::class, 'show']);
                });
            });
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