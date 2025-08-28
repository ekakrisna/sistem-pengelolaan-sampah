<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\CatalogController;
use App\Http\Controllers\Api\V1\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\V1\Customer\PaymentMethodController;
use App\Http\Controllers\Api\V1\Customer\PickupController as CustomerPickupController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\Customer\TransactionController as CustomerTransactionController;
use App\Http\Controllers\Api\V1\Xendit\AccountController;
use App\Http\Controllers\Api\V1\Xendit\PaymentController as XenditPaymentController;
use App\Http\Controllers\Api\V1\Xendit\PaymentRequestController;
use App\Http\Controllers\Api\V1\Xendit\PaymentsPayAndSaveController;
use App\Http\Controllers\Api\V1\Xendit\PaymentsPayController;
use App\Http\Controllers\Api\V1\Xendit\ReusablePaymentCodeController;
use App\Http\Controllers\Api\V1\SuperAdmin\PaymentController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupFeeController;
use App\Http\Controllers\Api\V1\SuperAdmin\PickupScheduleController;
use App\Http\Controllers\Api\V1\SuperAdmin\TransactionController;
use App\Http\Controllers\Api\V1\SuperAdmin\UserController;
use App\Http\Controllers\Api\V1\SuperAdmin\WasteTypeController;
use App\Http\Controllers\Api\V1\Xendit\PaymentChannelController;
use App\Http\Controllers\Api\V1\Xendit\WebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

Route::prefix(config('app.api.version'))
    ->name('api.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        Route::prefix('xendit')->name('xendit.')->group(function () {
            Route::post('webhook', [WebhookController::class, 'handle']);
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
                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::get('/', [CustomerPaymentController::class, 'index']);
                    Route::post('/', [CustomerPaymentController::class, 'store']);
                    Route::get('{id}', [CustomerPaymentController::class, 'show']);
                    Route::delete('{id}', [CustomerPaymentController::class, 'cancel']);
                });

                // Transactions
                Route::prefix('transactions')->name('transactions.')->group(function () {
                    Route::get('/', [CustomerTransactionController::class, 'index']);
                    Route::prefix('cart')->name('cart.')->group(function () {
                        Route::get('/', [CustomerTransactionController::class, 'cart']);
                        Route::put('{itemId}', [CustomerTransactionController::class, 'updateItem']);
                        Route::post('/', [CustomerTransactionController::class, 'addItem']);
                        Route::delete('{itemId}', [CustomerTransactionController::class, 'removeItem']);
                    });
                    Route::get('{id}', [CustomerTransactionController::class, 'show']);
                    Route::post('/', [CustomerTransactionController::class, 'store']);
                    Route::delete('{id}', [CustomerTransactionController::class, 'cancel']);
                });
            });

            Route::prefix('xendit')->name('xendit.')->group(function () {
                Route::prefix('payment-channels')->name('payment-channels.')->group(function () {
                    Route::get('/', [PaymentChannelController::class, 'index']);
                });

                Route::prefix('accounts')->name('accounts.')->group(function () {
                    Route::get('/', [AccountController::class, 'getAccounts']);
                    Route::get('{id}', [AccountController::class, 'getAccount']);
                    Route::post('create', [AccountController::class, 'createAccount']);
                });

                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::get('{id}', [XenditPaymentController::class, 'showByPaymentId']);
                });

                Route::prefix('payment-requests')->name('payment-requests.')->group(function () {
                    Route::get('/', [PaymentRequestController::class, 'getPaymentRequests']);
                    Route::get('{id}', [PaymentRequestController::class, 'showById']);
                    Route::post('{id}/cancel', [PaymentRequestController::class, 'cancelPaymentRequest']);
                    Route::post('{id}/simulate', [PaymentRequestController::class, 'simulatePaymentRequest']);

                    Route::prefix('reusable-payment-code')->name('reusable-payment-code.')->group(function () {
                        Route::post('create', [ReusablePaymentCodeController::class, 'createNoAmount']);
                        Route::post('create-with-amount', [ReusablePaymentCodeController::class, 'createWithAmount']);
                    });

                    Route::prefix('pay')->name('pay.')->group(function () {
                        Route::post('create-one-off-payment',  [PaymentsPayController::class, 'presentOneOff']);
                        Route::post('create-with-specific-code',  [PaymentsPayController::class, 'presentOneOffWithSpecificCode']);
                        Route::post('redirect-with-customer', [PaymentsPayController::class, 'redirectWithCustomer']);
                        Route::post('redirect-no-customer', [PaymentsPayController::class, 'redirectNoCustomer']);
                    });

                    Route::prefix('pay-and-save')->name('pay-and-save.')->group(function () {
                        Route::post('create', [PaymentsPayAndSaveController::class, 'create']);
                    });
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
