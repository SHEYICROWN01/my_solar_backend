<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\CustomerPreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data matching frontend requirements
     */
    public function getDashboardData(Request $request)
    {
        $period = $request->get('period', 'current_month');
        
        return response()->json([
            'success' => true,
            'data' => [
                'overview_metrics' => $this->getOverviewMetrics($period),
                'payment_dashboard' => $this->getPaymentDashboard($period),
                'order_statistics' => $this->getOrderStatistics(),
                'revenue_chart' => $this->getRevenueChartData($period),
                'recent_transactions' => $this->getRecentTransactions(10),
                'payment_methods' => $this->getPaymentMethodBreakdown(),
                'payment_stats' => $this->getPaymentStats($period),
                'order_status_distribution' => $this->getOrderStatusDistribution(),
                'top_selling_products' => $this->getTopSellingProducts($period),
                'recent_orders' => $this->getRecentOrders(),
                'sales_by_category' => $this->getSalesByCategory($period),
                'low_stock_alerts' => $this->getLowStockAlerts(),
                'preorder_metrics' => $this->getPreorderMetrics(),
                'preorder_analytics' => $this->getPreorderAnalytics(),
                'preorder_timeline' => $this->getPreorderTimeline(),
                'recent_preorders' => $this->getRecentPreorders()
            ],
            'message' => 'Dashboard data retrieved successfully'
        ]);
    }

    /**
     * Get overview metrics (total revenue, orders, products sold, conversion rate)
     */
    public function getOverviewMetrics($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);
        $previousDateRange = $this->getPreviousDateRange($period);

        // Current period metrics
        $currentRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_amount');

        $currentOrders = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $currentProductsSold = OrderItem::whereHas('order', function($query) use ($dateRange) {
            $query->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->sum('quantity');

        // Calculate conversion rate (assuming we track website visits - using orders/unique customers as proxy)
        $uniqueCustomers = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->distinct('customer_email')
            ->count();
        
        $totalVisitors = $uniqueCustomers * 24; // Estimate: assume 24 visitors per converting customer
        $conversionRate = $totalVisitors > 0 ? ($currentOrders / $totalVisitors) * 100 : 0;

        // Previous period metrics for comparison
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
            ->sum('total_amount');

        $previousOrders = Order::whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
            ->count();

        $previousProductsSold = OrderItem::whereHas('order', function($query) use ($previousDateRange) {
            $query->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']]);
        })->sum('quantity');

        $previousUniqueCustomers = Order::whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
            ->distinct('customer_email')
            ->count();
        
        $previousTotalVisitors = $previousUniqueCustomers * 24;
        $previousConversionRate = $previousTotalVisitors > 0 ? ($previousOrders / $previousTotalVisitors) * 100 : 0;

        // Calculate percentage changes
        $revenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $ordersChange = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $productsSoldChange = $previousProductsSold > 0 ? (($currentProductsSold - $previousProductsSold) / $previousProductsSold) * 100 : 0;
        $conversionRateChange = $previousConversionRate > 0 ? (($conversionRate - $previousConversionRate) / $previousConversionRate) * 100 : 0;

        return [
            'total_revenue' => [
                'value' => $currentRevenue,
                'formatted' => '₦' . number_format($currentRevenue, 1) . 'M',
                'change_percentage' => round($revenueChange, 1),
                'trend' => $revenueChange >= 0 ? 'up' : 'down'
            ],
            'total_orders' => [
                'value' => $currentOrders,
                'formatted' => number_format($currentOrders),
                'change_percentage' => round($ordersChange, 1),
                'trend' => $ordersChange >= 0 ? 'up' : 'down'
            ],
            'products_sold' => [
                'value' => $currentProductsSold,
                'formatted' => number_format($currentProductsSold),
                'change_percentage' => round($productsSoldChange, 1),
                'trend' => $productsSoldChange >= 0 ? 'up' : 'down'
            ],
            'conversion_rate' => [
                'value' => $conversionRate,
                'formatted' => number_format($conversionRate, 2) . '%',
                'change_percentage' => round($conversionRateChange, 1),
                'trend' => $conversionRateChange >= 0 ? 'up' : 'down'
            ]
        ];
    }

    /**
     * Get payment dashboard data matching PaymentDashboard interface
     */
    public function getPaymentDashboard($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);
        $previousDateRange = $this->getPreviousDateRange($period);

        // Current period payment metrics
        $currentRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_amount');

        $completedPayments = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $totalTransactions = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $averageTransaction = $completedPayments > 0 ? $currentRevenue / $completedPayments : 0;

        // Previous period for comparison
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
            ->sum('total_amount');

        $previousCompletedPayments = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
            ->count();

        $previousAverageTransaction = $previousCompletedPayments > 0 ? $previousRevenue / $previousCompletedPayments : 0;

        // Calculate changes
        $revenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $paymentsChange = $previousCompletedPayments > 0 ? (($completedPayments - $previousCompletedPayments) / $previousCompletedPayments) * 100 : 0;
        $avgTransactionChange = $previousAverageTransaction > 0 ? (($averageTransaction - $previousAverageTransaction) / $previousAverageTransaction) * 100 : 0;

        return [
            'total_revenue' => [
                'amount' => $currentRevenue,
                'formatted' => '₦' . number_format($currentRevenue, 2),
                'change_percentage' => round($revenueChange, 1),
                'change_text' => abs(round($revenueChange, 1)) . '% vs last period'
            ],
            'completed_payments' => [
                'count' => $completedPayments,
                'change_percentage' => round($paymentsChange, 1),
                'change_text' => abs(round($paymentsChange, 1)) . '% vs last period'
            ],
            'average_transaction' => [
                'amount' => $averageTransaction,
                'formatted' => '₦' . number_format($averageTransaction, 2),
                'change_percentage' => round($avgTransactionChange, 1),
                'change_text' => abs(round($avgTransactionChange, 1)) . '% vs last period'
            ],
            'total_transactions' => $totalTransactions
        ];
    }

    /**
     * Get order statistics matching OrderStatistics interface
     */
    public function getOrderStatistics()
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count()
        ];
    }

    /**
     * Get revenue chart data for the specified period
     */
    public function getRevenueChartData($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);
        
        // Get daily revenue and orders data for better chart visualization
        $revenueData = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(total_amount) as revenue')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $revenueData->map(function($item) {
            return [
                'name' => Carbon::parse($item->date)->format('M j'),
                'revenue' => $item->revenue,
                'orders' => $item->orders,
            ];
        })->toArray();
    }

    /**
     * Get top selling products with sales data and stock information
     */
    public function getTopSellingProducts($period = 'current_month', $limit = 5)
    {
        $dateRange = $this->getDateRange($period);

        $topProducts = OrderItem::whereHas('order', function($query) use ($dateRange) {
            $query->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->with('product')
        ->selectRaw('product_id, SUM(quantity) as total_sold, SUM(total_price) as total_revenue')
        ->groupBy('product_id')
        ->orderBy('total_sold', 'desc')
        ->limit($limit)
        ->get()
        ->map(function($item) {
            return [
                'name' => $item->product->name,
                'sales_count' => $item->total_sold,
                'revenue' => $item->total_revenue,
                'formatted_revenue' => '₦' . number_format($item->total_revenue / 1000000, 1) . 'M',
                'stock' => $item->product->stock,
                'stock_status' => $this->getStockStatus($item->product->stock),
            ];
        });

        return $topProducts;
    }

    /**
     * Get recent orders with customer and product information
     */
    public function getRecentOrders($limit = 10)
    {
        return Order::with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->first_name . ' ' . $order->last_name,
                    'formatted_total' => $order->total_amount,
                    'status' => $this->formatOrderStatus($order->status),
                    'formatted_date' => $order->created_at->format('M j, Y'),
                    'items_count' => $order->orderItems->count()
                ];
            })->toArray();
    }

    /**
     * Get sales data grouped by category for the chart
     */
    public function getSalesByCategory($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);

        $categorySales = OrderItem::whereHas('order', function($query) use ($dateRange) {
            $query->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->with(['product.category'])
        ->get()
        ->groupBy('product.category.name')
        ->map(function($items, $categoryName) {
            return [
                'category' => $categoryName,
                'total_sales' => $items->sum('total_price'),
                'units_sold' => $items->sum('quantity'),
            ];
        })
        ->values()
        ->sortByDesc('total_sales')
        ->take(5) // Top 5 categories
        ->map(function($item) {
            return [
                'category' => $item['category'],
                'sales' => $item['total_sales'],
                'units_sold' => $item['units_sold'],
                'formatted_sales' => '₦' . number_format($item['total_sales'] / 1000, 0) . 'K',
            ];
        });

        return $categorySales->values()->all();
    }

    /**
     * Get products with low stock levels
     */
    public function getLowStockAlerts($threshold = 10)
    {
        return Product::where('stock', '<=', $threshold)
            ->where('stock', '>', 0) // Only products with some stock left
            ->orderBy('stock', 'asc')
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'stock_message' => "Only {$product->stock} units left",
                    'status' => $this->getStockStatus($product->stock),
                    'category' => $product->category->name ?? 'Uncategorized',
                ];
            });
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'current_week':
                return [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek(),
                ];
            case 'current_month':
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
            case 'last_month':
                return [
                    'start' => Carbon::now()->subMonth()->startOfMonth(),
                    'end' => Carbon::now()->subMonth()->endOfMonth(),
                ];
            case 'last_3_months':
                return [
                    'start' => Carbon::now()->subMonths(3)->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
            case 'last_6_months':
                return [
                    'start' => Carbon::now()->subMonths(6)->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
            case 'current_year':
                return [
                    'start' => Carbon::now()->startOfYear(),
                    'end' => Carbon::now()->endOfYear(),
                ];
            default:
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
        }
    }

    /**
     * Get previous period date range for comparison
     */
    private function getPreviousDateRange($period)
    {
        switch ($period) {
            case 'current_month':
                return [
                    'start' => Carbon::now()->subMonth()->startOfMonth(),
                    'end' => Carbon::now()->subMonth()->endOfMonth(),
                ];
            case 'last_month':
                return [
                    'start' => Carbon::now()->subMonths(2)->startOfMonth(),
                    'end' => Carbon::now()->subMonths(2)->endOfMonth(),
                ];
            case 'last_3_months':
                return [
                    'start' => Carbon::now()->subMonths(6)->startOfMonth(),
                    'end' => Carbon::now()->subMonths(3)->endOfMonth(),
                ];
            case 'last_6_months':
                return [
                    'start' => Carbon::now()->subMonths(12)->startOfMonth(),
                    'end' => Carbon::now()->subMonths(6)->endOfMonth(),
                ];
            case 'current_year':
                return [
                    'start' => Carbon::now()->subYear()->startOfYear(),
                    'end' => Carbon::now()->subYear()->endOfYear(),
                ];
            default:
                return [
                    'start' => Carbon::now()->subMonth()->startOfMonth(),
                    'end' => Carbon::now()->subMonth()->endOfMonth(),
                ];
        }
    }

    /**
     * Determine stock status based on quantity
     */
    private function getStockStatus($stock)
    {
        if ($stock <= 0) {
            return 'out_of_stock';
        } elseif ($stock <= 10) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Format order status for display
     */
    private function formatOrderStatus($status)
    {
        $statusMap = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[$status] ?? ucfirst($status);
    }

    /**
     * Get recent transactions for the dashboard
     */
    public function getRecentTransactions($limit = 10)
    {
        return Order::with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($order) {
                return [
                    'order_id' => $order->order_number,
                    'customer' => $order->first_name . ' ' . $order->last_name,
                    'customer_email' => $order->customer_email,
                    'amount' => '₦' . number_format($order->total_amount, 2),
                    'amount_raw' => $order->total_amount,
                    'status' => $this->formatOrderStatus($order->status),
                    'method' => $order->payment_method ?? 'Paystack',
                    'date' => $order->created_at->format('Y-m-d'),
                    'formatted_date' => $order->created_at->format('M j, Y')
                ];
            })->toArray();
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown()
    {
        $breakdown = Order::where('payment_status', 'paid')
            ->select('payment_method')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->map(function($item) {
                return [
                    'method' => $item->payment_method ?? 'Paystack',
                    'transaction_count' => $item->transaction_count,
                    'total_amount' => $item->total_amount,
                    'formatted_amount' => '₦' . number_format($item->total_amount, 2)
                ];
            });

        return [
            'payment_methods' => $breakdown->toArray()
        ];
    }

    /**
     * Get payment statistics for specific period
     */
    public function getPaymentStats($period = 'month')
    {
        $dateRange = $this->getDateRange($period === 'week' ? 'current_week' : 
                                        ($period === 'year' ? 'current_year' : 'current_month'));

        $totalTransactions = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $successfulPayments = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $failedPayments = Order::where('payment_status', 'failed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $successRate = $totalTransactions > 0 ? round(($successfulPayments / $totalTransactions) * 100, 1) : 0;

        return [
            'stats' => [
                'total_transactions' => $totalTransactions,
                'successful_payments' => $successfulPayments,
                'failed_payments' => $failedPayments,
                'success_rate' => $successRate
            ]
        ];
    }

    /**
     * Get order status distribution for charts
     */
    public function getOrderStatusDistribution()
    {
        return Order::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $this->formatOrderStatus($item->status),
                    'count' => $item->count
                ];
            })->toArray();
    }

    /**
     * Get pre-order metrics/KPIs
     */
    public function getPreorderMetrics()
    {
        $total = CustomerPreOrder::count();
        $depositAmount = CustomerPreOrder::sum('deposit_amount');
        $remainingAmount = CustomerPreOrder::sum('remaining_amount');
        $totalAmount = CustomerPreOrder::sum('total_amount');
        $pendingCount = CustomerPreOrder::where('status', 'like', '%pending%')->count();

        return [
            'total' => $total,
            'deposit' => $depositAmount,
            'remaining' => $remainingAmount,
            'totalAmount' => $totalAmount,
            'pendingCount' => $pendingCount
        ];
    }

    /**
     * Get pre-order analytics including status breakdown
     */
    public function getPreorderAnalytics()
    {
        $statusData = CustomerPreOrder::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count
                ];
            })->toArray();

        return [
            'status_breakdown' => $statusData
        ];
    }

    /**
     * Get pre-order timeline data
     */
    public function getPreorderTimeline()
    {
        return CustomerPreOrder::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->date,
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })->toArray();
    }

    /**
     * Get recent pre-orders
     */
    public function getRecentPreorders($limit = 10)
    {
        return CustomerPreOrder::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($preorder) {
                return [
                    'id' => $preorder->id,
                    'pre_order_number' => $preorder->pre_order_number,
                    'first_name' => $preorder->first_name,
                    'last_name' => $preorder->last_name,
                    'customer_email' => $preorder->customer_email,
                    'status' => $preorder->status,
                    'total_amount' => $preorder->total_amount,
                    'deposit_amount' => $preorder->deposit_amount,
                    'remaining_amount' => $preorder->remaining_amount,
                    'created_at' => $preorder->created_at->format('Y-m-d H:i:s')
                ];
            })->toArray();
    }

    /**
     * Individual endpoint: Get payment dashboard data
     */
    public function getPaymentDashboardData(Request $request)
    {
        $period = $request->get('period', 'current_month');
        
        return response()->json([
            'success' => true,
            'data' => $this->getPaymentDashboard($period),
            'message' => 'Payment dashboard data retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get recent transactions
     */
    public function getRecentTransactionsData(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 60);
        $offset = ($page - 1) * $limit;

        $transactions = $this->getRecentTransactions($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions
            ],
            'message' => 'Recent transactions retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get payment method breakdown
     */
    public function getPaymentMethodBreakdownData(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->getPaymentMethodBreakdown(),
            'message' => 'Payment method breakdown retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get payment statistics
     */
    public function getPaymentStatsData(Request $request)
    {
        $period = $request->get('period', 'month');
        
        return response()->json([
            'success' => true,
            'data' => $this->getPaymentStats($period),
            'message' => 'Payment statistics retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get order statistics
     */
    public function getOrderStatisticsData(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->getOrderStatistics(),
            'message' => 'Order statistics retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get sales by category
     */
    public function getSalesByCategoryData(Request $request)
    {
        $period = $request->get('period', 'current_month');
        
        return response()->json([
            'success' => true,
            'data' => $this->getSalesByCategory($period),
            'message' => 'Sales by category retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get low stock alerts
     */
    public function getLowStockAlertsData(Request $request)
    {
        $threshold = $request->get('threshold', 10);
        
        return response()->json([
            'success' => true,
            'data' => $this->getLowStockAlerts($threshold),
            'message' => 'Low stock alerts retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get pre-order metrics
     */
    public function getPreorderMetricsData(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->getPreorderMetrics(),
            'message' => 'Pre-order metrics retrieved successfully'
        ]);
    }

    /**
     * Individual endpoint: Get customer pre-orders (for admin)
     */
    public function getCustomerPreordersData(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 30);
        
        $preorders = CustomerPreOrder::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $preorders->items(),
                'current_page' => $preorders->currentPage(),
                'per_page' => $preorders->perPage(),
                'total' => $preorders->total(),
                'last_page' => $preorders->lastPage()
            ],
            'message' => 'Customer pre-orders retrieved successfully'
        ]);
    }
}