<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInventory extends Model
{
    use HasFactory;

    protected $table = 'product_inventory';

    protected $fillable = [
        'product_id',
        'inventory_mode',
        'stock_quantity',
        'unit',
        'low_stock_threshold',
        'sync_status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }
}
