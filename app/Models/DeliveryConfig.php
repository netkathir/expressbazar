<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryConfig extends Model
{
    use HasFactory;

    protected $table = 'delivery_config';

    protected $fillable = [
        'country_id',
        'city_id',
        'zone_id',
        'delivery_available',
        'delivery_charge',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'delivery_available' => 'boolean',
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
        return $this->belongsTo(RegionZone::class, 'zone_id');
    }
}
