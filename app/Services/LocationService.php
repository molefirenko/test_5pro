<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Http;

class LocationService
{
    private $location;

    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    /**
     * Get location using coordinates
     *
     * @param float $longitude
     * @param float $latitude
     * @return array
     */
    public function getLocation(float $longitude, float $latitude): array
    {

        $coord = implode(',', [$latitude, $longitude]);

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => $coord,
            'key' => 'AIzaSyCIzm0nAKGbHCp4Yf14Q-6RCaca2JlXM1g'
        ]);

        if ($response->successful()) {
            $parsedLocation = $this->parseLocation($response->body(), $coord);
            if ($parsedLocation['status'] == 'OK') {
                $this->location->storeLocation($parsedLocation);
            }
            return $parsedLocation;
        }

        if ($response->failed()) {
            return [
                'status' => 'Error',
                'test' => 'response'
            ];
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
}
