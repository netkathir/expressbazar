<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'service_radius_km',
        'is_active',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'service_radius_km' => 'float',
            'is_active' => 'bool',
            'rating' => 'float',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'vendor_location');
    }

    protected static function booted(): void
    {
        static::saving(function (Vendor $vendor): void {
            if (! $vendor->slug && $vendor->name) {
                $vendor->slug = Str::slug($vendor->name);
            }
        });
    }
}
