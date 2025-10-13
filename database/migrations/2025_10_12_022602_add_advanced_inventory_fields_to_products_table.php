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
        Schema::table('products', function (Blueprint $table) {
            // Advanced inventory tracking fields
            $table->string('sku')->nullable()->unique()->after('name'); // Stock Keeping Unit
            $table->integer('reorder_point')->default(10)->after('stock'); // Minimum stock level before reordering
            $table->integer('reorder_quantity')->default(50)->after('reorder_point'); // Quantity to order when restocking
            $table->integer('max_stock')->default(100)->after('reorder_quantity'); // Maximum stock capacity
            $table->string('warehouse_location')->default('Warehouse A')->after('max_stock'); // Warehouse location
            $table->string('supplier')->nullable()->after('warehouse_location'); // Supplier information
            $table->decimal('cost_price', 10, 2)->nullable()->after('supplier'); // Cost price for profit margin calculations
            $table->boolean('track_inventory')->default(true)->after('cost_price'); // Whether to track inventory for this product
            $table->timestamp('last_restocked_at')->nullable()->after('track_inventory'); // Last restock date
            $table->text('inventory_notes')->nullable()->after('last_restocked_at'); // Inventory management notes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'reorder_point',
                'reorder_quantity',
                'max_stock',
                'warehouse_location',
                'supplier',
                'cost_price',
                'track_inventory',
                'last_restocked_at',
                'inventory_notes'
            ]);
        });
    }
};
