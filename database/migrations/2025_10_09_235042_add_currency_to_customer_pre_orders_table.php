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
        Schema::table('customer_pre_orders', function (Blueprint $table) {
            // Only add timestamp columns if they don't exist
            if (!Schema::hasColumn('customer_pre_orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('fully_paid_at');
            }
            if (!Schema::hasColumn('customer_pre_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('ready_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_pre_orders', function (Blueprint $table) {
            $table->dropColumn(['ready_at', 'completed_at']);
        });
    }
};
