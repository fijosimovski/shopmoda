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
        // A promotion = rule (type + value) + targets (promotion_targets) + time window.
        // It applies automatically, the customer doesn't type anything (codes live in the coupons table).
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();             // its own page: /promo/{slug}
            $table->text('description')->nullable();
            $table->string('image')->nullable();          // banner for the homepage cover and the promo page

            // percent / fixed           = product-level: changes the shoe's price everywhere
            // nth_item / cart_threshold = cart-level: calculated in the cart from what's in it
            $table->string('type');
            $table->decimal('value', 10, 2);                      // 20 (= 20%) or 1000 (= 1.000 RSD)
            $table->unsignedTinyInteger('nth')->nullable();       // nth_item: 2 = "every 2nd pair"
            $table->decimal('min_cart_total', 10, 2)->nullable(); // cart_threshold: "spend at least X"

            $table->boolean('applies_to_all')->default(false);    // true = whole shop, targets are ignored
            $table->integer('priority')->default(0);              // higher wins when several match
            // product-level: may it combine with the product's own sale discount?
            // cart-level: may it combine with other cart-level promotions?
            $table->boolean('is_stackable')->default(false);

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // deleted_at: soft delete keeps the row, it just gets hidden

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // What a promotion covers. Polymorphic: one row points at a Product, Brand, Category or ProductModel.
        // "All Nike + category Running + 3 Adidas models" = 5 rows.
        Schema::create('promotion_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->morphs('targetable'); // targetable_type + targetable_id
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_targets');
        Schema::dropIfExists('promotions');
    }
};
