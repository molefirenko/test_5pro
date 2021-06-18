<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['coordinates', 'address'];

    public function city() {
        return $this->belongsTo(City::class);
    }

    public function region() {
        return $this->belongsTo(Region::class);
    }
}
