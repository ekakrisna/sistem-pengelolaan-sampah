<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupFeeRequest;
use App\Http\Resources\PickupFeeResource;
use App\Models\PickupFee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupFeeController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PickupFeeResource::collection(PickupFee::latest()->paginate(10));
    }

    public function store(PickupFeeRequest $request): PickupFeeResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickupFee = PickupFee::create($request->validated());
            return new PickupFeeResource($pickupFee);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(PickupFee $pickupFee): PickupFeeResource
    {
        return PickupFeeResource::make($pickupFee);
    }

    public function update(PickupFeeRequest $request, PickupFee $pickupFee): PickupFeeResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickupFee->update($request->validated());
            return new PickupFeeResource($pickupFee);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(PickupFee $pickupFee): \Illuminate\Http\JsonResponse
    {
        try {
            $pickupFee->delete();
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
