<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PreOrder extends Model
{
    protected $fillable = [
        'product_name',
        'category_id',
        'pre_order_price',
        'deposit_percentage',
        'expected_availability',
        'power_output',
        'warranty_period',
        'specifications',
        'images',
    ];

    protected $casts = [
        'pre_order_price' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'images' => 'array',
    ];

    protected $appends = ['image_urls', 'deposit_amount'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: Pre-order has many customer pre-orders
     */
    public function customerPreOrders(): HasMany
    {
        return $this->hasMany(CustomerPreOrder::class);
    }

    /**
     * Get the full URLs for pre-order product images
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $this->images);
    }

    /**
     * Calculate the deposit amount based on percentage
     */
    public function getDepositAmountAttribute(): float
    {
        return ($this->pre_order_price * $this->deposit_percentage) / 100;
    }
}
