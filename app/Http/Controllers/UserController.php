<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Get user stats
        $totalOrders = Order::where('customer_email', $user->email)->count();
        $totalSpent = Order::where('customer_email', $user->email)
            ->where('payment_status', 'paid')
            ->sum('total_amount');
        
        $lastOrder = Order::where('customer_email', $user->email)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'stats' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'formatted_total_spent' => '₦' . number_format($totalSpent, 2),
                'last_order_date' => $lastOrder ? $lastOrder->created_at->format('M d, Y') : null,
                'account_age_days' => $user->created_at->diffInDays(now()),
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // If changing password, verify current password
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password')) {
                return response()->json([
                    'error' => 'Current password is required to set a new password'
                ], 400);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'error' => 'Current password is incorrect'
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
        }

        // Update profile fields
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Get user's recent orders (last 10)
     */
    public function getRecentOrders(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $recentOrders = Order::where('customer_email', $user->email)
            ->with('orderItems.product')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $formattedOrders = $recentOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'formatted_total' => '₦' . number_format($order->total_amount, 2),
                'items_count' => $order->orderItems->sum('quantity'),
                'date' => $order->created_at->format('M d, Y'),
                'relative_date' => $order->created_at->diffForHumans(),
                'fulfillment_method' => $order->fulfillment_method,
                'items_preview' => $order->orderItems->take(3)->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                    ];
                }),
                'can_track' => in_array($order->status, ['paid', 'processing', 'shipped']),
            ];
        });

        return response()->json([
            'recent_orders' => $formattedOrders
        ]);
    }

    /**
     * Get user's complete order history with pagination
     */
    public function getOrderHistory(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $query = Order::where('customer_email', $user->email)
            ->with('orderItems.product')
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment status if provided
        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number
        if ($request->has('search') && !empty($request->search)) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        $formattedOrders = $orders->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'formatted_total' => '₦' . number_format($order->total_amount, 2),
                'items_count' => $order->orderItems->sum('quantity'),
                'date' => $order->created_at->format('M d, Y'),
                'formatted_date' => $order->created_at->format('F j, Y \a\t g:i A'),
                'relative_date' => $order->created_at->diffForHumans(),
                'fulfillment_method' => $order->fulfillment_method,
                'shipping_address' => $order->shipping_address,
                'city' => $order->city,
                'state' => $order->state,
                'pickup_location' => $order->pickup_location,
                'order_items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'product_price' => $item->product_price,
                        'total_price' => $item->total_price,
                        'formatted_price' => '₦' . number_format($item->product_price, 2),
                        'formatted_total' => '₦' . number_format($item->total_price, 2),
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'images' => $item->product->images ?? [],
                        ] : null,
                    ];
                }),
                'can_track' => in_array($order->status, ['paid', 'processing', 'shipped']),
                'can_cancel' => $order->canBeCancelled(),
                'is_delivered' => $order->status === 'delivered',
                'delivered_at' => $order->delivered_at ? $order->delivered_at->format('M d, Y') : null,
            ];
        });

        return response()->json([
            'orders' => $formattedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
            'summary' => [
                'total_orders' => Order::where('customer_email', $user->email)->count(),
                'pending_orders' => Order::where('customer_email', $user->email)->where('status', 'pending')->count(),
                'completed_orders' => Order::where('customer_email', $user->email)->where('status', 'delivered')->count(),
                'total_spent' => Order::where('customer_email', $user->email)->where('payment_status', 'paid')->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Get specific order details for user
     */
    public function getOrderDetails(Request $request, $orderNumber)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('customer_email', $user->email)
            ->with('orderItems.product')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_amount,
            'formatted_total' => '₦' . number_format($order->total_amount, 2),
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'discount_amount' => $order->discount_amount,
            'date' => $order->created_at->format('M d, Y'),
            'formatted_date' => $order->created_at->format('F j, Y \a\t g:i A'),
            'fulfillment_method' => $order->fulfillment_method,
            'shipping_address' => $order->shipping_address,
            'city' => $order->city,
            'state' => $order->state,
            'pickup_location' => $order->pickup_location,
            'payment_method' => $order->payment_method,
            'promo_code' => $order->promo_code,
            'paid_at' => $order->paid_at ? $order->paid_at->format('M d, Y g:i A') : null,
            'delivered_at' => $order->delivered_at ? $order->delivered_at->format('M d, Y g:i A') : null,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'product_price' => $item->product_price,
                    'total_price' => $item->total_price,
                    'formatted_price' => '₦' . number_format($item->product_price, 2),
                    'formatted_total' => '₦' . number_format($item->total_price, 2),
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'images' => $item->product->images ?? [],
                        'power' => $item->product->power ?? null,
                        'warranty' => $item->product->warranty ?? null,
                    ] : null,
                ];
            }),
            'can_track' => in_array($order->status, ['paid', 'processing', 'shipped']),
            'can_cancel' => $order->canBeCancelled(),
            'tracking_info' => [
                'current_status' => $order->status,
                'status_message' => $this->getStatusMessage($order->status),
                'progress_percentage' => $this->getStatusProgress($order->status),
            ]
        ];

        return response()->json([
            'order' => $formattedOrder
        ]);
    }

    /**
     * Get status message for order tracking
     */
    private function getStatusMessage($status)
    {
        $messages = [
            'pending' => 'Your order is awaiting payment confirmation.',
            'paid' => 'Payment confirmed! We\'re preparing your order.',
            'processing' => 'Your order is being prepared for shipment.',
            'shipped' => 'Your order is on its way to you!',
            'delivered' => 'Your order has been delivered successfully.',
            'cancelled' => 'This order has been cancelled.',
        ];

        return $messages[$status] ?? 'Unknown status';
    }

    /**
     * Get status progress percentage
     */
    private function getStatusProgress($status)
    {
        $progress = [
            'pending' => 10,
            'paid' => 25,
            'processing' => 50,
            'shipped' => 75,
            'delivered' => 100,
            'cancelled' => 0,
        ];

        return $progress[$status] ?? 0;
    }
}
