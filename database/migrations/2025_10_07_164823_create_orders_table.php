<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            
            // Customer Information
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('first_name');
            $table->string('last_name');
            
            // Order Details
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            
            // Order Status
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Fulfillment
            $table->enum('fulfillment_method', ['delivery', 'pickup'])->default('delivery');
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pickup_location')->nullable();
            
            // Payment Information
            $table->string('payment_method')->nullable();
            $table->string('paystack_reference')->nullable()->unique();
            $table->string('paystack_access_code')->nullable();
            $table->json('paystack_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Promotion
            $table->string('promo_code')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_email', 'status']);
            $table->index('paystack_reference');
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
