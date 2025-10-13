<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderStatusUpdated;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCompleted;
use App\Services\AdminNotificationService;

class OrderController extends Controller
{
    protected $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Initialize payment with Paystack
     */
    public function initializePayment(Request $request)
    {
        $request->validate([
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'fulfillment_method' => 'required|in:delivery,pickup',
            'shipping_address' => 'nullable|string|required_if:fulfillment_method,delivery',
            'city' => 'nullable|string|required_if:fulfillment_method,delivery',
            'state' => 'nullable|string|required_if:fulfillment_method,delivery',
            'pickup_location' => 'nullable|string|required_if:fulfillment_method,pickup',
            'payment_method' => 'required|string',
            'promo_code' => 'nullable|string',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Calculate order totals
            $subtotal = 0;
            $cartItems = [];

            foreach ($request->cart_items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'error' => "Insufficient stock for {$product->name}. Available: {$product->stock}"
                    ], 400);
                }

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal
                ];
            }

            // Apply discount if promo code is provided
            $discountAmount = 0;
            if ($request->promo_code) {
                // You can implement promo code validation here
                // For now, we'll skip it but keep the structure
            }

            // Calculate shipping (free for now)
            $shippingFee = 0;
            $totalAmount = $subtotal + $shippingFee - $discountAmount;

            // Create order
            $order = Order::create([
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'fulfillment_method' => $request->fulfillment_method,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'state' => $request->state,
                'pickup_location' => $request->pickup_location,
                'payment_method' => $request->payment_method,
                'promo_code' => $request->promo_code,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'product_snapshot' => $item['product']->toArray(),
                ]);
            }

            // Load order items for notification
            $order->load('orderItems');

            // Create admin notification for new order
            $this->notificationService->notifyNewOrder($order);

            // Initialize Paystack payment
            $paystackData = [
                'email' => $request->customer_email,
                'amount' => $totalAmount * 100, // Paystack expects amount in kobo
                'currency' => 'NGN',
                'reference' => $order->order_number . '_' . time(),
                'callback_url' => config('app.frontend_url') . '/payment/callback',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $request->first_name . ' ' . $request->last_name,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $paystackData);

            if (!$response->successful()) {
                throw new \Exception('Failed to initialize Paystack payment');
            }

            $paystackResponse = $response->json();

            // Update order with Paystack details
            $order->update([
                'paystack_reference' => $paystackData['reference'],
                'paystack_access_code' => $paystackResponse['data']['access_code'],
                'paystack_response' => $paystackResponse,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order->load('orderItems.product'),
                    'paystack' => $paystackResponse['data'],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initialization failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to initialize payment. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment with Paystack
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            // Find order by Paystack reference
            $order = Order::where('paystack_reference', $request->reference)->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Verify payment with Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
            ])->get("https://api.paystack.co/transaction/verify/{$request->reference}");

            if (!$response->successful()) {
                return response()->json(['error' => 'Payment verification failed'], 400);
            }

            $paystackData = $response->json();

            if ($paystackData['data']['status'] === 'success') {
                // Update order status
                $order->markAsPaid();
                
                // Create admin notification for payment completion
                $this->notificationService->notifyOrderPaymentCompleted($order);
                
                // Update stock quantities
                foreach ($order->orderItems as $item) {
                    $product = $item->product;
                    $product->decrement('stock', $item->quantity);
                }

                // Send order completion email to customer
                try {
                    $order->load('orderItems.product'); // Load items for email template
                    Mail::to($order->customer_email)->send(new OrderCompleted($order));
                    Log::info("Order completion email sent to {$order->customer_email} for order {$order->order_number}");
                } catch (\Exception $e) {
                    Log::error("Failed to send order completion email for order {$order->order_number}: " . $e->getMessage());
                    // Don't fail the payment verification if email fails
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'order' => $order->load('orderItems.product'),
                        'payment_data' => $paystackData['data']
                    ]
                ]);
            } else {
                // Update order as failed
                $order->update(['payment_status' => 'failed']);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'data' => $paystackData
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Payment verification failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(Request $request)
    {
        // Verify the webhook signature
        $signature = $request->header('x-paystack-signature');
        $computedSignature = hash_hmac('sha512', $request->getContent(), config('services.paystack.secret_key'));

        if (!hash_equals($signature, $computedSignature)) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->all();

        try {
            switch ($event['event']) {
                case 'charge.success':
                    $this->handleSuccessfulPayment($event['data']);
                    break;
                    
                case 'charge.failed':
                    $this->handleFailedPayment($event['data']);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event: ' . $event['event']);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get all orders for admin (simplified for main orders page)
     */
    public function index(Request $request)
    {
        $query = Order::with('orderItems.product')
            ->orderBy('created_at', 'desc');

        // Search by order number or customer name/email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment status
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

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        // Transform orders to match your specifications
        $transformedOrders = $orders->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->first_name . ' ' . $order->last_name,
                'formatted_date' => $order->created_at->format('M d, Y'),
                'formatted_total' => '₦' . number_format($order->total_amount, 2),
                'status' => $order->status,
                'items_count' => $order->orderItems->sum('quantity')
            ];
        });

        return response()->json([
            'orders' => $transformedOrders
        ]);
    }

    /**
     * Get order details by ID (for admin)
     */
    public function show(Order $order)
    {
        $order->load('orderItems.product');

        // Format the order data to match your specifications
        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->first_name . ' ' . $order->last_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'formatted_date' => $order->created_at->format('M d, Y'),
            'formatted_total' => '₦' . number_format($order->total_amount, 2),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_method' => $order->fulfillment_method,
            'shipping_address' => $order->shipping_address ? 
                $order->shipping_address . ', ' . $order->city . ', ' . $order->state : 
                $order->pickup_location,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'product_price' => $item->product_price,
                    'total_price' => $item->total_price,
                    'product' => $item->product ? [
                        'images' => $item->product->images ?? []
                    ] : null
                ];
            })
        ];

        return response()->json([
            'order' => $formattedOrder
        ]);
    }

    /**
     * Update order status (enhanced with proper response format)
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,processing,shipped,delivered,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Validation rules for status transitions
        if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
            return response()->json([
                'error' => "Cannot change status from '{$oldStatus}' to '{$newStatus}'"
            ], 400);
        }

        // Update the status
        $order->update(['status' => $newStatus]);

        // Create admin notification for status change
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus, $newStatus);

        // If marking as delivered, update delivered timestamp
        if ($newStatus === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

        // Send email notification to customer
        try {
            Mail::to($order->customer_email)->send(new OrderStatusUpdated($order, $oldStatus, $newStatus));
            Log::info("Order status update email sent to {$order->customer_email} for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order status update email for order {$order->order_number}: " . $e->getMessage());
            // Don't fail the status update if email fails
        }

        // Log the status change
        Log::info("Order {$order->order_number} status changed from {$oldStatus} to {$newStatus}");

        // Reload the order with items for response
        $order->load('orderItems.product');

        // Format the updated order data
        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->first_name . ' ' . $order->last_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'formatted_date' => $order->created_at->format('M d, Y'),
            'formatted_total' => '₦' . number_format($order->total_amount, 2),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_method' => $order->fulfillment_method,
            'shipping_address' => $order->shipping_address ? 
                $order->shipping_address . ', ' . $order->city . ', ' . $order->state : 
                $order->pickup_location,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'product_price' => $item->product_price,
                    'total_price' => $item->total_price,
                    'product' => $item->product ? [
                        'images' => $item->product->images ?? []
                    ] : null
                ];
            })
        ];

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $formattedOrder
        ]);
    }

    /**
     * Get order by order number (for customer-facing order confirmation)
     */
    public function getByOrderNumber($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('orderItems.product')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // Format the order data same as show method
        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->first_name . ' ' . $order->last_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'formatted_date' => $order->created_at->format('M d, Y'),
            'formatted_total' => '₦' . number_format($order->total_amount, 2),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_method' => $order->fulfillment_method,
            'shipping_address' => $order->shipping_address ? 
                $order->shipping_address . ', ' . $order->city . ', ' . $order->state : 
                $order->pickup_location,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'product_price' => $item->product_price,
                    'total_price' => $item->total_price,
                    'product' => $item->product ? [
                        'images' => $item->product->images ?? []
                    ] : null
                ];
            })
        ];

        return response()->json([
            'order' => $formattedOrder
        ]);
    }

    /**
     * Handle successful payment from webhook
     */
    private function handleSuccessfulPayment($paymentData)
    {
        $order = Order::where('paystack_reference', $paymentData['reference'])->first();
        
        if ($order && !$order->isPaid()) {
            $order->markAsPaid();
            
            // Create admin notification for payment completion
            $this->notificationService->notifyOrderPaymentCompleted($order);
            
            // Update stock quantities
            foreach ($order->orderItems as $item) {
                $item->product->decrement('stock', $item->quantity);
            }
            
            // Send order completion email to customer
            try {
                $order->load('orderItems.product'); // Load items for email template
                Mail::to($order->customer_email)->send(new OrderCompleted($order));
                Log::info("Order completion email sent to {$order->customer_email} for order {$order->order_number} via webhook");
            } catch (\Exception $e) {
                Log::error("Failed to send order completion email for order {$order->order_number} via webhook: " . $e->getMessage());
            }
            
            Log::info("Order {$order->order_number} marked as paid via webhook");
        }
    }

    /**
     * Handle failed payment from webhook
     */
    private function handleFailedPayment($paymentData)
    {
        $order = Order::where('paystack_reference', $paymentData['reference'])->first();
        
        if ($order) {
            $order->update(['payment_status' => 'failed']);
            Log::info("Order {$order->order_number} marked as failed via webhook");
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order)
    {
        if (!$order->canBeCancelled()) {
            return response()->json([
                'error' => 'This order cannot be cancelled'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        // If order was paid, you might want to process refund here
        if ($order->isPaid()) {
            // TODO: Implement refund logic with Paystack
            Log::info("Order {$order->order_number} was cancelled but payment needs to be refunded");
        }

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order->load('orderItems.product')
        ]);
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition(string $oldStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['paid', 'cancelled'],
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [], // Final state
            'cancelled' => [], // Final state
        ];

        return in_array($newStatus, $validTransitions[$oldStatus] ?? []);
    }

    /**
     * Initialize payment session without creating order record
     */
    public function initializePaymentSession(Request $request)
    {
        $request->validate([
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'fulfillment_method' => 'required|in:delivery,pickup',
            'shipping_address' => 'nullable|string|required_if:fulfillment_method,delivery',
            'city' => 'nullable|string|required_if:fulfillment_method,delivery',
            'state' => 'nullable|string|required_if:fulfillment_method,delivery',
            'pickup_location' => 'nullable|string|required_if:fulfillment_method,pickup',
            'payment_method' => 'required|string',
            'promo_code' => 'nullable|string',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'callback_url' => 'nullable|url'
        ]);

        try {
            // Calculate order totals (same logic as before)
            $subtotal = 0;
            $cartItems = [];

            foreach ($request->cart_items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'error' => "Insufficient stock for {$product->name}. Available: {$product->stock}"
                    ], 400);
                }

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'product_snapshot' => $product->toArray(),
                ];
            }

            // Apply discount if promo code is provided
            $discountAmount = 0;
            if ($request->promo_code) {
                // You can implement promo code validation here
                // For now, we'll skip it but keep the structure
            }

            // Calculate shipping (free for now)
            $shippingFee = 0;
            $totalAmount = $subtotal + $shippingFee - $discountAmount;

            // Generate unique reference for payment session
            $reference = 'order_session_' . time() . '_' . rand(1000, 9999);

            // Create payment session data to store in metadata
            $sessionData = [
                'type' => 'order_session',
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'fulfillment_method' => $request->fulfillment_method,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'state' => $request->state,
                'pickup_location' => $request->pickup_location,
                'payment_method' => $request->payment_method,
                'promo_code' => $request->promo_code,
                'cart_items' => $cartItems,
            ];

            // Initialize Paystack payment
            $paystackData = [
                'email' => $request->customer_email,
                'amount' => $totalAmount * 100, // Paystack expects amount in kobo
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => $request->callback_url ?? config('app.frontend_url') . '/payment/callback',
                'metadata' => $sessionData
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $paystackData);

            if (!$response->successful()) {
                throw new \Exception('Failed to initialize Paystack payment');
            }

            $paystackResponse = $response->json();

            return response()->json([
                'success' => true,
                'data' => [
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                    'reference' => $reference,
                    'amount' => $totalAmount,
                    'customer_name' => $request->first_name . ' ' . $request->last_name,
                    'items_count' => array_sum(array_column($cartItems, 'quantity'))
                ],
                'message' => 'Payment session initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Order payment session initialization failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to initialize payment session. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment and create order record only after successful payment
     */
    public function verifyPaymentAndCreateOrder(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            // Verify payment with Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
            ])->get("https://api.paystack.co/transaction/verify/{$request->reference}");

            if (!$response->successful()) {
                return response()->json(['error' => 'Payment verification failed'], 400);
            }

            $paystackData = $response->json();

            if ($paystackData['data']['status'] !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful',
                    'data' => $paystackData
                ], 400);
            }

            // Extract session data from payment metadata
            $metadata = $paystackData['data']['metadata'];
            
            if (!isset($metadata['type']) || $metadata['type'] !== 'order_session') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment session data'
                ], 400);
            }

            // Check if order already exists for this reference (prevent duplicate creation)
            $existingOrder = Order::where('paystack_reference', $request->reference)->first();
            if ($existingOrder) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order' => $existingOrder->load('orderItems.product'),
                        'payment_data' => $paystackData['data']
                    ],
                    'message' => 'Order already exists for this payment'
                ]);
            }

            DB::beginTransaction();

            // Now create the order record
            $order = Order::create([
                'customer_email' => $metadata['customer_email'],
                'customer_phone' => $metadata['customer_phone'],
                'first_name' => $metadata['first_name'],
                'last_name' => $metadata['last_name'],
                'subtotal' => $metadata['subtotal'],
                'shipping_fee' => $metadata['shipping_fee'],
                'discount_amount' => $metadata['discount_amount'],
                'total_amount' => $metadata['total_amount'],
                'fulfillment_method' => $metadata['fulfillment_method'],
                'shipping_address' => $metadata['shipping_address'],
                'city' => $metadata['city'],
                'state' => $metadata['state'],
                'pickup_location' => $metadata['pickup_location'],
                'payment_method' => $metadata['payment_method'],
                'promo_code' => $metadata['promo_code'],
                'paystack_reference' => $request->reference,
                'paystack_response' => $paystackData,
                'payment_status' => 'paid', // Mark as paid since verification was successful
                'status' => 'paid'
            ]);

            // Create order items
            foreach ($metadata['cart_items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_price' => $item['product_price'],
                    'quantity' => $item['quantity'],
                    'product_snapshot' => $item['product_snapshot'],
                ]);
            }

            // Load order items for notifications and response
            $order->load('orderItems');

            // Update stock quantities
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                if ($product) {
                    $product->decrement('stock', $item->quantity);
                }
            }

            // Create admin notifications
            $this->notificationService->notifyNewOrder($order);
            $this->notificationService->notifyOrderPaymentCompleted($order);

            DB::commit();

            // Send order completion email to customer
            try {
                $order->load('orderItems.product'); // Load items for email template
                Mail::to($order->customer_email)->send(new OrderCompleted($order));
                Log::info("Order completion email sent to {$order->customer_email} for order {$order->order_number}");
            } catch (\Exception $e) {
                Log::error("Failed to send order completion email for order {$order->order_number}: " . $e->getMessage());
                // Don't fail the order creation if email fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and order created successfully',
                'data' => [
                    'order' => $order->load('orderItems.product'),
                    'payment_data' => $paystackData['data']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order verification and creation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to process payment verification',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
