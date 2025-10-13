# Reports & Analytics API Documentation

## Overview
The Reports & Analytics API provides comprehensive insights and performance metrics for your e-commerce platform. It includes sales trends, customer segmentation, product performance, and revenue analytics with support for various time periods and data export capabilities.

## Base URL
```
/api/reports
```

## Available Endpoints

### 1. Sales Trend Analysis
**GET** `/sales-trend`

Get sales trend data for line charts showing revenue, orders, and customer metrics over time.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)
- `start_date` (optional): Custom start date (YYYY-MM-DD)
- `end_date` (optional): Custom end date (YYYY-MM-DD)

**Example Request:**
```bash
GET /api/reports/sales-trend?period=30d
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "sales_trend": [
      {
        "period": "2025-01-01",
        "revenue": 25430.50,
        "orders": 45,
        "customers": 38
      }
    ],
    "period": "30d",
    "start_date": "2025-09-08",
    "end_date": "2025-10-08"
  }
}
```

### 2. Sales by Category
**GET** `/sales-by-category`

Get sales breakdown by product categories for pie charts and category performance analysis.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)
- `start_date` (optional): Custom start date
- `end_date` (optional): Custom end date

**Example Response:**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "category_name": "Electronics",
        "revenue": 45230.75,
        "quantity_sold": 156,
        "orders_count": 89,
        "percentage": 32.1
      }
    ],
    "total_revenue": 141000.50,
    "period": "30d"
  }
}
```

### 3. Customer Segments Analysis
**GET** `/customer-segments`

Analyze customer behavior patterns based on purchase value and frequency.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `90d`)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "segments": {
      "high_value": 23,
      "medium_value": 156,
      "low_value": 234,
      "frequent": 45,
      "occasional": 187,
      "one_time": 181
    },
    "total_customers": 413,
    "period": "90d"
  }
}
```

### 4. Analytics Overview
**GET** `/analytics-overview`

Get comprehensive analytics with current vs previous period comparison and growth rates.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "current": {
      "revenue": 125430.50,
      "orders": 245,
      "customers": 189,
      "avg_order_value": 512.37
    },
    "previous": {
      "revenue": 98750.25,
      "orders": 198,
      "customers": 156,
      "avg_order_value": 498.74
    },
    "growth": {
      "revenue": 27.02,
      "orders": 23.74,
      "customers": 21.15,
      "avg_order_value": 2.73
    },
    "period": "30d"
  }
}
```

### 5. Top Products Performance
**GET** `/top-products`

Get top-performing products based on revenue, quantity sold, or order count.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)
- `limit` (optional): Number of products to return (5-50, default: 10)
- `sort_by` (optional): `revenue`, `quantity`, `orders` (default: `revenue`)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 15,
        "name": "Premium Wireless Headphones",
        "category_name": "Electronics",
        "price": 299.99,
        "revenue": 8999.70,
        "quantity_sold": 30,
        "orders_count": 28,
        "avg_selling_price": 299.99
      }
    ],
    "period": "30d",
    "sort_by": "revenue"
  }
}
```

### 6. Revenue Metrics
**GET** `/revenue-metrics`

Get detailed revenue breakdown including subtotal, shipping, discounts, and payment methods.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "total_revenue": 125430.50,
      "total_subtotal": 118650.25,
      "total_shipping": 8750.00,
      "total_discounts": 1969.75,
      "avg_order_value": 512.37,
      "total_orders": 245,
      "unique_customers": 189
    },
    "payment_methods": [
      {
        "payment_method": "paystack",
        "count": 178,
        "revenue": 89234.75
      }
    ],
    "period": "30d"
  }
}
```

### 7. Export Analytics Data
**GET** `/export`

Export analytics data in JSON or CSV format for external analysis.

**Parameters:**
- `type` (required): `sales_trend`, `category_sales`, `customer_segments`, `top_products`
- `period` (optional): `7d`, `30d`, `90d`, `1y`
- `format` (optional): `json`, `csv` (default: `json`)

**Example Request:**
```bash
GET /api/reports/export?type=sales_trend&period=30d&format=csv
```

### 8. Advanced Customer Segments (New, Returning, VIP)
**GET** `/customer-segments/advanced`

Analyze customers based on purchase behavior and value, categorizing them as New, Returning, or VIP customers.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `90d`)

**Customer Categorization:**
- **New**: Customers with their first order within the specified period
- **Returning**: Customers with multiple orders but moderate spending
- **VIP**: High-value customers (>$1000 total spent OR >5 orders)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "segments": {
      "new": 45,
      "returning": 123,
      "vip": 32
    },
    "percentages": {
      "new": 22.5,
      "returning": 61.5,
      "vip": 16.0
    },
    "segment_details": {
      "new": [
        {
          "email": "newcustomer@example.com",
          "orders": 1,
          "total_spent": 299.99,
          "first_order": "2025-09-15"
        }
      ],
      "vip": [
        {
          "email": "vip@example.com",
          "orders": 8,
          "total_spent": 2450.75,
          "avg_order_value": 306.34
        }
      ]
    },
    "total_customers": 200,
    "period": "90d"
  }
}
```

### 9. Enhanced Product Performance
**GET** `/product-performance`

Get detailed product performance analytics with growth tracking and trending indicators.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `30d`)
- `limit` (optional): Number of products to return (5-50, default: 10)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 15,
        "name": "Wireless Headphones",
        "category_name": "Electronics",
        "price": 299.99,
        "revenue": 49360.00,
        "quantity_sold": 1234,
        "orders_count": 987,
        "avg_selling_price": 299.99,
        "revenue_growth": 23.5,
        "quantity_growth": 18.2,
        "is_trending": true
      }
    ],
    "period": "30d",
    "comparison_period": {
      "start": "2025-08-08",
      "end": "2025-09-08"
    }
  }
}
```

### 10. Customer Lifetime Value Analytics
**GET** `/customer-ltv`

Analyze customer lifetime value with segmentation and top customer insights.

**Parameters:**
- `period` (optional): `7d`, `30d`, `90d`, `1y` (default: `1y`)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "total_customers": 1250,
      "total_revenue": 875430.50,
      "avg_lifetime_value": 700.34,
      "avg_orders_per_customer": 3.2
    },
    "ltv_segments": {
      "high": 125,
      "medium": 456,
      "low": 669
    },
    "top_customers": [
      {
        "email": "topbuyer@example.com",
        "lifetime_value": 4567.89,
        "total_orders": 15,
        "avg_order_value": 304.53,
        "customer_since": "2 years ago",
        "last_order": "3 days ago"
      }
    ],
    "period": "1y"
  }
}
```

### 11. Real-Time Dashboard
**GET** `/real-time-dashboard`

Get comprehensive real-time metrics comparing today, this week, and this month with previous periods.

**Parameters:** None

**Example Response:**
```json
{
  "success": true,
  "data": {
    "today": {
      "metrics": {
        "revenue": 12450.75,
        "orders": 24,
        "customers": 21,
        "avg_order_value": 518.78
      },
      "vs_yesterday": {
        "revenue_growth": 15.3,
        "orders_growth": 9.1
      }
    },
    "this_week": {
      "metrics": {
        "revenue": 89250.40,
        "orders": 167,
        "customers": 134,
        "avg_order_value": 534.43
      },
      "vs_last_week": {
        "revenue_growth": 8.7,
        "orders_growth": 12.4
      }
    },
    "this_month": {
      "metrics": {
        "revenue": 345670.80,
        "orders": 689,
        "customers": 542,
        "avg_order_value": 501.68
      },
      "vs_last_month": {
        "revenue_growth": 23.1,
        "orders_growth": 18.9
      }
    },
    "recent_orders": [
      {
        "order_number": "ORD-ABC12345",
        "customer_name": "John Doe",
        "total_amount": 299.99,
        "status": "paid",
        "payment_status": "paid",
        "created_at": "5 minutes ago",
        "items_count": 2
      }
    ],
    "low_stock_alerts": [
      {
        "id": 15,
        "name": "Gaming Mouse Pro",
        "category": "Computing",
        "current_stock": 3,
        "price": 89.99
      }
    ],
    "timestamp": "2025-10-08T14:30:00.000000Z"
  }
}
```

## Usage Examples

### Frontend Integration
```javascript
// Fetch sales trend data for charts
const fetchSalesTrend = async (period = '30d') => {
  const response = await fetch(`/api/reports/sales-trend?period=${period}`);
  const data = await response.json();
  return data.data.sales_trend;
};

// Get category breakdown for pie chart
const fetchCategoryData = async () => {
  const response = await fetch('/api/reports/sales-by-category?period=30d');
  const data = await response.json();
  return data.data.categories;
};

// Get analytics overview with growth metrics
const fetchAnalyticsOverview = async () => {
  const response = await fetch('/api/reports/analytics-overview?period=30d');
  return response.json();
};
```

### Excel Integration
```javascript
// Export and download CSV data
const exportAnalytics = async (type, period = '30d') => {
  const url = `/api/reports/export?type=${type}&period=${period}&format=csv`;
  const link = document.createElement('a');
  link.href = url;
  link.download = `analytics_${type}_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
};
```

## Data Visualization Recommendations

### Charts That Match Your Dashboard
Based on your dashboard image, here are the recommended chart implementations:

1. **Sales Trend Line Chart**: Use `/sales-trend` endpoint
   - X-axis: Time periods (dates)
   - Y-axis: Revenue and Orders count
   - Multiple lines for Revenue and Orders

2. **Sales by Category Pie Chart**: Use `/sales-by-category` endpoint
   - Each slice represents category percentage
   - Colors can match your brand (Electronics: orange, etc.)

3. **Customer Segments Bar Chart**: Use `/customer-segments` endpoint
   - X-axis: Customer segment types
   - Y-axis: Number of customers
   - Different colors for value vs frequency segments

## Error Handling
All endpoints return consistent error responses:
```json
{
  "success": false,
  "message": "Validation error message",
  "errors": {
    "period": ["Invalid period specified"]
  }
}
```

## Rate Limiting
Consider implementing rate limiting for analytics endpoints as they can be resource-intensive:
- Recommended: 60 requests per minute per IP
- Export endpoints: 10 requests per minute per IP

## Performance Tips
1. Use appropriate time periods to balance data granularity with performance
2. Cache frequently accessed analytics data
3. Use the export functionality for large dataset analysis
4. Consider pagination for top products when using large limits

## Advanced Usage Examples

### Dashboard Integration (Matching Your Image)
```javascript
// Get customer segments for the bar chart (New, Returning, VIP)
const fetchCustomerSegments = async () => {
  const response = await fetch('/api/reports/customer-segments/advanced?period=90d');
  const data = await response.json();
  
  // Perfect for horizontal bar chart
  return {
    labels: ['New', 'Returning', 'VIP'],
    data: [
      data.data.segments.new,
      data.data.segments.returning,
      data.data.segments.vip
    ],
    percentages: data.data.percentages
  };
};

// Get enhanced product performance with growth indicators
const fetchProductPerformance = async () => {
  const response = await fetch('/api/reports/product-performance?period=30d&limit=10');
  const data = await response.json();
  
  return data.data.products.map(product => ({
    name: product.name,
    revenue: product.revenue,
    units_sold: product.quantity_sold,
    growth: product.revenue_growth,
    trending: product.is_trending
  }));
};

// Real-time dashboard updates
const updateDashboard = async () => {
  const response = await fetch('/api/reports/real-time-dashboard');
  const data = await response.json();
  
  // Update KPI cards
  updateKPICard('today-revenue', data.data.today.metrics.revenue);
  updateKPICard('today-orders', data.data.today.metrics.orders);
  updateGrowthIndicator('revenue-growth', data.data.today.vs_yesterday.revenue_growth);
  
  // Update recent orders list
  updateRecentOrders(data.data.recent_orders);
  
  // Update low stock alerts
  updateLowStockAlerts(data.data.low_stock_alerts);
};

// Set up real-time updates every 30 seconds
setInterval(updateDashboard, 30000);
```

### Power BI / Tableau Integration
```javascript
// Export comprehensive data for external BI tools
const exportForBI = async () => {
  const endpoints = [
    'sales-trend',
    'category_sales', 
    'customer_segments',
    'top_products'
  ];
  
  for (const endpoint of endpoints) {
    const url = `/api/reports/export?type=${endpoint}&period=1y&format=csv`;
    await downloadFile(url, `analytics_${endpoint}_${new Date().toISOString().split('T')[0]}.csv`);
  }
};
```

### Customer Segmentation Marketing
```javascript
// Get VIP customers for targeted campaigns
const getVIPCustomers = async () => {
  const response = await fetch('/api/reports/customer-segments/advanced?period=1y');
  const data = await response.json();
  
  return data.data.segment_details.vip.map(customer => ({
    email: customer.email,
    lifetime_value: customer.total_spent,
    avg_order_value: customer.avg_order_value,
    segment: 'VIP'
  }));
};
```

## Performance Optimization Tips

### 1. Caching Strategy
```php
// Add to your controller methods
use Illuminate\Support\Facades\Cache;

public function getSalesTrend(Request $request): JsonResponse
{
    $cacheKey = 'sales_trend_' . md5(serialize($request->all()));
    
    return Cache::remember($cacheKey, 300, function () use ($request) {
        // Your existing logic here
    });
}
```

### 2. Database Indexing
```sql
-- Add these indexes for better performance
CREATE INDEX idx_orders_payment_status_created_at ON orders(payment_status, created_at);
CREATE INDEX idx_order_items_order_product ON order_items(order_id, product_id);
CREATE INDEX idx_products_category_stock ON products(category_id, stock);
```

### 3. Pagination for Large Datasets
```javascript
// For top products with pagination
const fetchTopProductsPaginated = async (page = 1, limit = 50) => {
  const response = await fetch(`/api/reports/top-products?period=30d&limit=${limit}&page=${page}`);
  return response.json();
};
```

## Testing Your Analytics

Run the comprehensive test suite to validate all endpoints:

```bash
php artisan test tests/Feature/ReportsAnalyticsTest.php
```

This will test:
- ✅ All endpoint responses and data structures
- ✅ Growth rate calculations
- ✅ Customer segmentation logic
- ✅ Data export functionality
- ✅ Input validation
- ✅ Custom date range handling