<?php

namespace App\Http\Controllers;

use App\Models\PreOrder;
use App\Models\CustomerPreOrder;
use App\Mail\PreOrderConfirmation;
use App\Mail\PreOrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\AdminNotificationService;

class CustomerPreOrderController extends Controller
{
    protected $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all available pre-orders for customers
     */
    public function index(Request $request): JsonResponse
    {
        $query = PreOrder::with(['category', 'customerPreOrders']);

        // Add search functionality
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where('product_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('specifications', 'LIKE', "%{$searchTerm}%");
        }

        // Add category filtering
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Add sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 12);
        $preOrders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $preOrders,
            'message' => 'Available pre-orders retrieved successfully'
        ]);
    }

    /**
     * Get specific pre-order details for customers
     */
    public function show(PreOrder $preOrder): JsonResponse
    {
        $preOrder->load(['category', 'customerPreOrders']);

        return response()->json([
            'success' => true,
            'data' => $preOrder,
            'message' => 'Pre-order details retrieved successfully'
        ]);
    }

    /**
     * Place a new pre-order (customer creates pre-order)
     */
    public function placePreOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pre_order_id' => 'required|exists:pre_orders,id',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'fulfillment_method' => 'required|in:pickup,delivery',
            'shipping_address' => 'required_if:fulfillment_method,delivery|string',
            'city' => 'required_if:fulfillment_method,delivery|string',
            'state' => 'required_if:fulfillment_method,delivery|string',
            'pickup_location' => 'required_if:fulfillment_method,pickup|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $preOrder = PreOrder::findOrFail($request->pre_order_id);
        $data = $validator->validated();

        // Calculate amounts
        $unitPrice = $preOrder->pre_order_price;
        $totalAmount = $unitPrice * $data['quantity'];
        $depositAmount = ($totalAmount * $preOrder->deposit_percentage) / 100;
        $remainingAmount = $totalAmount - $depositAmount;

        // Log the calculated amounts for debugging
        \Log::info('Pre-order amount calculations', [
            'unit_price' => $unitPrice,
            'quantity' => $data['quantity'],
            'deposit_percentage' => $preOrder->deposit_percentage,
            'calculated_total' => $totalAmount,
            'calculated_deposit' => $depositAmount,
            'calculated_remaining' => $remainingAmount
        ]);

        $customerPreOrder = CustomerPreOrder::create([
            'pre_order_id' => $preOrder->id,
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'quantity' => $data['quantity'],
            'unit_price' => $unitPrice,
            'deposit_amount' => $depositAmount,
            'remaining_amount' => $remainingAmount,
            'total_amount' => $totalAmount,
            'fulfillment_method' => $data['fulfillment_method'],
            'shipping_address' => $data['shipping_address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $customerPreOrder->load('preOrder.category');

        // Create admin notification for new pre-order
        $this->notificationService->notifyNewPreOrder($customerPreOrder);

        // Send confirmation email
        try {
            Mail::to($customerPreOrder->customer_email)->send(new PreOrderConfirmation($customerPreOrder));
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send pre-order confirmation email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $customerPreOrder,
            'message' => 'Pre-order placed successfully! Confirmation email sent.'
        ], 201);
    }

    /**
     * Initialize payment for deposit or full payment
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_pre_order_id' => 'required|exists:customer_pre_orders,id',
            'payment_type' => 'required|in:deposit,full',
            'callback_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customerPreOrder = CustomerPreOrder::with('preOrder')->findOrFail($request->customer_pre_order_id);
            $paymentType = $request->payment_type;

            // Prevent overpayment
            if ($customerPreOrder->isFullyPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pre-order is already fully paid'
                ], 400);
            }

            // Determine payment amount based on payment type and current status
            $amount = 0;
            
            if ($paymentType === 'deposit') {
                if ($customerPreOrder->isDepositPaid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Deposit has already been paid'
                    ], 400);
                }
                
                $amount = (float) $customerPreOrder->deposit_amount;
                
                if ($amount <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Deposit unavailable from backend. Switching to full payment.'
                    ], 400);
                }
            } else {
                // For 'full' payment type
                if ($customerPreOrder->isDepositPaid()) {
                    // This is a remaining balance payment
                    $amount = (float) $customerPreOrder->remaining_amount;
                } else {
                    // This is a full payment (no deposit paid yet)
                    $amount = (float) $customerPreOrder->total_amount;
                }
                
                if ($amount <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Amount Sent'
                    ], 400);
                }
            }

            // Generate unique reference
            $reference = 'pre_' . $customerPreOrder->pre_order_number . '_' . time();

            // Initialize Paystack payment - using the same approach as working product orders
            $paystackData = [
                'email' => $customerPreOrder->customer_email,
                'amount' => $amount * 100, // Paystack expects amount in kobo
                'currency' => $customerPreOrder->currency ?: 'NGN',
                'reference' => $reference,
                'callback_url' => $request->callback_url ?? config('app.frontend_url') . '/payment/callback',
                'metadata' => [
                    'customer_pre_order_id' => $customerPreOrder->id,
                    'payment_type' => $paymentType,
                    'customer_name' => $customerPreOrder->full_name,
                    'product_name' => $customerPreOrder->preOrder->product_name,
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

            // Update pre-order with payment details
            $customerPreOrder->update([
                'paystack_reference' => $reference,
                'paystack_access_code' => $paystackResponse['data']['access_code'],
                'paystack_response' => $paystackResponse,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                    'reference' => $reference,
                    'amount' => $amount,
                    'payment_type' => $paymentType
                ],
                'message' => 'Payment initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Pre-order payment initialization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize payment. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize payment session without creating database record
     */
    public function initializePaymentSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pre_order_id' => 'required|exists:pre_orders,id',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'fulfillment_method' => 'required|in:pickup,delivery',
            'shipping_address' => 'required_if:fulfillment_method,delivery|string',
            'city' => 'required_if:fulfillment_method,delivery|string',
            'state' => 'required_if:fulfillment_method,delivery|string',
            'pickup_location' => 'required_if:fulfillment_method,pickup|string',
            'notes' => 'nullable|string',
            'payment_type' => 'required|in:deposit,full',
            'callback_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preOrder = PreOrder::findOrFail($request->pre_order_id);
            $data = $validator->validated();

            // Calculate amounts (same logic as before)
            $unitPrice = $preOrder->pre_order_price;
            $totalAmount = $unitPrice * $data['quantity'];
            $depositAmount = ($totalAmount * $preOrder->deposit_percentage) / 100;
            $remainingAmount = $totalAmount - $depositAmount;

            // Determine payment amount based on payment type
            $paymentAmount = $data['payment_type'] === 'deposit' ? $depositAmount : $totalAmount;

            if ($paymentAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment amount calculated'
                ], 400);
            }

            // Generate unique reference for payment session
            $reference = 'pre_session_' . time() . '_' . rand(1000, 9999);

            // Create payment session data to store in metadata
            $sessionData = [
                'type' => 'pre_order_session',
                'pre_order_id' => $preOrder->id,
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'quantity' => $data['quantity'],
                'unit_price' => $unitPrice,
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $remainingAmount,
                'total_amount' => $totalAmount,
                'fulfillment_method' => $data['fulfillment_method'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pickup_location' => $data['pickup_location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'payment_type' => $data['payment_type'],
                'payment_amount' => $paymentAmount,
                'product_name' => $preOrder->product_name,
            ];

            // Initialize Paystack payment
            $paystackData = [
                'email' => $data['customer_email'],
                'amount' => $paymentAmount * 100, // Paystack expects amount in kobo
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
                    'amount' => $paymentAmount,
                    'payment_type' => $data['payment_type'],
                    'product_name' => $preOrder->product_name,
                    'customer_name' => $data['first_name'] . ' ' . $data['last_name']
                ],
                'message' => 'Payment session initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Pre-order payment session initialization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize payment session. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment and create pre-order record only after successful payment
     */
    public function verifyPaymentAndCreatePreOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reference = $request->reference;

        try {
            // Verify payment with Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret'),
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'error' => $response->json()
                ], 400);
            }

            $data = $response->json();
            
            if ($data['data']['status'] !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful',
                    'data' => $data
                ], 400);
            }

            // Extract session data from payment metadata
            $metadata = $data['data']['metadata'];
            
            if (!isset($metadata['type']) || $metadata['type'] !== 'pre_order_session') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment session data'
                ], 400);
            }

            // Check if pre-order already exists for this reference (prevent duplicate creation)
            $existingPreOrder = CustomerPreOrder::where('paystack_reference', $reference)->first();
            if ($existingPreOrder) {
                return response()->json([
                    'success' => true,
                    'data' => $existingPreOrder->load('preOrder.category'),
                    'message' => 'Pre-order already exists for this payment'
                ]);
            }

            DB::beginTransaction();

            // Now create the customer pre-order record
            $customerPreOrder = CustomerPreOrder::create([
                'pre_order_id' => $metadata['pre_order_id'],
                'customer_email' => $metadata['customer_email'],
                'customer_phone' => $metadata['customer_phone'],
                'first_name' => $metadata['first_name'],
                'last_name' => $metadata['last_name'],
                'quantity' => $metadata['quantity'],
                'unit_price' => $metadata['unit_price'],
                'deposit_amount' => $metadata['deposit_amount'],
                'remaining_amount' => $metadata['remaining_amount'],
                'total_amount' => $metadata['total_amount'],
                'fulfillment_method' => $metadata['fulfillment_method'],
                'shipping_address' => $metadata['shipping_address'],
                'city' => $metadata['city'],
                'state' => $metadata['state'],
                'pickup_location' => $metadata['pickup_location'],
                'notes' => $metadata['notes'],
                'paystack_reference' => $reference,
                'paystack_response' => $data['data'],
                'payment_method' => $data['data']['channel'] ?? 'card'
            ]);

            // Set payment status based on payment type
            if ($metadata['payment_type'] === 'deposit') {
                $customerPreOrder->markDepositAsPaid();
                // Create admin notification for deposit payment
                $this->notificationService->notifyPreOrderDepositPaid($customerPreOrder);
            } else {
                $customerPreOrder->markAsFullyPaid();
                // Create admin notification for full payment
                $this->notificationService->notifyPreOrderFullyPaid($customerPreOrder);
            }

            // Create admin notification for new pre-order
            $this->notificationService->notifyNewPreOrder($customerPreOrder);

            $customerPreOrder->load('preOrder.category');

            DB::commit();

            // Send confirmation email
            try {
                Mail::to($customerPreOrder->customer_email)->send(new PreOrderConfirmation($customerPreOrder));
            } catch (\Exception $e) {
                // Log the error but don't fail the request since payment was successful
                Log::error('Failed to send pre-order confirmation email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $customerPreOrder,
                'message' => ucfirst($metadata['payment_type']) . ' payment verified and pre-order created successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pre-order verification and creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reference = $request->reference;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret'),
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['data']['status'] === 'success') {
                    $metadata = $data['data']['metadata'];
                    $customerPreOrder = CustomerPreOrder::findOrFail($metadata['customer_pre_order_id']);
                    $paymentType = $metadata['payment_type'];
                    
                    // Update payment status
                    $previousStatus = $customerPreOrder->status;
                    
                    if ($paymentType === 'deposit') {
                        $customerPreOrder->markDepositAsPaid();
                        // Create admin notification for deposit payment
                        $this->notificationService->notifyPreOrderDepositPaid($customerPreOrder);
                    } else {
                        $customerPreOrder->markAsFullyPaid();
                        // Create admin notification for full payment
                        $this->notificationService->notifyPreOrderFullyPaid($customerPreOrder);
                        // For remaining balance payments, status can remain as ready_for_pickup 
                        // or move to completed based on business logic
                        if ($customerPreOrder->isDepositPaid() && $previousStatus === 'ready_for_pickup') {
                            // Keep status as ready_for_pickup until admin marks as completed
                            $customerPreOrder->update(['status' => 'ready_for_pickup']);
                        }
                    }
                    
                    $customerPreOrder->update([
                        'paystack_response' => $data['data'],
                        'payment_method' => $data['data']['channel'] ?? 'card'
                    ]);

                    // Send status update email
                    try {
                        Mail::to($customerPreOrder->customer_email)->send(
                            new PreOrderStatusUpdated($customerPreOrder, $previousStatus)
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to send pre-order status email: ' . $e->getMessage());
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $customerPreOrder->fresh(['preOrder.category']),
                        'message' => ucfirst($paymentType) . ' payment verified successfully!'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment verification failed',
                        'data' => $data
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify payment',
                    'error' => $response->json()
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer's pre-orders by email
     */
    public function getCustomerPreOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customerPreOrders = CustomerPreOrder::with(['preOrder.category'])
            ->where('customer_email', $request->customer_email)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customerPreOrders,
            'message' => 'Customer pre-orders retrieved successfully'
        ]);
    }

    /**
     * Get specific customer pre-order details
     */
    public function getCustomerPreOrder(string $preOrderNumber): JsonResponse
    {
        $customerPreOrder = CustomerPreOrder::with(['preOrder.category'])
            ->where('pre_order_number', $preOrderNumber)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $customerPreOrder,
            'message' => 'Pre-order details retrieved successfully'
        ]);
    }

    /**
     * Get amount due for a pre-order (for CTA display)
     */
    public function getAmountDue(string $preOrderNumber): JsonResponse
    {
        $customerPreOrder = CustomerPreOrder::where('pre_order_number', $preOrderNumber)
            ->firstOrFail();

        $amountDue = $customerPreOrder->isDepositPaid() 
            ? $customerPreOrder->remaining_amount 
            : $customerPreOrder->total_amount;

        $paymentType = $customerPreOrder->isDepositPaid() ? 'remaining' : 'full';

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $amountDue,
                'payment_type' => $paymentType,
                'payment_status' => $customerPreOrder->payment_status,
                'status' => $customerPreOrder->status,
                'can_pay_remaining' => $customerPreOrder->status === 'ready_for_pickup' && 
                                     $customerPreOrder->payment_status === 'deposit_paid'
            ],
            'message' => 'Amount due retrieved successfully'
        ]);
    }

    /**
     * Secure deep link for one-click remaining balance payment (Option A)
     */
    public function payRemainingDirectLink(Request $request, string $preOrderNumber): JsonResponse
    {
        // Validate the signed URL
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired payment link'
            ], 403);
        }

        $customerPreOrder = CustomerPreOrder::with('preOrder')
            ->where('pre_order_number', $preOrderNumber)
            ->firstOrFail();

        // Validate state
        if ($customerPreOrder->status !== 'ready_for_pickup' || 
            $customerPreOrder->payment_status !== 'deposit_paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment state. Remaining balance can only be paid when order is ready for pickup and deposit is paid.'
            ], 400);
        }

        if ($customerPreOrder->isFullyPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'This pre-order is already fully paid'
            ], 400);
        }

        // Initialize payment for remaining amount
        $amount = $customerPreOrder->remaining_amount;
        $amountInKobo = $amount * 100;

        $paystackData = [
            'amount' => $amountInKobo,
            'email' => $customerPreOrder->customer_email,
            'currency' => $customerPreOrder->currency,
            'reference' => 'pre_rem_' . $customerPreOrder->pre_order_number . '_' . time(),
            'callback_url' => config('app.frontend_url', config('app.url')) . '/payment/callback',
            'metadata' => [
                'customer_pre_order_id' => $customerPreOrder->id,
                'payment_type' => 'full',
                'customer_name' => $customerPreOrder->full_name,
                'product_name' => $customerPreOrder->preOrder->product_name,
                'is_remaining_payment' => true,
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $paystackData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update pre-order with payment details
                $customerPreOrder->update([
                    'paystack_reference' => $paystackData['reference'],
                    'paystack_access_code' => $responseData['data']['access_code'],
                ]);

                // Return 302 redirect to Paystack authorization URL
                return response()->json([
                    'success' => true,
                    'redirect_url' => $responseData['data']['authorization_url']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment',
                    'error' => $response->json()
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate secure payment token for frontend deep links (Option B)
     */
    public function generatePaymentToken(string $preOrderNumber): JsonResponse
    {
        $customerPreOrder = CustomerPreOrder::where('pre_order_number', $preOrderNumber)
            ->firstOrFail();

        // Validate state
        if ($customerPreOrder->status !== 'ready_for_pickup' || 
            $customerPreOrder->payment_status !== 'deposit_paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment token can only be generated for orders ready for pickup with deposit paid'
            ], 400);
        }

        // Generate signed, time-limited token
        $tokenData = [
            'pre_order_id' => $customerPreOrder->id,
            'pre_order_number' => $preOrderNumber,
            'customer_email' => $customerPreOrder->customer_email,
            'payment_type' => 'full',
            'expires_at' => now()->addHours(72)->timestamp
        ];

        $token = encrypt($tokenData);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_at' => $tokenData['expires_at'],
                'deep_link' => config('app.frontend_url') . "/pre-orders/confirmation/{$preOrderNumber}?action=pay-remaining&token={$token}"
            ],
            'message' => 'Payment token generated successfully'
        ]);
    }

    /**
     * Exchange payment token for pre-order details (Option B)
     */
    public function exchangePaymentToken(string $token): JsonResponse
    {
        try {
            $tokenData = decrypt($token);
            
            // Validate token expiry
            if ($tokenData['expires_at'] < now()->timestamp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment token has expired'
                ], 403);
            }

            $customerPreOrder = CustomerPreOrder::findOrFail($tokenData['pre_order_id']);

            // Re-validate state (in case it changed since token generation)
            if ($customerPreOrder->status !== 'ready_for_pickup' || 
                $customerPreOrder->payment_status !== 'deposit_paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment state'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pre_order_id' => $customerPreOrder->id,
                    'pre_order_number' => $customerPreOrder->pre_order_number,
                    'allowed_payment_type' => 'full',
                    'amount_due' => $customerPreOrder->remaining_amount,
                    'customer_name' => $customerPreOrder->full_name
                ],
                'message' => 'Payment token validated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment token'
            ], 403);
        }
    }

    /**
     * Handle Paystack webhook for payment confirmations
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();
        $computedSignature = hash_hmac('sha512', $body, config('services.paystack.webhook_secret'));

        if (!hash_equals($signature, $computedSignature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($body, true);
        
        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];
            
            // Find the customer pre-order by reference
            $customerPreOrder = CustomerPreOrder::where('paystack_reference', $reference)->first();
            
            if ($customerPreOrder && $data['status'] === 'success') {
                $metadata = $data['metadata'] ?? [];
                $paymentType = $metadata['payment_type'] ?? 'full';
                $previousStatus = $customerPreOrder->status;
                
                // Update payment status based on payment type
                if ($paymentType === 'deposit' && !$customerPreOrder->isDepositPaid()) {
                    $customerPreOrder->markDepositAsPaid();
                    // Create admin notification for deposit payment
                    $this->notificationService->notifyPreOrderDepositPaid($customerPreOrder);
                } elseif ($paymentType === 'full' && !$customerPreOrder->isFullyPaid()) {
                    $customerPreOrder->markAsFullyPaid();
                    // Create admin notification for full payment
                    $this->notificationService->notifyPreOrderFullyPaid($customerPreOrder);
                    
                    // For remaining balance payments, maintain ready_for_pickup status
                    if ($customerPreOrder->isDepositPaid() && $previousStatus === 'ready_for_pickup') {
                        $customerPreOrder->update(['status' => 'ready_for_pickup']);
                    }
                }
                
                // Update payment details
                $customerPreOrder->update([
                    'paystack_response' => $data,
                    'payment_method' => $data['channel'] ?? 'card'
                ]);

                // Send status update email
                try {
                    Mail::to($customerPreOrder->customer_email)->send(
                        new PreOrderStatusUpdated($customerPreOrder, $previousStatus)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send pre-order status email: ' . $e->getMessage());
                }
            }
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Debug endpoint to check customer pre-order data and payment calculations
     */
    public function debugPreOrder(string $preOrderNumber): JsonResponse
    {
        try {
            $customerPreOrder = CustomerPreOrder::with('preOrder')->where('pre_order_number', $preOrderNumber)->firstOrFail();
            
            // Calculate what the amounts should be
            $unitPrice = (float) $customerPreOrder->unit_price;
            $quantity = (int) $customerPreOrder->quantity;
            $calculatedTotal = $unitPrice * $quantity;
            
            // Get deposit percentage from the pre-order
            $depositPercentage = $customerPreOrder->preOrder->deposit_percentage ?? 0;
            $calculatedDeposit = ($calculatedTotal * $depositPercentage) / 100;
            $calculatedRemaining = $calculatedTotal - $calculatedDeposit;
            
            // Determine payment amounts for each type
            $depositAmount = (float) ($customerPreOrder->deposit_amount ?? 0);
            $remainingAmount = (float) ($customerPreOrder->remaining_amount ?? 0);
            $totalAmount = (float) ($customerPreOrder->total_amount ?? 0);
            
            $fullPaymentAmount = $customerPreOrder->isDepositPaid() ? $remainingAmount : $totalAmount;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'pre_order_info' => [
                        'number' => $customerPreOrder->pre_order_number,
                        'status' => $customerPreOrder->status,
                        'payment_status' => $customerPreOrder->payment_status,
                        'currency' => $customerPreOrder->currency,
                        'is_deposit_paid' => $customerPreOrder->isDepositPaid(),
                        'is_fully_paid' => $customerPreOrder->isFullyPaid(),
                    ],
                    'stored_amounts' => [
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'deposit_amount' => $depositAmount,
                        'remaining_amount' => $remainingAmount,
                        'total_amount' => $totalAmount,
                    ],
                    'calculated_amounts' => [
                        'total' => $calculatedTotal,
                        'deposit' => $calculatedDeposit,
                        'remaining' => $calculatedRemaining,
                        'deposit_percentage' => $depositPercentage,
                    ],
                    'payment_options' => [
                        'deposit_available' => !$customerPreOrder->isDepositPaid() && $depositAmount > 0,
                        'deposit_amount' => $depositAmount,
                        'deposit_amount_kobo' => (int) round($depositAmount * 100),
                        'full_available' => !$customerPreOrder->isFullyPaid() && $fullPaymentAmount > 0,
                        'full_amount' => $fullPaymentAmount,
                        'full_amount_kobo' => (int) round($fullPaymentAmount * 100),
                    ],
                    'validation_checks' => [
                        'amounts_match_calculation' => [
                            'total_matches' => abs($totalAmount - $calculatedTotal) < 0.01,
                            'deposit_matches' => abs($depositAmount - $calculatedDeposit) < 0.01,
                            'remaining_matches' => abs($remainingAmount - $calculatedRemaining) < 0.01,
                        ],
                        'amounts_positive' => [
                            'total_positive' => $totalAmount > 0,
                            'deposit_positive' => $depositAmount > 0,
                            'remaining_positive' => $remainingAmount > 0,
                        ],
                        'paystack_config' => [
                            'secret_configured' => !empty(config('services.paystack.secret')),
                            'webhook_secret_configured' => !empty(config('services.paystack.webhook_secret')),
                        ]
                    ]
                ],
                'message' => 'Debug information retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
