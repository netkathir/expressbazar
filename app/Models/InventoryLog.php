<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_inventory_id',
        'vendor_id',
        'change_type',
        'quantity',
        'previous_stock',
        'new_stock',
        'source',
        'reason',
    ];
}
