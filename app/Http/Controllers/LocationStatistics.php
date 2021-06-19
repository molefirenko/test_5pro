<?php

namespace App\Http\Controllers;

use App\Models\Location;


class LocationStatistics extends Controller
{
    /**
     * Get locations information
     *
     * @param int $id
     * @return string
     */
    public function getLocationInformation($id = null) {
        if ( isset($id) ) {
            $data = $this->getRegionLocations($id);
        }
        else {
            $data = $this->getAllLocations();
        }

        return response()->json($data);
    }

    /**
     * Get all stored locations
     *
     * @return array
     */
    public function getAllLocations() {
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
    public function getRegionLocations($regionId) {
        $locations = Location::where('region_id', $regionId)->pluck('address')->toArray();
        if (empty($locations)) {
            return ['status' => 'Empty'];
        }

        return ['status' => 'OK', 'locations' => $locations];
    }
}
