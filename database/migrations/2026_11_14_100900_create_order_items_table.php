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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('size', 4, 1);
            $table->unsignedInteger('quantity');
            // Price at the moment of ordering (after discount).
            // Later price changes don't affect old orders.
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
            $table->softDeletes(); // deleted_at: soft delete keeps the row, it just gets hidden
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
