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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // A brand or category can't be deleted while it still has products.
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('gender', ['men', 'women', 'unisex']);
            $table->unsignedTinyInteger('discount_percent')->default(0); // 0 - 100
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true); // hidden from the shop when false
            $table->timestamps();
            // Soft delete: a "deleted" product stays in the table,
            // so old orders that contain it still work.
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
