<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'subtotal',
        'discount_total',
        'delivery_fee',
        'grand_total',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'delivery_fee' => 'integer',
            'grand_total' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
