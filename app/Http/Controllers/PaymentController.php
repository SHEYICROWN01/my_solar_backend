<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CustomerPreOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Get payment management dashboard data including both orders and pre-orders
     */
    public function getDashboardData(): JsonResponse
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Calculate current month stats
        $currentStats = $this->getMonthlyStats($currentMonth);
        $lastMonthStats = $this->getMonthlyStats($lastMonth);
        
        // Calculate percentage changes
        $revenueChange = $this->calculatePercentageChange(
            $lastMonthStats['total_revenue'], 
            $currentStats['total_revenue']
        );
        
        $completedChange = $this->calculatePercentageChange(
            $lastMonthStats['completed_payments'], 
            $currentStats['completed_payments']
        );
        
        $avgTransactionChange = $this->calculatePercentageChange(
            $lastMonthStats['avg_transaction'], 
            $currentStats['avg_transaction']
        );

        return response()->json([
            'total_revenue' => [
                'amount' => $currentStats['total_revenue'],
                'formatted' => '₦' . number_format($currentStats['total_revenue'], 2),
                'change_percentage' => $revenueChange,
                'change_text' => $revenueChange > 0 ? "+{$revenueChange}% from last month" : "{$revenueChange}% from last month"
            ],
            'completed_payments' => [
                'count' => $currentStats['completed_payments'],
                'change_percentage' => $completedChange,
                'change_text' => "Successful payments"
            ],
            'pending_payments' => [
                'count' => $currentStats['pending_payments'],
                'change_text' => "Processing payments"
            ],
            'average_transaction' => [
                'amount' => $currentStats['avg_transaction'],
                'formatted' => '₦' . number_format($currentStats['avg_transaction'], 2),
                'change_percentage' => $avgTransactionChange,
                'change_text' => $avgTransactionChange > 0 ? "+{$avgTransactionChange}% from last month" : "{$avgTransactionChange}% from last month"
            ],
            'breakdown' => [
                'orders' => [
                    'count' => $currentStats['order_count'],
                    'revenue' => $currentStats['order_revenue']
                ],
                'pre_orders' => [
                    'count' => $currentStats['preorder_count'],
                    'revenue' => $currentStats['preorder_revenue']
                ]
            ]
        ]);
    }

    /**
     * Get recent transactions with pagination including both orders and pre-orders
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $type = $request->get('type', 'all'); // all, orders, pre-orders
        
        $transactions = collect();
        
        // Get order transactions
        if ($type === 'all' || $type === 'orders') {
            $orders = Order::select([
                    'order_number as transaction_id',
                    'first_name',
                    'last_name',
                    'customer_email',
                    'total_amount',
                    'payment_method',
                    'payment_status',
                    'status',
                    'created_at',
                    'paid_at'
                ])
                ->whereNotNull('payment_method')
                ->get()
                ->map(function ($order) {
                    return [
                        'transaction_id' => $order->transaction_id,
                        'type' => 'order',
                        'customer' => trim($order->first_name . ' ' . $order->last_name),
                        'customer_email' => $order->customer_email,
                        'amount' => '₦' . number_format($order->total_amount, 2),
                        'amount_raw' => $order->total_amount,
                        'method' => $this->formatPaymentMethod($order->payment_method),
                        'date' => $order->created_at->format('Y-m-d'),
                        'formatted_date' => $order->created_at->format('M d, Y'),
                        'status' => $this->mapOrderStatus($order->payment_status, $order->status),
                        'status_color' => $this->getStatusColor($order->payment_status, $order->status),
                        'created_at' => $order->created_at
                    ];
                });
            
            $transactions = $transactions->merge($orders);
        }
        
        // Get pre-order transactions
        if ($type === 'all' || $type === 'pre-orders') {
            $preOrders = CustomerPreOrder::select([
                    'pre_order_number as transaction_id',
                    'first_name',
                    'last_name',
                    'customer_email',
                    'total_amount',
                    'deposit_amount',
                    'remaining_amount',
                    'payment_method',
                    'payment_status',
                    'status',
                    'created_at',
                    'deposit_paid_at',
                    'fully_paid_at'
                ])
                ->whereNotNull('payment_method')
                ->get()
                ->map(function ($preOrder) {
                    return [
                        'transaction_id' => $preOrder->transaction_id,
                        'type' => 'pre-order',
                        'customer' => trim($preOrder->first_name . ' ' . $preOrder->last_name),
                        'customer_email' => $preOrder->customer_email,
                        'amount' => '₦' . number_format($preOrder->total_amount, 2),
                        'amount_raw' => $preOrder->total_amount,
                        'paid_amount' => $this->getPreOrderPaidAmount($preOrder),
                        'method' => $this->formatPaymentMethod($preOrder->payment_method),
                        'date' => $preOrder->created_at->format('Y-m-d'),
                        'formatted_date' => $preOrder->created_at->format('M d, Y'),
                        'status' => $this->mapPreOrderStatus($preOrder->payment_status, $preOrder->status),
                        'status_color' => $this->getPreOrderStatusColor($preOrder->payment_status, $preOrder->status),
                        'created_at' => $preOrder->created_at
                    ];
                });
            
            $transactions = $transactions->merge($preOrders);
        }

        // Sort by created_at and paginate manually
        $transactions = $transactions->sortByDesc('created_at');
        $total = $transactions->count();
        $offset = ($page - 1) * $perPage;
        $paginatedTransactions = $transactions->slice($offset, $perPage)->values();

        return response()->json([
            'transactions' => $paginatedTransactions,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ]);
    }

    /**
     * Get payment statistics for a specific period including both orders and pre-orders
     */
    public function getPaymentStats(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // month, week, year
        $startDate = $this->getStartDateForPeriod($period);
        
        // Get orders for the period
        $orders = Order::where('created_at', '>=', $startDate)->get();
        
        // Get pre-orders for the period
        $preOrders = CustomerPreOrder::where('created_at', '>=', $startDate)->get();
        
        // Calculate order stats
        $orderStats = [
            'total_transactions' => $orders->count(),
            'total_revenue' => $orders->where('payment_status', 'paid')->sum('total_amount'),
            'successful_payments' => $orders->where('payment_status', 'paid')->count(),
            'pending_payments' => $orders->where('payment_status', 'pending')->count(),
            'failed_payments' => $orders->where('payment_status', 'failed')->count(),
        ];
        
        // Calculate pre-order stats
        $preOrderStats = [
            'total_transactions' => $preOrders->count(),
            'total_revenue' => $preOrders->whereIn('payment_status', ['deposit_paid', 'fully_paid'])->sum('total_amount'),
            'deposit_revenue' => $preOrders->whereIn('payment_status', ['deposit_paid', 'fully_paid'])->sum('deposit_amount'),
            'remaining_revenue' => $preOrders->where('payment_status', 'fully_paid')->sum('remaining_amount'),
            'successful_payments' => $preOrders->whereIn('payment_status', ['deposit_paid', 'fully_paid'])->count(),
            'pending_payments' => $preOrders->where('payment_status', 'pending')->count(),
            'failed_payments' => $preOrders->where('payment_status', 'failed')->count(),
        ];
        
        // Combined stats
        $totalTransactions = $orderStats['total_transactions'] + $preOrderStats['total_transactions'];
        $totalRevenue = $orderStats['total_revenue'] + $preOrderStats['total_revenue'];
        $totalSuccessful = $orderStats['successful_payments'] + $preOrderStats['successful_payments'];
        $totalPending = $orderStats['pending_payments'] + $preOrderStats['pending_payments'];
        $totalFailed = $orderStats['failed_payments'] + $preOrderStats['failed_payments'];
        $avgTransactionAmount = $totalSuccessful > 0 ? $totalRevenue / $totalSuccessful : 0;

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'combined_stats' => [
                'total_transactions' => $totalTransactions,
                'total_revenue' => $totalRevenue,
                'successful_payments' => $totalSuccessful,
                'pending_payments' => $totalPending,
                'failed_payments' => $totalFailed,
                'avg_transaction_amount' => $avgTransactionAmount,
                'success_rate' => $totalTransactions > 0 
                    ? round(($totalSuccessful / $totalTransactions) * 100, 2)
                    : 0
            ],
            'order_stats' => $orderStats,
            'pre_order_stats' => $preOrderStats
        ]);
    }

    /**
     * Get payment method breakdown including both orders and pre-orders
     */
    public function getPaymentMethodBreakdown(): JsonResponse
    {
        // Get order payment methods
        $orders = Order::where('payment_status', 'paid')
            ->whereNotNull('payment_method')
            ->get();

        // Get pre-order payment methods
        $preOrders = CustomerPreOrder::whereIn('payment_status', ['deposit_paid', 'fully_paid'])
            ->whereNotNull('payment_method')
            ->get();

        // Combine and group by payment method
        $allTransactions = collect();
        
        // Process orders
        foreach ($orders as $order) {
            $allTransactions->push([
                'method' => $order->payment_method,
                'amount' => $order->total_amount,
                'type' => 'order'
            ]);
        }
        
        // Process pre-orders
        foreach ($preOrders as $preOrder) {
            $allTransactions->push([
                'method' => $preOrder->payment_method,
                'amount' => $preOrder->total_amount,
                'type' => 'pre-order'
            ]);
        }

        $breakdown = $allTransactions->groupBy('method')
            ->map(function ($methodTransactions, $method) {
                $transactionCount = $methodTransactions->count();
                $totalAmount = $methodTransactions->sum('amount');
                $avgAmount = $transactionCount > 0 ? $totalAmount / $transactionCount : 0;
                
                $orderCount = $methodTransactions->where('type', 'order')->count();
                $preOrderCount = $methodTransactions->where('type', 'pre-order')->count();

                return [
                    'method' => $this->formatPaymentMethod($method),
                    'transaction_count' => $transactionCount,
                    'total_amount' => $totalAmount,
                    'formatted_total' => '₦' . number_format($totalAmount, 2),
                    'avg_amount' => $avgAmount,
                    'formatted_avg' => '₦' . number_format($avgAmount, 2),
                    'order_count' => $orderCount,
                    'pre_order_count' => $preOrderCount
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        return response()->json(['payment_methods' => $breakdown]);
    }

    /**
     * Search transactions including both orders and pre-orders
     */
    public function searchTransactions(Request $request): JsonResponse
    {
        $query = $request->get('query');
        $status = $request->get('status');
        $method = $request->get('method');
        $type = $request->get('type', 'all'); // all, orders, pre-orders
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $results = collect();

        // Search orders
        if ($type === 'all' || $type === 'orders') {
            $orderQuery = Order::query()
                ->select([
                    'order_number as transaction_id',
                    'first_name',
                    'last_name',
                    'customer_email',
                    'total_amount',
                    'payment_method',
                    'payment_status',
                    'status',
                    'created_at'
                ])
                ->whereNotNull('payment_method');

            $this->applySearchFilters($orderQuery, $query, $status, $method, $dateFrom, $dateTo);

            $orders = $orderQuery->get()->map(function ($order) {
                return [
                    'transaction_id' => $order->transaction_id,
                    'type' => 'order',
                    'customer' => trim($order->first_name . ' ' . $order->last_name),
                    'customer_email' => $order->customer_email,
                    'amount' => '₦' . number_format($order->total_amount, 2),
                    'method' => $this->formatPaymentMethod($order->payment_method),
                    'date' => $order->created_at->format('Y-m-d'),
                    'status' => $this->mapOrderStatus($order->payment_status, $order->status),
                    'status_color' => $this->getStatusColor($order->payment_status, $order->status)
                ];
            });

            $results = $results->merge($orders);
        }

        // Search pre-orders
        if ($type === 'all' || $type === 'pre-orders') {
            $preOrderQuery = CustomerPreOrder::query()
                ->select([
                    'pre_order_number as transaction_id',
                    'first_name',
                    'last_name',
                    'customer_email',
                    'total_amount',
                    'payment_method',
                    'payment_status',
                    'status',
                    'created_at'
                ])
                ->whereNotNull('payment_method');

            $this->applySearchFilters($preOrderQuery, $query, $status, $method, $dateFrom, $dateTo, true);

            $preOrders = $preOrderQuery->get()->map(function ($preOrder) {
                return [
                    'transaction_id' => $preOrder->transaction_id,
                    'type' => 'pre-order',
                    'customer' => trim($preOrder->first_name . ' ' . $preOrder->last_name),
                    'customer_email' => $preOrder->customer_email,
                    'amount' => '₦' . number_format($preOrder->total_amount, 2),
                    'method' => $this->formatPaymentMethod($preOrder->payment_method),
                    'date' => $preOrder->created_at->format('Y-m-d'),
                    'status' => $this->mapPreOrderStatus($preOrder->payment_status, $preOrder->status),
                    'status_color' => $this->getPreOrderStatusColor($preOrder->payment_status, $preOrder->status)
                ];
            });

            $results = $results->merge($preOrders);
        }

        return response()->json(['transactions' => $results->take(50)]);
    }

    /**
     * Get payment analytics including revenue trends
     */
    public function getPaymentAnalytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDateForPeriod($period);
        
        // Revenue trend data
        $revenueTrend = $this->getRevenueTrend($startDate, $period);
        
        // Payment method distribution
        $methodDistribution = $this->getPaymentMethodBreakdown()->getData()->payment_methods;
        
        // Transaction type breakdown
        $typeBreakdown = $this->getTransactionTypeBreakdown($startDate);

        return response()->json([
            'revenue_trend' => $revenueTrend,
            'payment_method_distribution' => $methodDistribution,
            'transaction_type_breakdown' => $typeBreakdown
        ]);
    }

    /**
     * Private helper methods
     */
    private function getMonthlyStats(Carbon $month): array
    {
        $endOfMonth = $month->copy()->endOfMonth();
        
        // Get orders for the month
        $orders = Order::whereBetween('created_at', [$month, $endOfMonth])->get();
        $preOrders = CustomerPreOrder::whereBetween('created_at', [$month, $endOfMonth])->get();
        
        // Calculate order stats
        $orderRevenue = $orders->where('payment_status', 'paid')->sum('total_amount');
        $orderCompleted = $orders->where('payment_status', 'paid')->count();
        $orderPending = $orders->where('payment_status', 'pending')->count();
        
        // Calculate pre-order stats
        $preOrderRevenue = $preOrders->whereIn('payment_status', ['deposit_paid', 'fully_paid'])->sum('total_amount');
        $preOrderCompleted = $preOrders->whereIn('payment_status', ['deposit_paid', 'fully_paid'])->count();
        $preOrderPending = $preOrders->where('payment_status', 'pending')->count();
        
        // Combined stats
        $totalRevenue = $orderRevenue + $preOrderRevenue;
        $completedPayments = $orderCompleted + $preOrderCompleted;
        $pendingPayments = $orderPending + $preOrderPending;
        $avgTransaction = $completedPayments > 0 ? $totalRevenue / $completedPayments : 0;

        return [
            'total_revenue' => $totalRevenue,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'avg_transaction' => $avgTransaction,
            'order_count' => $orders->count(),
            'order_revenue' => $orderRevenue,
            'preorder_count' => $preOrders->count(),
            'preorder_revenue' => $preOrderRevenue
        ];
    }

    private function getPreOrderPaidAmount($preOrder): string
    {
        if ($preOrder->payment_status === 'fully_paid') {
            return '₦' . number_format($preOrder->total_amount, 2) . ' (Full)';
        } elseif ($preOrder->payment_status === 'deposit_paid') {
            return '₦' . number_format($preOrder->deposit_amount, 2) . ' (Deposit)';
        }
        return '₦0.00';
    }

    private function mapPreOrderStatus($paymentStatus, $orderStatus): string
    {
        if ($paymentStatus === 'fully_paid') {
            return 'Fully Paid';
        }
        
        if ($paymentStatus === 'deposit_paid') {
            return 'Deposit Paid';
        }
        
        if ($paymentStatus === 'pending') {
            return 'Pending';
        }
        
        if ($paymentStatus === 'failed') {
            return 'Failed';
        }
        
        return 'Pending';
    }

    private function getPreOrderStatusColor($paymentStatus, $orderStatus): string
    {
        if ($paymentStatus === 'fully_paid') {
            return 'green';
        }
        
        if ($paymentStatus === 'deposit_paid') {
            return 'blue';
        }
        
        if ($paymentStatus === 'pending') {
            return 'orange';
        }
        
        if ($paymentStatus === 'failed') {
            return 'red';
        }
        
        return 'orange';
    }

    private function applySearchFilters($query, $searchTerm, $status, $method, $dateFrom, $dateTo, $isPreOrder = false)
    {
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm, $isPreOrder) {
                $numberField = $isPreOrder ? 'pre_order_number' : 'order_number';
                $q->where($numberField, 'like', "%{$searchTerm}%")
                  ->orWhere('customer_email', 'like', "%{$searchTerm}%")
                  ->orWhere('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%");
            });
        }

        if ($status) {
            if ($isPreOrder) {
                // Map status for pre-orders
                $preOrderStatuses = [
                    'completed' => ['fully_paid'],
                    'pending' => ['pending', 'deposit_paid'],
                    'failed' => ['failed']
                ];
                
                if (isset($preOrderStatuses[$status])) {
                    $query->whereIn('payment_status', $preOrderStatuses[$status]);
                }
            } else {
                $query->where('payment_status', $status);
            }
        }

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function getRevenueTrend($startDate, $period): array
    {
        $days = $startDate->diffInDays(Carbon::now()) + 1;
        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            $orderRevenue = Order::whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('total_amount');
                
            $preOrderRevenue = CustomerPreOrder::whereDate('created_at', $date)
                ->whereIn('payment_status', ['deposit_paid', 'fully_paid'])
                ->sum('total_amount');

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'total_revenue' => $orderRevenue + $preOrderRevenue,
                'order_revenue' => $orderRevenue,
                'preorder_revenue' => $preOrderRevenue
            ];
        }

        return $trend;
    }

    private function getTransactionTypeBreakdown($startDate): array
    {
        $orderCount = Order::where('created_at', '>=', $startDate)->count();
        $preOrderCount = CustomerPreOrder::where('created_at', '>=', $startDate)->count();
        $total = $orderCount + $preOrderCount;

        return [
            'orders' => [
                'count' => $orderCount,
                'percentage' => $total > 0 ? round(($orderCount / $total) * 100, 1) : 0
            ],
            'pre_orders' => [
                'count' => $preOrderCount,
                'percentage' => $total > 0 ? round(($preOrderCount / $total) * 100, 1) : 0
            ]
        ];
    }

    private function calculatePercentageChange($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    private function formatPaymentMethod($method): string
    {
        $methods = [
            'card' => 'Credit Card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            'debit_card' => 'Debit Card',
            'mobile_money' => 'Mobile Money'
        ];

        return $methods[$method] ?? ucwords(str_replace('_', ' ', $method));
    }

    private function mapOrderStatus($paymentStatus, $orderStatus): string
    {
        if ($paymentStatus === 'paid' && in_array($orderStatus, ['paid', 'completed', 'delivered'])) {
            return 'Completed';
        }
        
        if ($paymentStatus === 'pending') {
            return 'Pending';
        }
        
        if ($paymentStatus === 'failed') {
            return 'Failed';
        }
        
        if ($orderStatus === 'refunded') {
            return 'Refunded';
        }
        
        return 'Pending';
    }

    private function getStatusColor($paymentStatus, $orderStatus): string
    {
        if ($paymentStatus === 'paid' && in_array($orderStatus, ['paid', 'completed', 'delivered'])) {
            return 'green';
        }
        
        if ($paymentStatus === 'pending') {
            return 'orange';
        }
        
        if ($paymentStatus === 'failed') {
            return 'red';
        }
        
        if ($orderStatus === 'refunded') {
            return 'blue';
        }
        
        return 'orange';
    }

    private function getStartDateForPeriod($period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };
    }
}