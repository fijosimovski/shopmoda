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
        // A code the customer types in at checkout.
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // always saved in UPPERCASE, so lookups are case-insensitive
            $table->string('description')->nullable();    // admin note, e.g. "Instagram giveaway"
            $table->string('type');                       // percent / fixed
            $table->decimal('value', 10, 2);              // 10 (= 10%) or 1000 (= 1.000 RSD)
            $table->decimal('min_order_total', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();   // cap for percent coupons

            $table->unsignedInteger('usage_limit')->nullable();              // total uses, null = unlimited, 1 = single-use
            $table->unsignedInteger('usage_limit_per_customer')->nullable(); // counted by email, so guests count too
            $table->boolean('first_order_only')->default(false);
            // true = only full-price items count (not ones already discounted by a sale or a promotion)
            $table->boolean('exclude_sale_items')->default(false);
            $table->boolean('applies_to_all')->default(true);   // false = only the coupon_targets below

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Optional scope: only these brands / categories / models / products.
        Schema::create('coupon_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->morphs('targetable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_targets');
        Schema::dropIfExists('coupons');
    }
};
