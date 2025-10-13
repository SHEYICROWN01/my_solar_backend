<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AnalyticsTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing analytics
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Create categories if they don't exist
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and gadgets'],
            ['name' => 'Computing', 'slug' => 'computing', 'description' => 'Computers and computing accessories'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Various accessories and add-ons'],
            ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Audio equipment and devices'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(['slug' => $categoryData['slug']], $categoryData);
        }

        $categories = Category::all();

        // Create sample products
        $products = [];
        for ($i = 0; $i < 20; $i++) {
            $category = $categories->random();
            $product = Product::create([
                'name' => $faker->words(3, true) . ' ' . ucfirst($category->name),
                'category_id' => $category->id,
                'price' => $faker->randomFloat(2, 50, 2000),
                'stock' => $faker->numberBetween(10, 100),
                'description' => $faker->paragraph(),
                'power' => $faker->optional()->randomElement(['AC', 'Battery', 'USB']),
                'warranty' => $faker->optional()->randomElement(['1 Year', '2 Years', '6 Months']),
                'specifications' => [
                    'weight' => $faker->randomFloat(2, 0.1, 5) . 'kg',
                    'dimensions' => $faker->randomFloat(1, 10, 50) . 'x' . $faker->randomFloat(1, 10, 50) . 'cm',
                ],
            ]);
            $products[] = $product;
        }

        // Create sample orders over the past 6 months
        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now();
        
        $paymentMethods = ['paystack', 'bank_transfer', 'cash_on_delivery'];
        $statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
        
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            // Create 1-5 orders per day
            $ordersCount = $faker->numberBetween(1, 5);
            
            for ($j = 0; $j < $ordersCount; $j++) {
                $orderDate = $date->copy()->addHours($faker->numberBetween(0, 23))->addMinutes($faker->numberBetween(0, 59));
                
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper($faker->bothify('########')),
                    'customer_email' => $faker->email,
                    'customer_phone' => $faker->phoneNumber,
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'subtotal' => 0, // Will be calculated
                    'shipping_fee' => $faker->randomFloat(2, 0, 50),
                    'discount_amount' => $faker->randomFloat(2, 0, 100),
                    'total_amount' => 0, // Will be calculated
                    'currency' => 'NGN',
                    'status' => $faker->randomElement($statuses),
                    'payment_status' => $faker->randomElement(['pending', 'paid', 'failed']),
                    'fulfillment_method' => $faker->randomElement(['delivery', 'pickup']),
                    'shipping_address' => $faker->address,
                    'city' => $faker->city,
                    'state' => $faker->state,
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'paystack_reference' => $faker->uuid,
                    'paid_at' => $faker->boolean(80) ? $orderDate : null,
                    'delivered_at' => $faker->boolean(60) ? $orderDate->copy()->addDays($faker->numberBetween(1, 7)) : null,
                    'promo_code' => $faker->optional(0.3)->word,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                // Create 1-4 order items per order
                $itemsCount = $faker->numberBetween(1, 4);
                $subtotal = 0;
                
                for ($k = 0; $k < $itemsCount; $k++) {
                    $product = $faker->randomElement($products);
                    $quantity = $faker->numberBetween(1, 3);
                    $price = $product->price * $faker->randomFloat(2, 0.8, 1.2); // Small price variation
                    $totalPrice = $price * $quantity;
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'product_price' => $price,
                        'total_price' => $totalPrice,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                    
                    $subtotal += $totalPrice;
                }

                // Update order totals
                $totalAmount = $subtotal + $order->shipping_fee - $order->discount_amount;
                $order->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $totalAmount,
                ]);
            }
        }

        $this->command->info('Analytics test data created successfully!');
        $this->command->info('Created ' . Order::count() . ' orders with order items');
        $this->command->info('Created ' . Product::count() . ' products across ' . Category::count() . ' categories');
    }
}