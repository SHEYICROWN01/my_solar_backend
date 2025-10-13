<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_email',
        'customer_phone',
        'first_name',
        'last_name',
        'subtotal',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'fulfillment_method',
        'shipping_address',
        'city',
        'state',
        'pickup_location',
        'payment_method',
        'paystack_reference',
        'paystack_access_code',
        'paystack_response',
        'paid_at',
        'delivered_at',
        'promo_code',
    ];

    protected $casts = [
        'paystack_response' => 'array',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Generate unique order number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Relationship: Order has many order items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'paid']);
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Calculate total from order items
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->orderItems->sum('total_price');
        return $subtotal + $this->shipping_fee - $this->discount_amount;
    }
}
