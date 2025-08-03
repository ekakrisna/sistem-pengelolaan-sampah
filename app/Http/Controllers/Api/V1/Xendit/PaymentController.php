<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\XenditPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    protected XenditPaymentService $paymentService;

    public function __construct(XenditPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create new payment request to Xendit
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method'       => ['required', Rule::in(['qris', 'bca_va', 'gopay', 'ovo', 'dana'])],
            'amount'       => ['required', 'numeric', 'min:1000'],
            'customer_id'  => ['required', 'exists:users,id'],
            'metadata'     => ['nullable', 'array'],
        ]);

        try {
            $payment = $this->paymentService->createPayment(
                $validated['method'],
                $validated['amount'],
                $validated['customer_id'],
                $validated['metadata'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully.',
                'data' => $payment
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Xendit Payment Error: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'details' => [
                    'name' => 'Error::InternalServerError',
                    'message' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTrace() : [],
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all payments (with optional filters)
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with('customer')
            ->when($request->filled('status'), fn($q) =>
            $q->where('status', $request->input('status')))
            ->when($request->filled('method'), fn($q) =>
            $q->where('payment_method', $request->input('method')))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get a single payment
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with('customer', 'transaction')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Get available payment channels from Xendit
     */
    public function getAvailableChannels(): JsonResponse
    {
        try {
            $channels = $this->paymentService->getPaymentChannels();
            return response()->json([
                'success' => true,
                'data' => $channels,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'error' => true,
                'details' => [
                    'name' => 'Error::XenditChannelFetchError',
                    'message' => $e->getMessage(),
                    'errors' => [],
                ],
            ], 500);
        }
    }
}
