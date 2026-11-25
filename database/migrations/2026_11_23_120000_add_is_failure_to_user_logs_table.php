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
        // true = something went wrong or was refused (wrong password, access denied, mail not sent...).
        // Written with UserLog::recordFailure().
        Schema::table('user_logs', function (Blueprint $table) {
            $table->boolean('is_failure')->default(false)->after('description');
            $table->index(['is_failure', 'created_at']);
            $table->index('created_at'); // for the cleanup of old logs
        });

        // Mail failures were already logged before this column existed.
        DB::table('user_logs')->whereIn('action', ['order.code_mail', 'order.mail', 'order.confirmation'])->update(['is_failure' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropIndex(['is_failure', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('is_failure');
        });
    }
};
