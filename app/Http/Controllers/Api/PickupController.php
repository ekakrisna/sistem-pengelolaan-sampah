<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupRequest;
use App\Http\Resources\PickupResource;
use App\Models\Pickup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PickupResource::collection(Pickup::latest()->paginate(10));
    }

    public function store(PickupRequest $request): PickupResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickup = Pickup::create($request->validated());
            return new PickupResource($pickup);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Pickup $pickup): PickupResource
    {
        return PickupResource::make($pickup);
    }

    public function update(PickupRequest $request, Pickup $pickup): PickupResource|\Illuminate\Http\JsonResponse
    {
        try {
            $pickup->update($request->validated());
            return new PickupResource($pickup);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Pickup $pickup): \Illuminate\Http\JsonResponse
    {
        try {
            $pickup->delete();
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
