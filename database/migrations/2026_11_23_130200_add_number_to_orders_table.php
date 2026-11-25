<?php

use App\Models\Order;
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
        // Order number shown to customers and in emails (e.g. "SM-7K3QX9P2"), so the database id
        // (and with it how many orders the shop gets) isn't visible. Made in Order::booted().
        Schema::table('orders', function (Blueprint $table) {
            $table->string('number', 20)->nullable()->unique()->after('id');
        });

        // Existing orders get a number too.
        DB::table('orders')->whereNull('number')->orderBy('id')->pluck('id')->each(function ($id) {
            DB::table('orders')->where('id', $id)->update(['number' => Order::newNumber()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->dropColumn('number');
        });
    }
};
