<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerPreOrder extends Model
{
    protected $fillable = [
        'pre_order_number',
        'pre_order_id',
        'customer_email',
        'customer_phone',
        'first_name',
        'last_name',
        'quantity',
        'unit_price',
        'deposit_amount',
        'remaining_amount',
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
        'deposit_paid_at',
        'fully_paid_at',
        'ready_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paystack_response' => 'array',
        'deposit_paid_at' => 'datetime',
        'fully_paid_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Generate unique pre-order number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($customerPreOrder) {
            if (empty($customerPreOrder->pre_order_number)) {
                $customerPreOrder->pre_order_number = 'PRE-' . strtoupper(Str::random(8));
            }
            
            // Set default currency if not provided
            if (empty($customerPreOrder->currency)) {
                $customerPreOrder->currency = config('app.default_currency', 'NGN');
            }
        });
    }

    /**
     * Relationship: Customer pre-order belongs to a pre-order
     */
    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }

    /**
     * Get customer full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if deposit is paid
     */
    public function isDepositPaid(): bool
    {
        return in_array($this->payment_status, ['deposit_paid', 'fully_paid']);
    }

    /**
     * Check if fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'fully_paid';
    }

    /**
     * Check if can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'deposit_paid']);
    }

    /**
     * Mark deposit as paid
     */
    public function markDepositAsPaid(): void
    {
        $this->update([
            'payment_status' => 'deposit_paid',
            'status' => 'deposit_paid',
            'deposit_paid_at' => now(),
        ]);
    }

    /**
     * Mark as fully paid
     */
    public function markAsFullyPaid(): void
    {
        $this->update([
            'payment_status' => 'fully_paid',
            'status' => 'fully_paid',
            'fully_paid_at' => now(),
        ]);
    }

    /**
     * Mark as ready for pickup/delivery
     */
    public function markAsReady(): void
    {
        $this->update([
            'status' => 'ready_for_pickup',
            'ready_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
