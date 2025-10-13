<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Filter by status if provided
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'valid') {
                $query->valid();
            }
        }

        // Search by name or promo code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('promo_code', 'like', "%{$search}%");
            });
        }

        $promotions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'promotions' => $promotions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'promo_code' => 'required|string|max:50|unique:promotions,promo_code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Additional validation for percentage discount
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100%',
                'errors' => ['discount_value' => ['Percentage discount cannot exceed 100%']]
            ], 422);
        }

        $promotion = Promotion::create($request->all());

        return response()->json([
            'message' => 'Promotion created successfully',
            'promotion' => $promotion,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Promotion $promotion)
    {
        return response()->json([
            'promotion' => $promotion,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'promo_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('promotions', 'promo_code')->ignore($promotion->id),
            ],
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Additional validation for percentage discount
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100%',
                'errors' => ['discount_value' => ['Percentage discount cannot exceed 100%']]
            ], 422);
        }

        $promotion->update($request->all());

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return response()->json([
            'message' => 'Promotion deleted successfully',
        ]);
    }

    /**
     * Validate a promo code for checkout
     */
    public function validatePromoCode(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $promotion = Promotion::where('promo_code', $request->promo_code)->first();

        if (!$promotion) {
            return response()->json([
                'message' => 'Invalid promo code',
                'valid' => false,
            ], 404);
        }

        if (!$promotion->isValid()) {
            return response()->json([
                'message' => 'Promo code is expired or has reached usage limit',
                'valid' => false,
            ], 400);
        }

        if (!$promotion->canApplyToAmount($request->order_amount)) {
            return response()->json([
                'message' => "Minimum order amount of ${$promotion->minimum_order_amount} required",
                'valid' => false,
            ], 400);
        }

        $discountAmount = $promotion->calculateDiscount($request->order_amount);
        $finalAmount = $request->order_amount - $discountAmount;

        return response()->json([
            'message' => 'Promo code is valid',
            'valid' => true,
            'promotion' => $promotion,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ]);
    }

    /**
     * Apply a promo code (increment usage count)
     */
    public function applyPromoCode(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $promotion = Promotion::where('promo_code', $request->promo_code)->first();

        if (!$promotion || !$promotion->isValid() || !$promotion->canApplyToAmount($request->order_amount)) {
            return response()->json([
                'message' => 'Invalid or expired promo code',
            ], 400);
        }

        $promotion->incrementUsage();

        $discountAmount = $promotion->calculateDiscount($request->order_amount);
        $finalAmount = $request->order_amount - $discountAmount;

        return response()->json([
            'message' => 'Promo code applied successfully',
            'promotion' => $promotion->fresh(),
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ]);
    }

    /**
     * Get promotion statistics
     */
    public function statistics()
    {
        $totalPromotions = Promotion::count();
        $activePromotions = Promotion::active()->count();
        $validPromotions = Promotion::valid()->count();
        $expiredPromotions = Promotion::where('end_date', '<', Carbon::now()->toDateString())->count();
        $totalUsage = Promotion::sum('used_count');

        return response()->json([
            'statistics' => [
                'total_promotions' => $totalPromotions,
                'active_promotions' => $activePromotions,
                'valid_promotions' => $validPromotions,
                'expired_promotions' => $expiredPromotions,
                'total_usage' => $totalUsage,
            ],
        ]);
    }

    /**
     * Toggle promotion status
     */
    public function toggleStatus(Promotion $promotion)
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        return response()->json([
            'message' => 'Promotion status updated successfully',
            'promotion' => $promotion->fresh(),
        ]);
    }
}
