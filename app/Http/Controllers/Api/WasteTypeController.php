<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WasteTypeRequest;
use App\Http\Resources\WasteTypeResource;
use App\Models\WasteType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WasteTypeController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return WasteTypeResource::collection(WasteType::latest()->paginate(10));
    }

    public function store(WasteTypeRequest $request): WasteTypeResource|\Illuminate\Http\JsonResponse
    {
        try {
            $wasteType = WasteType::create($request->validated());
            return new WasteTypeResource($wasteType);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(WasteType $wasteType): WasteTypeResource
    {
        return WasteTypeResource::make($wasteType);
    }

    public function update(WasteTypeRequest $request, WasteType $wasteType): WasteTypeResource|\Illuminate\Http\JsonResponse
    {
        try {
            $wasteType->update($request->validated());
            return new WasteTypeResource($wasteType);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(WasteType $wasteType): \Illuminate\Http\JsonResponse
    {
        try {
            $wasteType->delete();
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
