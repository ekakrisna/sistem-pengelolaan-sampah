<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupScheduleRequest;
use App\Http\Resources\PickupScheduleResource;
use App\Models\PickupSchedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupScheduleController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PickupScheduleResource::collection(PickupSchedule::latest()->paginate(10));
    }

    public function store(PickupScheduleRequest $request): PickupScheduleResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickupSchedule = PickupSchedule::create($request->validated());
            return new PickupScheduleResource($pickupSchedule);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(PickupSchedule $pickupSchedule): PickupScheduleResource
    {
        return PickupScheduleResource::make($pickupSchedule);
    }

    public function update(PickupScheduleRequest $request, PickupSchedule $pickupSchedule): PickupScheduleResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickupSchedule->update($request->validated());
            return new PickupScheduleResource($pickupSchedule);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(PickupSchedule $pickupSchedule): \Illuminate\Http\JsonResponse
    {
        try {
            $pickupSchedule->delete();
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
