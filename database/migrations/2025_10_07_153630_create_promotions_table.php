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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Promotion Name
            $table->string('promo_code')->unique(); // Promo Code
            $table->enum('discount_type', ['percentage', 'fixed']); // Discount Type
            $table->decimal('discount_value', 10, 2); // Discount Value
            $table->date('start_date'); // Start Date
            $table->date('end_date'); // End Date
            $table->integer('usage_limit')->nullable(); // Usage Limit
            $table->decimal('minimum_order_amount', 10, 2)->nullable(); // Minimum Order Amount
            $table->text('description')->nullable(); // Description
            $table->integer('used_count')->default(0); // Track how many times used
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
