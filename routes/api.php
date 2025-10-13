<?php
// In routes/api.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\CustomerPreOrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AdminNotificationController;
use Illuminate\Support\Facades\Route;

// Public (unprotected) routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-email', [AuthController::class, 'validateEmail']); // Real-time email validation
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');

// Category routes
Route::apiResource('categories', CategoryController::class);

// Product routes
Route::apiResource('products', ProductController::class);
Route::get('/products/featured/list', [ProductController::class, 'featured']); // Featured products
Route::get('/products/search/query', [ProductController::class, 'search']); // Product search with autocomplete
Route::get('/products/category/{categoryId}', [ProductController::class, 'byCategory']); // Products by category
Route::get('/products/category/by-slug/{slug}', [ProductController::class, 'byCategorySlug']); // Products by category slug

// Pre-order routes
Route::apiResource('pre-orders', PreOrderController::class);
Route::get('/pre-orders/categories/list', [PreOrderController::class, 'getCategories']); // Get categories for dropdown
Route::get('/pre-orders/search/query', [PreOrderController::class, 'search']); // Pre-order search
Route::get('/pre-orders/category/{categoryId}', [PreOrderController::class, 'byCategory']); // Pre-orders by category

// Customer Pre-order routes (Public - for customers to place pre-orders)
Route::get('/customer-pre-orders/available', [CustomerPreOrderController::class, 'index']); // Get available pre-orders for customers
Route::get('/customer-pre-orders/available/{preOrder}', [CustomerPreOrderController::class, 'show']); // Get specific pre-order details
Route::post('/customer-pre-orders/place', [CustomerPreOrderController::class, 'placePreOrder']); // Place a pre-order
Route::post('/customer-pre-orders/initialize-payment', [CustomerPreOrderController::class, 'initializePayment']); // Initialize payment
Route::post('/customer-pre-orders/verify-payment', [CustomerPreOrderController::class, 'verifyPayment']); // Verify payment

// NEW: Pre-order payment session routes (no DB records until payment success)
Route::post('/customer-pre-orders/initialize-payment-session', [CustomerPreOrderController::class, 'initializePaymentSession']);
Route::post('/customer-pre-orders/verify-payment-and-create', [CustomerPreOrderController::class, 'verifyPaymentAndCreatePreOrder']);

Route::post('/customer-pre-orders/customer-orders', [CustomerPreOrderController::class, 'getCustomerPreOrders']); // Get customer's pre-orders
Route::get('/customer-pre-orders/{preOrderNumber}', [CustomerPreOrderController::class, 'getCustomerPreOrder']); // Get specific customer pre-order

// New routes for remaining balance payment functionality
Route::get('/customer-pre-orders/{preOrderNumber}/amount-due', [CustomerPreOrderController::class, 'getAmountDue']); // Get amount due for CTA display
Route::get('/customer-pre-orders/{preOrderNumber}/pay-remaining', [CustomerPreOrderController::class, 'payRemainingDirectLink'])->name('customer-pre-orders.pay-remaining'); // Secure deep link for one-click payment (Option A)
Route::get('/customer-pre-orders/{preOrderNumber}/generate-token', [CustomerPreOrderController::class, 'generatePaymentToken']); // Generate payment token (Option B)
Route::get('/customer-pre-orders/pay-ticket/{token}', [CustomerPreOrderController::class, 'exchangePaymentToken']); // Exchange payment token (Option B)

// Debug endpoint for troubleshooting payment calculations
Route::get('/customer-pre-orders/{preOrderNumber}/debug', [CustomerPreOrderController::class, 'debugPreOrder']); // Debug pre-order amounts and calculations

// Promotion routes
Route::apiResource('promotions', PromotionController::class);
// Additional promotion routes
Route::post('/promotions/validate', [PromotionController::class, 'validatePromoCode']);
Route::post('/promotions/apply', [PromotionController::class, 'applyPromoCode']);
Route::get('/promotions/stats/statistics', [PromotionController::class, 'statistics']);
Route::patch('/promotions/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus']);

// Order and Payment routes
Route::get('/orders', [OrderController::class, 'index']); // List all orders for admin
Route::post('/orders/initialize-payment', [OrderController::class, 'initializePayment']);
Route::post('/orders/verify-payment', [OrderController::class, 'verifyPayment']);

// NEW: Payment session routes (no DB records until payment success)
Route::post('/orders/initialize-payment-session', [OrderController::class, 'initializePaymentSession']);
Route::post('/orders/verify-payment-and-create', [OrderController::class, 'verifyPaymentAndCreateOrder']);

Route::get('/orders/statistics', [OrderController::class, 'getStatistics']); // Order statistics
Route::get('/orders/export', [OrderController::class, 'exportOrders']); // Export orders
Route::get('/orders/by-number/{orderNumber}', [OrderController::class, 'getByOrderNumber']); // Get order by order number
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']); // Update order status
Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancelOrder']); // Cancel order
Route::post('/orders/customer-orders', [OrderController::class, 'getCustomerOrders']);

// Paystack webhook (should be unprotected)
Route::post('/paystack/webhook', [OrderController::class, 'handleWebhook']);

// Paystack webhook for customer pre-orders
Route::post('/paystack/pre-order-webhook', [CustomerPreOrderController::class, 'handleWebhook']);

// Payment Management routes
Route::get('/payments/dashboard', [PaymentController::class, 'getDashboardData']); // Payment dashboard stats
Route::get('/payments/transactions', [PaymentController::class, 'getRecentTransactions']); // Recent transactions with pagination
Route::get('/payments/stats', [PaymentController::class, 'getPaymentStats']); // Payment statistics for specific period
Route::get('/payments/methods', [PaymentController::class, 'getPaymentMethodBreakdown']); // Payment method breakdown
Route::get('/payments/search', [PaymentController::class, 'searchTransactions']); // Search transactions
Route::get('/payments/analytics', [PaymentController::class, 'getPaymentAnalytics']); // Payment analytics with revenue trends

// Customer routes
Route::get('/customers', [CustomerController::class, 'getAllCustomers']);

// Settings routes
Route::post('/settings', [SettingsController::class, 'saveSettings']);
// Add a route to get the latest settings
Route::get('/settings/latest', [SettingsController::class, 'getLatestSettings']);

// Admin routes
Route::post('/admins', [AdminController::class, 'addAdmin']);
Route::get('/admins', [AdminController::class, 'getAdminList']);
// Admin management routes
Route::put('/admins/{id}', [AdminController::class, 'editAdmin']);
Route::delete('/admins/{id}', [AdminController::class, 'deleteAdmin']);

// Admin login route
Route::post('/admins/login', [AdminController::class, 'login']);

// Admin customer pre-order management routes
Route::get('/admin/customer-pre-orders', [AdminController::class, 'getCustomerPreOrders']); // Get all customer pre-orders with filtering
Route::get('/admin/customer-pre-orders/{id}', [AdminController::class, 'getCustomerPreOrder']); // Get specific customer pre-order
Route::put('/admin/customer-pre-orders/{id}/status', [AdminController::class, 'updateCustomerPreOrderStatus']); // Update customer pre-order status
Route::put('/admin/customer-pre-orders/bulk-status', [AdminController::class, 'bulkUpdateCustomerPreOrderStatus']); // Bulk update statuses

// Admin notification routes
Route::prefix('admin/notifications')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index']); // Get all notifications with filters
    Route::get('/stats', [AdminNotificationController::class, 'getStats']); // Get notification statistics
    Route::get('/unread-count', [AdminNotificationController::class, 'getUnreadCount']); // Get unread count
    Route::get('/recent', [AdminNotificationController::class, 'getRecent']); // Get recent notifications for dropdown
    Route::get('/by-type', [AdminNotificationController::class, 'getByType']); // Get notifications grouped by type
    Route::get('/{notification}', [AdminNotificationController::class, 'show']); // Get specific notification
    
    Route::patch('/{notification}/mark-read', [AdminNotificationController::class, 'markAsRead']); // Mark single as read
    Route::patch('/{notification}/mark-unread', [AdminNotificationController::class, 'markAsUnread']); // Mark single as unread
    Route::patch('/mark-multiple-read', [AdminNotificationController::class, 'markMultipleAsRead']); // Mark multiple as read
    Route::patch('/mark-all-read', [AdminNotificationController::class, 'markAllAsRead']); // Mark all as read
    
    Route::delete('/{notification}', [AdminNotificationController::class, 'destroy']); // Delete single notification
    Route::delete('/delete-multiple', [AdminNotificationController::class, 'deleteMultiple']); // Delete multiple notifications
    Route::delete('/delete-old', [AdminNotificationController::class, 'deleteOld']); // Delete old notifications
    
    // Test endpoint for development
    Route::post('/test', [AdminNotificationController::class, 'createTestNotification']); // Create test notification
});

// Contact form routes
Route::post('/contact/submit', [ContactController::class, 'submitContactForm']);
Route::get('/contact/info', [ContactController::class, 'getContactInfo']);

// Dashboard routes
Route::get('/dashboard/overview', [DashboardController::class, 'getDashboardData']); // Complete dashboard data
Route::get('/dashboard/metrics', [DashboardController::class, 'getOverviewMetrics']); // Overview metrics only
Route::get('/dashboard/revenue-chart', [DashboardController::class, 'getRevenueChartData']); // Revenue chart data
Route::get('/dashboard/top-products', [DashboardController::class, 'getTopSellingProducts']); // Top selling products
Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders']); // Recent orders
Route::get('/dashboard/sales-by-category', [DashboardController::class, 'getSalesByCategoryData']); // Sales by category chart
Route::get('/dashboard/low-stock-alerts', [DashboardController::class, 'getLowStockAlertsData']); // Low stock alerts

// Enhanced dashboard routes for complete frontend support
Route::get('/dashboard/payment-dashboard', [DashboardController::class, 'getPaymentDashboardData']); // Payment dashboard data
Route::get('/dashboard/order-statistics', [DashboardController::class, 'getOrderStatisticsData']); // Order statistics
Route::get('/dashboard/recent-transactions', [DashboardController::class, 'getRecentTransactionsData']); // Recent transactions
Route::get('/dashboard/payment-methods', [DashboardController::class, 'getPaymentMethodBreakdownData']); // Payment method breakdown
Route::get('/dashboard/payment-stats', [DashboardController::class, 'getPaymentStatsData']); // Payment statistics
Route::get('/dashboard/preorder-metrics', [DashboardController::class, 'getPreorderMetricsData']); // Pre-order metrics
Route::get('/dashboard/customer-preorders', [DashboardController::class, 'getCustomerPreordersData']); // Customer pre-orders

// Reports & Analytics API routes
Route::prefix('reports')->group(function () {
    // Sales trend data for line charts
    Route::get('/sales-trend', [ReportsController::class, 'getSalesTrend']);
    
    // Sales by category for pie charts
    Route::get('/sales-by-category', [ReportsController::class, 'getSalesByCategory']);
    
    // Customer segments analysis
    Route::get('/customer-segments', [ReportsController::class, 'getCustomerSegments']);
    
    // Advanced customer segments (New, Returning, VIP)
    Route::get('/customer-segments/advanced', [ReportsController::class, 'getAdvancedCustomerSegments']);
    
    // Comprehensive analytics overview with growth metrics
    Route::get('/analytics-overview', [ReportsController::class, 'getAnalyticsOverview']);
    
    // Top performing products
    Route::get('/top-products', [ReportsController::class, 'getTopProducts']);
    
    // Enhanced product performance with growth tracking
    Route::get('/product-performance', [ReportsController::class, 'getEnhancedProductPerformance']);
    
    // Customer lifetime value analytics
    Route::get('/customer-ltv', [ReportsController::class, 'getCustomerLifetimeValue']);
    
    // Revenue performance metrics
    Route::get('/revenue-metrics', [ReportsController::class, 'getRevenueMetrics']);
    
    // Real-time dashboard data
    Route::get('/real-time-dashboard', [ReportsController::class, 'getRealTimeDashboard']);
    
    // Export analytics data (JSON/CSV)
    Route::get('/export', [ReportsController::class, 'exportAnalytics']);
});

// Inventory Management API routes
Route::prefix('inventory')->group(function () {
    // Overview and dashboard
    Route::get('/overview', [App\Http\Controllers\InventoryController::class, 'getInventoryOverview']); // Inventory dashboard overview
    Route::get('/stock-levels', [App\Http\Controllers\InventoryController::class, 'getStockLevels']); // Detailed stock levels with filtering
    Route::get('/low-stock-alerts', [App\Http\Controllers\InventoryController::class, 'getLowStockAlerts']); // Low stock alerts
    Route::get('/out-of-stock', [App\Http\Controllers\InventoryController::class, 'getOutOfStockItems']); // Out of stock items
    
    // Stock management
    Route::patch('/products/{product}/stock', [App\Http\Controllers\InventoryController::class, 'updateStock']); // Update single product stock
    Route::post('/bulk-update-stock', [App\Http\Controllers\InventoryController::class, 'bulkUpdateStock']); // Bulk stock updates
    
    // Analytics and reporting
    Route::get('/stats-by-category', [App\Http\Controllers\InventoryController::class, 'getInventoryStatsByCategory']); // Inventory stats by category
    Route::get('/movements', [App\Http\Controllers\InventoryController::class, 'getInventoryMovements']); // Stock movement history
    Route::get('/report', [App\Http\Controllers\InventoryController::class, 'generateInventoryReport']); // Generate inventory report
    
    // Reorder management
    Route::patch('/products/{product}/reorder-point', [App\Http\Controllers\InventoryController::class, 'setReorderPoint']); // Set reorder points
});

// User Profile and Order History routes (Protected - require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user/profile', [UserController::class, 'getProfile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    
    // User Orders
    Route::get('/user/orders/recent', [UserController::class, 'getRecentOrders']);
    Route::get('/user/orders/history', [UserController::class, 'getOrderHistory']);
    Route::get('/user/orders/{orderNumber}', [UserController::class, 'getOrderDetails']);
});

// ... (Your protected routes will go here)
