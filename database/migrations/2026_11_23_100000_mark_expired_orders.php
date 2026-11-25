<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Before the "expired" status existed, orders:expire set expired orders to "cancelled" and logged "order.expired".
     * Those orders get the new status. Stock and coupon were already released, so only the status changes.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'cancelled')
            ->whereIn('id', DB::table('user_logs')
                ->where('action', 'order.expired')
                ->where('subject_type', Order::class)
                ->select('subject_id'))
            ->update(['status' => 'expired']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->where('status', 'expired')->update(['status' => 'cancelled']);
    }
};
