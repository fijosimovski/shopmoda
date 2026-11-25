<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // subtotal (items) - discount_total (cart promotions + coupon) = total (what the customer pays)
            $table->decimal('subtotal', 10, 2)->default(0)->after('note');
            $table->decimal('discount_total', 10, 2)->default(0)->after('subtotal');
            // Copied onto the order, like prices, so editing / deleting the coupon later changes nothing here.
            $table->string('coupon_code')->nullable()->after('discount_total');
        });

        // Old orders had no discounts: subtotal = total.
        DB::table('orders')->update(['subtotal' => DB::raw('total')]);

        // Snapshot of every cart-level discount (promotion or coupon) applied at checkout.
        // Like unit_price in order_items: later changes to the promotion don't change old orders.
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                       // e.g. "2nd pair -20%" or "Coupon WELCOME10"
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        // One row per order that used a coupon. It counts towards the usage limits.
        // Soft-deleted (= released) when the order is cancelled or expires unconfirmed.
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');                      // lowercase; the per-customer limit is counted by this
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['coupon_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('order_discounts');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_total', 'coupon_code']);
        });
    }
};
