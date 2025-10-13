<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\CustomerPreOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Get sales trend data for line chart including both orders and pre-orders
     */
    public function getSalesTrend(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $period = $request->get('period', '30d');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set date range based on period or custom dates
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            $end = Carbon::now();
            $start = match($period) {
                '7d' => $end->copy()->subDays(7),
                '30d' => $end->copy()->subDays(30),
                '90d' => $end->copy()->subDays(90),
                '1y' => $end->copy()->subYear(),
                default => $end->copy()->subDays(30),
            };
        }

        // Get order sales data
        $orderSalesData = Order::selectRaw("
                DATE_TRUNC('day', created_at)::date as period,
                SUM(total_amount) as revenue,
                COUNT(*) as orders,
                COUNT(DISTINCT customer_email) as customers
            ")
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Get pre-order sales data
        $preOrderSalesData = CustomerPreOrder::selectRaw("
                DATE_TRUNC('day', created_at)::date as period,
                SUM(total_amount) as revenue,
                COUNT(*) as pre_orders,
                COUNT(DISTINCT customer_email) as customers
            ")
            ->whereIn('payment_status', ['deposit_paid', 'fully_paid'])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Combine the data
        $combinedSalesData = collect();
        $currentDate = $start->copy();
        
        while ($currentDate <= $end) {
            $dateKey = $currentDate->format('Y-m-d');
            $orderData = $orderSalesData->get($dateKey);
            $preOrderData = $preOrderSalesData->get($dateKey);
            
            $combinedSalesData->push([
                'period' => $dateKey,
                'total_revenue' => ($orderData->revenue ?? 0) + ($preOrderData->revenue ?? 0),
                'order_revenue' => $orderData->revenue ?? 0,
                'preorder_revenue' => $preOrderData->revenue ?? 0,
                'total_transactions' => ($orderData->orders ?? 0) + ($preOrderData->pre_orders ?? 0),
                'orders' => $orderData->orders ?? 0,
                'pre_orders' => $preOrderData->pre_orders ?? 0,
                'total_customers' => max($orderData->customers ?? 0, $preOrderData->customers ?? 0)
            ]);
            
            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sales_trend' => $combinedSalesData,
                'period' => $period,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Get sales by category for pie chart including pre-orders
     */
    public function getSalesByCategory(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $period = $request->get('period', '30d');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set date range
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            $end = Carbon::now();
            $start = match($period) {
                '7d' => $end->copy()->subDays(7),
                '30d' => $end->copy()->subDays(30),
                '90d' => $end->copy()->subDays(90),
                '1y' => $end->copy()->subYear(),
                default => $end->copy()->subDays(30),
            };
        }

        // Get order category data
        $orderCategoryData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                'categories.name as category_name',
                DB::raw('SUM(order_items.total_price) as revenue'),
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ])
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->keyBy('category_name');

        // Get pre-order category data
        $preOrderCategoryData = DB::table('customer_pre_orders')
            ->join('pre_orders', 'customer_pre_orders.pre_order_id', '=', 'pre_orders.id')
            ->join('categories', 'pre_orders.category_id', '=', 'categories.id')
            ->select([
                'categories.name as category_name',
                DB::raw('SUM(customer_pre_orders.total_amount) as revenue'),
                DB::raw('SUM(customer_pre_orders.quantity) as quantity_sold'),
                DB::raw('COUNT(*) as preorders_count')
            ])
            ->whereIn('customer_pre_orders.payment_status', ['deposit_paid', 'fully_paid'])
            ->whereBetween('customer_pre_orders.created_at', [$start, $end])
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->keyBy('category_name');

        // Combine category data
        $allCategories = $orderCategoryData->keys()->merge($preOrderCategoryData->keys())->unique();
        $combinedCategoryData = collect();

        foreach ($allCategories as $categoryName) {
            $orderData = $orderCategoryData->get($categoryName);
            $preOrderData = $preOrderCategoryData->get($categoryName);

            $combinedCategoryData->push([
                'category_name' => $categoryName,
                'total_revenue' => ($orderData->revenue ?? 0) + ($preOrderData->revenue ?? 0),
                'order_revenue' => $orderData->revenue ?? 0,
                'preorder_revenue' => $preOrderData->revenue ?? 0,
                'total_quantity_sold' => ($orderData->quantity_sold ?? 0) + ($preOrderData->quantity_sold ?? 0),
                'orders_count' => $orderData->orders_count ?? 0,
                'preorders_count' => $preOrderData->preorders_count ?? 0,
                'total_transactions' => ($orderData->orders_count ?? 0) + ($preOrderData->preorders_count ?? 0)
            ]);
        }

        // Sort by total revenue and calculate percentages
        $combinedCategoryData = $combinedCategoryData->sortByDesc('total_revenue');
        $totalRevenue = $combinedCategoryData->sum('total_revenue');
        
        $combinedCategoryData = $combinedCategoryData->map(function ($item) use ($totalRevenue) {
            $item['percentage'] = $totalRevenue > 0 ? round(($item['total_revenue'] / $totalRevenue) * 100, 1) : 0;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $combinedCategoryData->values(),
                'total_revenue' => $totalRevenue,
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get customer segments analysis including pre-order customers
     */
    public function getCustomerSegments(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
        ]);

        $period = $request->get('period', '90d');
        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(90),
        };

        // Get order customer data
        $orderCustomers = DB::table('orders')
            ->select([
                'customer_email',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('MAX(created_at) as last_order_date')
            ])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('customer_email')
            ->get()
            ->keyBy('customer_email');

        // Get pre-order customer data
        $preOrderCustomers = DB::table('customer_pre_orders')
            ->select([
                'customer_email',
                DB::raw('COUNT(*) as preorder_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_preorder_value'),
                DB::raw('MAX(created_at) as last_preorder_date')
            ])
            ->whereIn('payment_status', ['deposit_paid', 'fully_paid'])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('customer_email')
            ->get()
            ->keyBy('customer_email');

        // Combine customer data
        $allCustomers = $orderCustomers->keys()->merge($preOrderCustomers->keys())->unique();
        $combinedCustomerData = collect();

        foreach ($allCustomers as $customerEmail) {
            $orderData = $orderCustomers->get($customerEmail);
            $preOrderData = $preOrderCustomers->get($customerEmail);

            $combinedCustomerData->push([
                'customer_email' => $customerEmail,
                'total_orders' => ($orderData->order_count ?? 0) + ($preOrderData->preorder_count ?? 0),
                'order_count' => $orderData->order_count ?? 0,
                'preorder_count' => $preOrderData->preorder_count ?? 0,
                'total_spent' => ($orderData->total_spent ?? 0) + ($preOrderData->total_spent ?? 0),
                'order_spent' => $orderData->total_spent ?? 0,
                'preorder_spent' => $preOrderData->total_spent ?? 0
            ]);
        }

        // Categorize customers
        $segments = [
            'high_value' => 0,      // >₦500,000 total spent
            'medium_value' => 0,    // ₦100,000-₦500,000 total spent
            'low_value' => 0,       // <₦100,000 total spent
            'frequent' => 0,        // >3 transactions
            'occasional' => 0,      // 2-3 transactions
            'one_time' => 0,        // 1 transaction
        ];

        foreach ($combinedCustomerData as $customer) {
            // Value segments
            if ($customer['total_spent'] > 500000) {
                $segments['high_value']++;
            } elseif ($customer['total_spent'] >= 100000) {
                $segments['medium_value']++;
            } else {
                $segments['low_value']++;
            }

            // Frequency segments
            if ($customer['total_orders'] > 3) {
                $segments['frequent']++;
            } elseif ($customer['total_orders'] >= 2) {
                $segments['occasional']++;
            } else {
                $segments['one_time']++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'segments' => $segments,
                'total_customers' => $combinedCustomerData->count(),
                'breakdown' => [
                    'order_only_customers' => $orderCustomers->count() - $allCustomers->intersect($preOrderCustomers->keys())->count(),
                    'preorder_only_customers' => $preOrderCustomers->count() - $allCustomers->intersect($orderCustomers->keys())->count(),
                    'both_customers' => $allCustomers->intersect($orderCustomers->keys())->intersect($preOrderCustomers->keys())->count()
                ],
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get comprehensive analytics overview including pre-orders
     */
    public function getAnalyticsOverview(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
        ]);

        $period = $request->get('period', '30d');
        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(30),
        };

        // Previous period for comparison
        $prevStart = $start->copy()->sub($start->diffAsCarbonInterval($end));
        $prevEnd = $start->copy();

        // Current period metrics
        $current = $this->getPeriodMetrics($start, $end);
        $previous = $this->getPeriodMetrics($prevStart, $prevEnd);

        // Calculate growth rates
        $growth = [
            'revenue' => $this->calculateGrowthRate($previous['revenue'], $current['revenue']),
            'orders' => $this->calculateGrowthRate($previous['orders'], $current['orders']),
            'pre_orders' => $this->calculateGrowthRate($previous['pre_orders'], $current['pre_orders']),
            'customers' => $this->calculateGrowthRate($previous['customers'], $current['customers']),
            'avg_transaction_value' => $this->calculateGrowthRate($previous['avg_transaction_value'], $current['avg_transaction_value']),
        ];

        // Today's specific metrics
        $todayStart = Carbon::today();
        $todayEnd = Carbon::now();
        $todayMetrics = $this->getPeriodMetrics($todayStart, $todayEnd);

        // This week's metrics
        $weekStart = Carbon::now()->startOfWeek();
        $weekMetrics = $this->getPeriodMetrics($weekStart, $todayEnd);

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $current,
                'previous' => $previous,
                'growth' => $growth,
                'today' => $todayMetrics,
                'this_week' => $weekMetrics,
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get top performing products including pre-order products
     */
    public function getTopProducts(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'limit' => 'sometimes|integer|min:5|max:50',
            'sort_by' => 'sometimes|in:revenue,quantity,orders',
        ]);

        $period = $request->get('period', '30d');
        $limit = $request->get('limit', 10);
        $sortBy = $request->get('sort_by', 'revenue');

        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(30),
        };

        // Get order products
        $orderProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                'products.id',
                'products.name',
                'categories.name as category_name',
                'products.price',
                DB::raw('SUM(order_items.total_price) as revenue'),
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                DB::raw('AVG(order_items.product_price) as avg_selling_price')
            ])
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('products.id', 'products.name', 'categories.name', 'products.price')
            ->get()
            ->keyBy('id');

        // Get pre-order products
        $preOrderProducts = DB::table('customer_pre_orders')
            ->join('pre_orders', 'customer_pre_orders.pre_order_id', '=', 'pre_orders.id')
            ->join('categories', 'pre_orders.category_id', '=', 'categories.id')
            ->select([
                'pre_orders.id',
                'pre_orders.title as name',
                'categories.name as category_name',
                'pre_orders.price',
                DB::raw('SUM(customer_pre_orders.total_amount) as revenue'),
                DB::raw('SUM(customer_pre_orders.quantity) as quantity_sold'),
                DB::raw('COUNT(*) as preorders_count'),
                DB::raw('AVG(customer_pre_orders.unit_price) as avg_selling_price')
            ])
            ->whereIn('customer_pre_orders.payment_status', ['deposit_paid', 'fully_paid'])
            ->whereBetween('customer_pre_orders.created_at', [$start, $end])
            ->groupBy('pre_orders.id', 'pre_orders.title', 'categories.name', 'pre_orders.price')
            ->get();

        // Combine and process data
        $combinedProducts = collect();

        // Add order products
        foreach ($orderProducts as $product) {
            $combinedProducts->push([
                'id' => $product->id,
                'name' => $product->name,
                'category_name' => $product->category_name,
                'price' => $product->price,
                'total_revenue' => $product->revenue,
                'order_revenue' => $product->revenue,
                'preorder_revenue' => 0,
                'total_quantity_sold' => $product->quantity_sold,
                'orders_count' => $product->orders_count,
                'preorders_count' => 0,
                'total_transactions' => $product->orders_count,
                'avg_selling_price' => $product->avg_selling_price,
                'type' => 'product'
            ]);
        }

        // Add pre-order products
        foreach ($preOrderProducts as $preOrder) {
            $combinedProducts->push([
                'id' => 'pre_' . $preOrder->id,
                'name' => $preOrder->name,
                'category_name' => $preOrder->category_name,
                'price' => $preOrder->price,
                'total_revenue' => $preOrder->revenue,
                'order_revenue' => 0,
                'preorder_revenue' => $preOrder->revenue,
                'total_quantity_sold' => $preOrder->quantity_sold,
                'orders_count' => 0,
                'preorders_count' => $preOrder->preorders_count,
                'total_transactions' => $preOrder->preorders_count,
                'avg_selling_price' => $preOrder->avg_selling_price,
                'type' => 'pre-order'
            ]);
        }

        // Sort by specified criteria
        $sortField = match($sortBy) {
            'quantity' => 'total_quantity_sold',
            'orders' => 'total_transactions',
            default => 'total_revenue'
        };

        $topProducts = $combinedProducts->sortByDesc($sortField)->take($limit)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $topProducts,
                'period' => $period,
                'sort_by' => $sortBy,
            ]
        ]);
    }

    /**
     * Get revenue performance metrics
     */
    public function getRevenueMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
        ]);

        $period = $request->get('period', '30d');
        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(30),
        };

        $metrics = Order::selectRaw('
                SUM(total_amount) as total_revenue,
                SUM(subtotal) as total_subtotal,
                SUM(shipping_fee) as total_shipping,
                SUM(discount_amount) as total_discounts,
                AVG(total_amount) as avg_order_value,
                COUNT(*) as total_orders,
                COUNT(DISTINCT customer_email) as unique_customers
            ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->first();

        // Payment method breakdown
        $paymentMethods = Order::selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as revenue
            ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'payment_methods' => $paymentMethods,
                'period' => $period,
            ]
        ]);
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sales_trend,category_sales,customer_segments,top_products',
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'format' => 'sometimes|in:json,csv',
        ]);

        $type = $request->get('type');
        $format = $request->get('format', 'json');

        $data = match($type) {
            'sales_trend' => $this->getSalesTrend($request)->getData()->data,
            'category_sales' => $this->getSalesByCategory($request)->getData()->data,
            'customer_segments' => $this->getCustomerSegments($request)->getData()->data,
            'top_products' => $this->getTopProducts($request)->getData()->data,
        };

        if ($format === 'csv') {
            // Convert to CSV format
            $csvData = $this->convertToCSV($data, $type);
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="analytics_' . $type . '_' . date('Y-m-d') . '.csv"');
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'exported_at' => now()->toISOString(),
        ]);
    }

    /**
     * Helper method to get metrics for a period including pre-orders
     */
    private function getPeriodMetrics(Carbon $start, Carbon $end): array
    {
        // Order metrics
        $orders = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end]);

        $orderRevenue = $orders->sum('total_amount');
        $orderCount = $orders->count();
        $orderCustomers = $orders->distinct('customer_email')->count();

        // Pre-order metrics
        $preOrders = CustomerPreOrder::whereIn('payment_status', ['deposit_paid', 'fully_paid'])
            ->whereBetween('created_at', [$start, $end]);

        $preOrderRevenue = $preOrders->sum('total_amount');
        $preOrderCount = $preOrders->count();
        $preOrderCustomers = $preOrders->distinct('customer_email')->count();

        // Combined metrics
        $totalRevenue = $orderRevenue + $preOrderRevenue;
        $totalTransactions = $orderCount + $preOrderCount;
        $totalCustomers = max($orderCustomers, $preOrderCustomers); // Approximate unique customers

        return [
            'revenue' => $totalRevenue,
            'order_revenue' => $orderRevenue,
            'preorder_revenue' => $preOrderRevenue,
            'orders' => $orderCount,
            'pre_orders' => $preOrderCount,
            'total_transactions' => $totalTransactions,
            'customers' => $totalCustomers,
            'avg_order_value' => $orderCount > 0 ? $orderRevenue / $orderCount : 0,
            'avg_preorder_value' => $preOrderCount > 0 ? $preOrderRevenue / $preOrderCount : 0,
            'avg_transaction_value' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
        ];
    }

    /**
     * Calculate growth rate between two values
     */
    private function calculateGrowthRate($previous, $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Convert data to CSV format
     */
    private function convertToCSV($data, $type): string
    {
        $output = '';
        
        switch ($type) {
            case 'sales_trend':
                $output = "Period,Revenue,Orders,Customers\n";
                foreach ($data->sales_trend as $item) {
                    $output .= "{$item->period},{$item->revenue},{$item->orders},{$item->customers}\n";
                }
                break;
                
            case 'category_sales':
                $output = "Category,Revenue,Quantity Sold,Orders,Percentage\n";
                foreach ($data->categories as $item) {
                    $output .= "{$item->category_name},{$item->revenue},{$item->quantity_sold},{$item->orders_count},{$item->percentage}%\n";
                }
                break;
                
            case 'top_products':
                $output = "Product Name,Category,Price,Revenue,Quantity Sold,Orders Count,Avg Selling Price\n";
                foreach ($data->products as $item) {
                    $output .= "{$item->name},{$item->category_name},{$item->price},{$item->revenue},{$item->quantity_sold},{$item->orders_count},{$item->avg_selling_price}\n";
                }
                break;
        }
        
        return $output;
    }

    /**
     * Get advanced customer segments analysis (New, Returning, VIP)
     */
    public function getAdvancedCustomerSegments(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
        ]);

        $period = $request->get('period', '90d');
        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(90),
        };

        // Get all customers with their order history
        $customerStats = DB::table('orders')
            ->select([
                'customer_email',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MIN(created_at) as first_order_date'),
                DB::raw('MAX(created_at) as last_order_date')
            ])
            ->where('payment_status', 'paid')
            ->groupBy('customer_email')
            ->get();

        // Categorize customers based on advanced criteria
        $segments = [
            'new' => 0,         // First order within the period
            'returning' => 0,   // Multiple orders, moderate spending
            'vip' => 0,         // High value customers (>$1000 total or >5 orders)
        ];

        $segmentDetails = [
            'new' => [],
            'returning' => [],
            'vip' => []
        ];

        foreach ($customerStats as $customer) {
            $firstOrderDate = Carbon::parse($customer->first_order_date);
            $isNewCustomer = $firstOrderDate >= $start;
            $isVIP = $customer->total_spent > 1000 || $customer->total_orders > 5;

            if ($isVIP) {
                $segments['vip']++;
                $segmentDetails['vip'][] = [
                    'email' => $customer->customer_email,
                    'orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'avg_order_value' => $customer->total_spent / $customer->total_orders
                ];
            } elseif ($isNewCustomer) {
                $segments['new']++;
                $segmentDetails['new'][] = [
                    'email' => $customer->customer_email,
                    'orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'first_order' => $customer->first_order_date
                ];
            } else {
                $segments['returning']++;
                $segmentDetails['returning'][] = [
                    'email' => $customer->customer_email,
                    'orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'last_order' => $customer->last_order_date
                ];
            }
        }

        // Calculate percentages
        $totalCustomers = array_sum($segments);
        $percentages = [];
        foreach ($segments as $segment => $count) {
            $percentages[$segment] = $totalCustomers > 0 ? round(($count / $totalCustomers) * 100, 1) : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'segments' => $segments,
                'percentages' => $percentages,
                'segment_details' => $segmentDetails,
                'total_customers' => $totalCustomers,
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get enhanced product performance with growth metrics
     */
    public function getEnhancedProductPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'limit' => 'sometimes|integer|min:5|max:50',
        ]);

        $period = $request->get('period', '30d');
        $limit = $request->get('limit', 10);

        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(30),
        };

        // Previous period for comparison
        $prevStart = $start->copy()->sub($start->diffAsCarbonInterval($end));
        $prevEnd = $start->copy();

        // Current period data
        $currentProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                'products.id',
                'products.name',
                'categories.name as category_name',
                'products.price',
                DB::raw('SUM(order_items.total_price) as revenue'),
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                DB::raw('AVG(order_items.product_price) as avg_selling_price')
            ])
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('products.id', 'products.name', 'categories.name', 'products.price')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get()
            ->keyBy('id');

        // Previous period data for the same products
        $productIds = $currentProducts->pluck('id')->toArray();
        $previousProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select([
                'order_items.product_id as id',
                DB::raw('SUM(order_items.total_price) as revenue'),
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
            ])
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$prevStart, $prevEnd])
            ->whereIn('order_items.product_id', $productIds)
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('id');

        // Calculate growth rates
        $enhancedProducts = $currentProducts->map(function ($product) use ($previousProducts) {
            $previous = $previousProducts->get($product->id);
            
            $revenueGrowth = 0;
            $quantityGrowth = 0;
            
            if ($previous) {
                $revenueGrowth = $this->calculateGrowthRate($previous->revenue, $product->revenue);
                $quantityGrowth = $this->calculateGrowthRate($previous->quantity_sold, $product->quantity_sold);
            } else {
                // New product in this period
                $revenueGrowth = $product->revenue > 0 ? 100 : 0;
                $quantityGrowth = $product->quantity_sold > 0 ? 100 : 0;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category_name' => $product->category_name,
                'price' => $product->price,
                'revenue' => $product->revenue,
                'quantity_sold' => $product->quantity_sold,
                'orders_count' => $product->orders_count,
                'avg_selling_price' => $product->avg_selling_price,
                'revenue_growth' => $revenueGrowth,
                'quantity_growth' => $quantityGrowth,
                'is_trending' => $revenueGrowth > 15, // Products with >15% growth
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $enhancedProducts,
                'period' => $period,
                'comparison_period' => [
                    'start' => $prevStart->format('Y-m-d'),
                    'end' => $prevEnd->format('Y-m-d'),
                ],
            ]
        ]);
    }

    /**
     * Get customer lifetime value analytics
     */
    public function getCustomerLifetimeValue(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
        ]);

        $period = $request->get('period', '1y');
        $end = Carbon::now();
        $start = match($period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subYear(),
        };

        $customerLTV = DB::table('orders')
            ->select([
                'customer_email',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as lifetime_value'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('MIN(created_at) as first_order'),
                DB::raw('MAX(created_at) as last_order'),
                DB::raw('EXTRACT(DAY FROM (MAX(created_at) - MIN(created_at))) as customer_lifespan_days')
            ])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('customer_email')
            ->havingRaw('COUNT(*) > 0')
            ->orderBy('lifetime_value', 'desc')
            ->get();

        // Calculate metrics
        $totalCustomers = $customerLTV->count();
        $totalRevenue = $customerLTV->sum('lifetime_value');
        $avgLTV = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
        
        // Segment customers by LTV
        $ltvSegments = [
            'high' => $customerLTV->where('lifetime_value', '>', 1000)->count(),
            'medium' => $customerLTV->whereBetween('lifetime_value', [500, 1000])->count(),
            'low' => $customerLTV->where('lifetime_value', '<', 500)->count(),
        ];

        // Top customers
        $topCustomers = $customerLTV->take(10)->map(function ($customer) {
            return [
                'email' => $customer->customer_email,
                'lifetime_value' => $customer->lifetime_value,
                'total_orders' => $customer->total_orders,
                'avg_order_value' => $customer->avg_order_value,
                'customer_since' => Carbon::parse($customer->first_order)->diffForHumans(),
                'last_order' => Carbon::parse($customer->last_order)->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'total_customers' => $totalCustomers,
                    'total_revenue' => $totalRevenue,
                    'avg_lifetime_value' => $avgLTV,
                    'avg_orders_per_customer' => $totalCustomers > 0 ? $customerLTV->avg('total_orders') : 0,
                ],
                'ltv_segments' => $ltvSegments,
                'top_customers' => $topCustomers,
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get real-time dashboard metrics
     */
    public function getRealTimeDashboard(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Today's metrics
        $todayMetrics = $this->getPeriodMetrics($today, Carbon::now());
        $yesterdayMetrics = $this->getPeriodMetrics($yesterday, $today);

        // Week metrics
        $thisWeekMetrics = $this->getPeriodMetrics($thisWeek, Carbon::now());
        $lastWeekMetrics = $this->getPeriodMetrics($lastWeek, $thisWeek);

        // Month metrics
        $thisMonthMetrics = $this->getPeriodMetrics($thisMonth, Carbon::now());
        $lastMonthMetrics = $this->getPeriodMetrics($lastMonth, $thisMonth);

        // Recent orders (last 24 hours)
        $recentOrders = Order::with(['orderItems.product'])
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->first_name . ' ' . $order->last_name,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at->diffForHumans(),
                    'items_count' => $order->orderItems->count(),
                ];
            });

        // Low stock alerts
        $lowStockProducts = Product::where('stock', '<=', 10)
            ->where('stock', '>', 0)
            ->with('category')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'current_stock' => $product->stock,
                    'price' => $product->price,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'metrics' => $todayMetrics,
                    'vs_yesterday' => [
                        'revenue_growth' => $this->calculateGrowthRate($yesterdayMetrics['revenue'], $todayMetrics['revenue']),
                        'orders_growth' => $this->calculateGrowthRate($yesterdayMetrics['orders'], $todayMetrics['orders']),
                    ]
                ],
                'this_week' => [
                    'metrics' => $thisWeekMetrics,
                    'vs_last_week' => [
                        'revenue_growth' => $this->calculateGrowthRate($lastWeekMetrics['revenue'], $thisWeekMetrics['revenue']),
                        'orders_growth' => $this->calculateGrowthRate($lastWeekMetrics['orders'], $thisWeekMetrics['orders']),
                    ]
                ],
                'this_month' => [
                    'metrics' => $thisMonthMetrics,
                    'vs_last_month' => [
                        'revenue_growth' => $this->calculateGrowthRate($lastMonthMetrics['revenue'], $thisMonthMetrics['revenue']),
                        'orders_growth' => $this->calculateGrowthRate($lastMonthMetrics['orders'], $thisMonthMetrics['orders']),
                    ]
                ],
                'recent_orders' => $recentOrders,
                'low_stock_alerts' => $lowStockProducts,
                'timestamp' => Carbon::now()->toISOString(),
            ]
        ]);
    }
}