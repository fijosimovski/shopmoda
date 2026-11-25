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
        // true = only logged-in (registered) customers get the discount, guests don't.
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('members_only')->default(false)->after('first_order_only');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->boolean('members_only')->default(false)->after('is_stackable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('members_only');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('members_only');
        });
    }
};
