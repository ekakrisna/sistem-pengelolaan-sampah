<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use App\Services\XenditPaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ApiResponse;
    protected XenditPaymentService $paymentService;
    protected PricingService $pricingService;

    public function __construct(
        XenditPaymentService $paymentService,
        PricingService $pricingService
    ) {
        $this->paymentService = $paymentService;
        $this->pricingService = $pricingService;
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_category' => ['required', Rule::in(['QRIS', 'VIRTUAL_ACCOUNT', 'RETAIL_OUTLET', 'EWALLET'])],
            'channel_code'     => ['required', 'string', 'max:50'],
            'customer_id'      => ['required', 'exists:users,id'],

            // multi-item by fee_id
            'fee_items'                       => ['required', 'array', 'min:1'],
            'fee_items.*.pickup_fee_id'       => ['required', 'integer', 'exists:pickup_fees,id'],
            'fee_items.*.qty'                 => ['nullable', 'integer', 'min:1'],
            'fee_items.*.description'         => ['nullable', 'string', 'max:191'],

            // optional metadata lain (redirect/callback/phone_number dsb)
            'metadata'         => ['nullable', 'array'],
            // kalau tetap mau relasi ke pickup tertentu, boleh tambahkan:
            'pickup_id'        => ['required', 'exists:pickups,id'],
        ]);

        try {
            // 1) Build draft dari pricing service
            $draft = $this->pricingService
                ->buildDraftFromFeeIds($validated['fee_items']);

            $amountInt = (int) round((float) $draft['grand_total'], 0);

            // 2) Merge metadata
            $metadata = array_merge($validated['metadata'] ?? [], [
                'pickup_id'   => (int) $validated['pickup_id'],
                'draft_items' => $draft['items'],
            ]);

            // 3) Call service Xendit
            $payment = $this->paymentService->createPaymentByChannelCategory(
                channelCategory: $validated['channel_category'],
                channelCode: $validated['channel_code'],
                amount: $amountInt,
                customerId: (int) $validated['customer_id'],
                metadata: $metadata
            );

            return $this->successResponse($payment, 'Payment created successfully.');
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse('Error::XenditPaymentFailed', $e->getMessage(), statusCode: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get available payment channels from Xendit
     */
    public function channels(): JsonResponse
    {
        try {
            $channels = $this->paymentService->getPaymentChannels();
            return $this->successResponse($channels, 'Payment channels retrieved successfully.');
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                'Error::XenditChannelFetchError',
                $e->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
