<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReportsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create categories
        $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $computing = Category::create(['name' => 'Computing', 'slug' => 'computing']);

        // Create products
        $product1 = Product::create([
            'name' => 'Wireless Headphones',
            'category_id' => $electronics->id,
            'price' => 299.99,
            'stock' => 50,
            'description' => 'Premium wireless headphones'
        ]);

        $product2 = Product::create([
            'name' => 'Gaming Mouse',
            'category_id' => $computing->id,
            'price' => 89.99,
            'stock' => 25,
            'description' => 'High-performance gaming mouse'
        ]);

        // Create orders with different dates for trend analysis
        $dates = [
            Carbon::now()->subDays(30),
            Carbon::now()->subDays(20),
            Carbon::now()->subDays(10),
            Carbon::now()->subDays(5),
            Carbon::now()->subDays(1),
        ];

        foreach ($dates as $index => $date) {
            $order = Order::create([
                'order_number' => 'ORD-TEST-' . ($index + 1),
                'customer_email' => "customer{$index}@example.com",
                'customer_phone' => '1234567890',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'subtotal' => 299.99,
                'shipping_fee' => 10.00,
                'discount_amount' => 0,
                'total_amount' => 309.99,
                'currency' => 'NGN',
                'status' => 'delivered',
                'payment_status' => 'paid',
                'fulfillment_method' => 'delivery',
                'payment_method' => 'paystack',
                'paid_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Create order items
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $index % 2 === 0 ? $product1->id : $product2->id,
                'product_name' => $index % 2 === 0 ? $product1->name : $product2->name,
                'quantity' => 1,
                'product_price' => $index % 2 === 0 ? 299.99 : 89.99,
                'total_price' => $index % 2 === 0 ? 299.99 : 89.99,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }

    public function test_sales_trend_endpoint()
    {
        $response = $this->getJson('/api/reports/sales-trend?period=30d');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'sales_trend' => [
                            '*' => ['period', 'revenue', 'orders', 'customers']
                        ],
                        'period',
                        'start_date',
                        'end_date'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_sales_by_category_endpoint()
    {
        $response = $this->getJson('/api/reports/sales-by-category?period=30d');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'categories' => [
                            '*' => [
                                'category_name',
                                'revenue',
                                'quantity_sold',
                                'orders_count',
                                'percentage'
                            ]
                        ],
                        'total_revenue',
                        'period'
                    ]
                ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data['categories']));
    }

    public function test_advanced_customer_segments_endpoint()
    {
        $response = $this->getJson('/api/reports/customer-segments/advanced?period=90d');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'segments' => [
                            'new',
                            'returning',
                            'vip'
                        ],
                        'percentages' => [
                            'new',
                            'returning',
                            'vip'
                        ],
                        'segment_details',
                        'total_customers',
                        'period'
                    ]
                ]);

        $segments = $response->json('data.segments');
        $this->assertArrayHasKey('new', $segments);
        $this->assertArrayHasKey('returning', $segments);
        $this->assertArrayHasKey('vip', $segments);
    }

    public function test_enhanced_product_performance_endpoint()
    {
        $response = $this->getJson('/api/reports/product-performance?period=30d&limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'products' => [
                            '*' => [
                                'id',
                                'name',
                                'category_name',
                                'price',
                                'revenue',
                                'quantity_sold',
                                'orders_count',
                                'avg_selling_price',
                                'revenue_growth',
                                'quantity_growth',
                                'is_trending'
                            ]
                        ],
                        'period',
                        'comparison_period'
                    ]
                ]);
    }

    public function test_customer_lifetime_value_endpoint()
    {
        $response = $this->getJson('/api/reports/customer-ltv?period=1y');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'metrics' => [
                            'total_customers',
                            'total_revenue',
                            'avg_lifetime_value',
                            'avg_orders_per_customer'
                        ],
                        'ltv_segments' => [
                            'high',
                            'medium',
                            'low'
                        ],
                        'top_customers',
                        'period'
                    ]
                ]);
    }

    public function test_real_time_dashboard_endpoint()
    {
        $response = $this->getJson('/api/reports/real-time-dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'today' => [
                            'metrics' => [
                                'revenue',
                                'orders',
                                'customers',
                                'avg_order_value'
                            ],
                            'vs_yesterday' => [
                                'revenue_growth',
                                'orders_growth'
                            ]
                        ],
                        'this_week' => [
                            'metrics',
                            'vs_last_week'
                        ],
                        'this_month' => [
                            'metrics',
                            'vs_last_month'
                        ],
                        'recent_orders',
                        'low_stock_alerts',
                        'timestamp'
                    ]
                ]);
    }

    public function test_analytics_overview_endpoint()
    {
        $response = $this->getJson('/api/reports/analytics-overview?period=30d');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'current' => [
                            'revenue',
                            'orders',
                            'customers',
                            'avg_order_value'
                        ],
                        'previous' => [
                            'revenue',
                            'orders',
                            'customers',
                            'avg_order_value'
                        ],
                        'growth' => [
                            'revenue',
                            'orders',
                            'customers',
                            'avg_order_value'
                        ],
                        'period'
                    ]
                ]);
    }

    public function test_export_analytics_endpoint()
    {
        $response = $this->getJson('/api/reports/export?type=sales_trend&period=30d&format=json');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'exported_at'
                ]);
    }

    public function test_export_analytics_csv_format()
    {
        $response = $this->getJson('/api/reports/export?type=sales_trend&period=30d&format=csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_invalid_period_validation()
    {
        $response = $this->getJson('/api/reports/sales-trend?period=invalid');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['period']);
    }

    public function test_custom_date_range()
    {
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $response = $this->getJson("/api/reports/sales-trend?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($startDate, $data['start_date']);
        $this->assertEquals($endDate, $data['end_date']);
    }
}