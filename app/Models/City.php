<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'state',
        'city_name',
        'city_code',
        'status',
        'created_by',
        'updated_by',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function zones()
    {
        return $this->hasMany(RegionZone::class);
    }
}
