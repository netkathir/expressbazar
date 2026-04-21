<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'subcategory_id',
        'name',
        'slug',
        'description',
        'sku',
        'price',
        'mrp',
        'stock_quantity',
        'rating',
        'unit',
        'deal_text',
        'accent_color',
        'background_color',
        'image_url',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'mrp' => 'integer',
            'stock_quantity' => 'integer',
            'rating' => 'float',
            'is_active' => 'bool',
            'last_synced_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (! $product->slug && $product->name) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
