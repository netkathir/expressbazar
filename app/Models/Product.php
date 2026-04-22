<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_name',
        'description',
        'category_id',
        'subcategory_id',
        'vendor_id',
        'tax_id',
        'price',
        'discount_type',
        'discount_value',
        'final_price',
        'discount_start_date',
        'discount_end_date',
        'inventory_mode',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_start_date' => 'date',
        'discount_end_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function inventory()
    {
        return $this->hasOne(ProductInventory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
