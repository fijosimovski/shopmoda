<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('status'); // 45 chars fits IPv6
        });

        // Fill in older orders from their "order.created" log row, which already has the IP.
        // MySQL only: SQLite (the tests' in-memory database) can't UPDATE with a JOIN, and is empty there anyway.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('orders')
            ->join('user_logs', function ($join) {
                $join->on('user_logs.subject_id', '=', 'orders.id')
                    ->where('user_logs.subject_type', 'App\\Models\\Order')
                    ->where('user_logs.action', 'order.created');
            })
            ->whereNull('orders.ip_address')
            ->update(['orders.ip_address' => DB::raw('user_logs.ip_address')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
