<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Province, City, District, Village};
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function provinces(): JsonResponse
    {
        $provinces = Province::select('id', 'name', 'code')
            ->with(['cities:id,name,province_code'])
            ->get();

        return response()->json($provinces);
    }

    public function districts($cityId): JsonResponse
    {
        $districts = District::select('id', 'name', 'city_code')
            ->where('city_code', $cityId)
            ->get();

        return response()->json($districts);
    }

    /**
     * @param string $districtId
     * @return JsonResponse
     */
    public function villages($districtId): JsonResponse
    {
        $villages = Village::select('id', 'name', 'district_code')
            ->where('district_code', $districtId)
            ->get();

        return response()->json($villages);
    }
}
