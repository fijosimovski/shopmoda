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
            // Empty for guest orders. If a user is deleted, their orders stay.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Customer details are copied into the order,
            // so the order doesn't change if the user edits their profile later.
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->string('city');
            $table->text('note')->nullable();

            $table->decimal('total', 10, 2);
            $table->string('status')->default('new'); // new, processing, shipped, completed, cancelled
            $table->timestamps();
            $table->softDeletes(); // deleted_at: soft delete keeps the row, it just gets hidden
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
