<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_name',
        'country_code',
        'currency',
        'timezone',
        'status',
        'created_by',
        'updated_by',
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
