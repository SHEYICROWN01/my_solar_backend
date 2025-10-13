<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_price',
        'quantity',
        'total_price',
        'product_snapshot',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'product_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relationship: OrderItem belongs to Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: OrderItem belongs to Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total price automatically
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($orderItem) {
            $orderItem->total_price = $orderItem->quantity * $orderItem->product_price;
        });
    }
}
