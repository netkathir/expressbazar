<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_name',
        'tax_percentage',
        'country_id',
        'region_name',
        'status',
        'created_by',
        'updated_by',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
