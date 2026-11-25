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
        // One row per action on the platform: login, logout, register,
        // order placed, product created / updated / deleted, order status changed...
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            // Empty for guests (e.g. a guest placing an order).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                 // e.g. "login", "order.created", "product.updated"
            $table->string('description')->nullable(); // readable text, e.g. "Order #15 placed"
            // What the action was done on (optional), e.g. subject_type = App\Models\Order, subject_id = 15
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable(); // 45 chars fits IPv6
            $table->timestamps();
            $table->softDeletes();

            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};
