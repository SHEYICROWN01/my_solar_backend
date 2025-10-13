<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'promo_code',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'usage_limit',
        'minimum_order_amount',
        'description',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the promotion is currently valid
     */
    public function isValid(): bool
    {
        $now = Carbon::now()->toDateString();
        
        return $this->is_active &&
               $this->start_date <= $now &&
               $this->end_date >= $now &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Check if the promotion can be applied to a given order amount
     */
    public function canApplyToAmount(float $amount): bool
    {
        return $this->minimum_order_amount === null || $amount >= $this->minimum_order_amount;
    }

    /**
     * Calculate discount amount for a given order total
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->canApplyToAmount($orderTotal)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return ($orderTotal * $this->discount_value) / 100;
        }

        return min($this->discount_value, $orderTotal);
    }

    /**
     * Increment the usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid promotions (active and within date range)
     */
    public function scopeValid($query)
    {
        $now = Carbon::now()->toDateString();
        
        return $query->active()
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now)
                    ->where(function($q) {
                        $q->whereNull('usage_limit')
                          ->orWhereRaw('used_count < usage_limit');
                    });
    }
}
