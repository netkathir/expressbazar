<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegionZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'regions_zones';

    protected $fillable = [
        'country_id',
        'city_id',
        'zone_name',
        'zone_code',
        'delivery_available',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_available' => 'boolean',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
