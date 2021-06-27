<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Region;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    private $fakeGoogleResponse = '
    {
        "results" : [
           {
              "address_components" : [
                 {
                    "long_name" : "1600",
                    "short_name" : "1600",
                    "types" : [ "street_number" ]
                 },
                 {
                    "long_name" : "Amphitheatre Parkway",
                    "short_name" : "Amphitheatre Pkwy",
                    "types" : [ "route" ]
                 },
                 {
                    "long_name" : "Mountain View",
                    "short_name" : "Mountain View",
                    "types" : [ "locality", "political" ]
                 },
                 {
                    "long_name" : "Santa Clara County",
                    "short_name" : "Santa Clara County",
                    "types" : [ "administrative_area_level_2", "political" ]
                 },
                 {
                    "long_name" : "California",
                    "short_name" : "CA",
                    "types" : [ "administrative_area_level_1", "political" ]
                 },
                 {
                    "long_name" : "United States",
                    "short_name" : "US",
                    "types" : [ "country", "political" ]
                 },
                 {
                    "long_name" : "94043",
                    "short_name" : "94043",
                    "types" : [ "postal_code" ]
                 }
              ],
              "formatted_address" : "1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA",
              "geometry" : {
                 "location" : {
                    "lat" : 37.4267861,
                    "lng" : -122.0806032
                 },
                 "location_type" : "ROOFTOP",
                 "viewport" : {
                    "northeast" : {
                       "lat" : 37.4281350802915,
                       "lng" : -122.0792542197085
                    },
                    "southwest" : {
                       "lat" : 37.4254371197085,
                       "lng" : -122.0819521802915
                    }
                 }
              },
              "place_id" : "ChIJtYuu0V25j4ARwu5e4wwRYgE",
              "plus_code" : {
                 "compound_code" : "CWC8+R3 Mountain View, California, United States",
                 "global_code" : "849VCWC8+R3"
              },
              "types" : [ "street_address" ]
           }
        ],
        "status" : "OK"
     }';

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

        //Example: https://maps.googleapis.com/maps/api/geocode/json?latlng=40.714224,-73.961452&key=AIzaSyDev6n3CZg8q0m109MVmMhorcdIyqmqN68
        $longitude = $request->input('longitude');
        $latitude = $request->input('latitude');
        $coord = implode(',', [$latitude, $longitude]);

        /*Http::fake([
            '*' => Http::response($this->fakeGoogleResponse, 200, ['Headers'])
        ]);*/

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json',[
            'latlng' => $coord,
            'key' => 'AIzaSyCIzm0nAKGbHCp4Yf14Q-6RCaca2JlXM1g'
        ]);

        if ($response->successful()) {
            $parsedLocation = $this->parseLocation($response->body(), $coord);
            if ($parsedLocation['status'] == 'OK') {
                $this->storeLocation($parsedLocation);
            }
            return response()->json($parsedLocation);
        }

        if ($response->failed()) {
            return response()->json([
                'status' => 'Error',
                'test' => 'response'
            ]);
        }

    }

    /**
     * Parse location data
     *
     * @param string $data
     * @param string $coordinates
     * @return array
     */
    private function parseLocation(string $data, string $coordinates): array
    {

        $arData = json_decode($data, true);
        $city = false;
        $region = false;

        if ($arData['status'] !== 'OK') {
            return ['status' => $arData['status']];
        }

        $address = $arData['results'][0]['formatted_address'];
        foreach ($arData['results'][0]['address_components'] as $component) {
            if (in_array('locality', $component['types'])) {
                $city = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $component['types'])) {
                $region = $component['long_name'];
            }
        }

        return [
            'address' => $address,
            'city' => $city,
            'region' => $region,
            'coordinates' => $coordinates,
            'status' => $arData['status']
        ];
    }

    /**
     * Store location, city and region to DB
     *
     * @param array $arLocation
     * @return array
     */
    private function storeLocation(array $arLocation): array
    {

        $region = Region::firstOrCreate(['name' => $arLocation['region']]);

        $city = City::firstOrNew(['name' => $arLocation['city']]);
        if (!$city->exists) {
            $city->region()->associate($region);
            $city->save();
        }

        $location = Location::firstOrNew(['coordinates' => $arLocation['coordinates']]);
        if (!$location->exists) {
            $location->address = $arLocation['address'];
            $location->region()->associate($region);
            $location->city()->associate($city);
            $location->save();
        }

        return [$region, $city, $location];
    }

    /**
     * Get locations information
     *
     * @param int $id
     * @return string
     */
    public function getLocationInformation($id = null): string
    {
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
