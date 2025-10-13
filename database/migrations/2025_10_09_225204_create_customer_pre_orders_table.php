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
        Schema::create('customer_pre_orders', function (Blueprint $table) {
            $table->id();
            $table->string('pre_order_number')->unique();
            $table->foreignId('pre_order_id')->constrained('pre_orders')->onDelete('cascade');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('first_name');
            $table->string('last_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('deposit_amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'deposit_paid', 'fully_paid', 'cancelled', 'ready_for_pickup', 'completed'])->default('pending');
            $table->enum('payment_status', ['pending', 'deposit_paid', 'fully_paid', 'failed'])->default('pending');
            $table->enum('fulfillment_method', ['pickup', 'delivery']);
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('paystack_reference')->nullable();
            $table->string('paystack_access_code')->nullable();
            $table->json('paystack_response')->nullable();
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('fully_paid_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_pre_orders');
    }
};
