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
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('pre_order_price', 12, 2);
            $table->decimal('deposit_percentage', 5, 2)->default(0);
            $table->string('expected_availability');
            $table->string('power_output')->nullable();
            $table->string('warranty_period')->nullable();
            $table->text('specifications')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_orders');
    }
};
