<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class LocationController extends Controller
{

    private $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get location by coordinates uses Geocoding API
     *
     * @param Request $request
     * @return string
     */
    public function getAddressByCoordinates(Request $request): string
    {

        $validator = Validator::make($request->all(), [
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages());
        }

        $response = $this->locationService->getLocation($request->input('longitude'), $request->input('latitude'));

        return response()->json($response);
    }

    /**
     * Get locations information
     *
     * @param int $id
     * @return string
     */
    public function getLocationInformation($id = null): string
    {
        if (isset($id)) {
            $data = $this->getRegionLocations($id);
        } else {
            $data = $this->getAllLocations();
        }

        return response()->json($data);
    }

    /**
     * Get all stored locations
     *
     * @return array
     */
    private function getAllLocations(): array
    {
        $locations = Location::all()->pluck('address')->toArray();
        if (empty($locations)) {
            return ['status' => 'Empty'];
        }

        return ['status' => 'OK', 'locations' => $locations];
    }

    /**
     * Get locations by region id
     *
     * @param int $regionId
     * @return array
     */
    private function getRegionLocations(int $regionId): array
    {
        $locations = Location::where('region_id', $regionId)->pluck('address')->toArray();
        if (empty($locations)) {
            return ['status' => 'Empty'];
        }

        return ['status' => 'OK', 'locations' => $locations];
    }
}
