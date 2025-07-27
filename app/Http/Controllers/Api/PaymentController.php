<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PaymentResource::collection(Payment::latest()->paginate(10));
    }

    public function store(PaymentRequest $request): PaymentResource|\Illuminate\Http\JsonResponse
    {
        try {
            $payment = Payment::create($request->validated());
            return new PaymentResource($payment);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Payment $payment): PaymentResource
    {
        return PaymentResource::make($payment);
    }

    public function update(PaymentRequest $request, Payment $payment): PaymentResource|\Illuminate\Http\JsonResponse
    {
        try {
            $payment->update($request->validated());
            return new PaymentResource($payment);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Payment $payment): \Illuminate\Http\JsonResponse
    {
        try {
            $payment->delete();
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
