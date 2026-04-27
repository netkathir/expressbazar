<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_name',
        'email',
        'phone',
        'address',
        'pincode',
        'logo_path',
        'country_id',
        'city_id',
        'region_zone_id',
        'inventory_mode',
        'api_url',
        'api_key',
        'credentials',
        'status',
        'created_by',
        'updated_by',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function zone()
    {
        return $this->belongsTo(RegionZone::class, 'region_zone_id');
    }
}
