<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['coordinates', 'address'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Store location, city and region to DB
     *
     * @param array $arLocation
     * @return array
     */
    public function storeLocation(array $arLocation): array
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
}
