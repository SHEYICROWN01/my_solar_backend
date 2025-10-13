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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // registration, order, pre_order, payment, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Store additional data like IDs, amounts, etc.
            $table->string('action_url')->nullable(); // Where to redirect when clicked
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('related_id')->nullable(); // ID of related record (user, order, etc.)
            $table->string('related_type')->nullable(); // Type of related record
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('icon')->nullable(); // Icon for the notification
            $table->timestamps();
            
            $table->index(['is_read', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
