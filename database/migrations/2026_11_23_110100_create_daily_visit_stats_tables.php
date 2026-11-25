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
        // Totals per day, counted from page_views by the visits:aggregate command (App\Services\VisitStats).
        // Kept forever, so the Analytics charts still work after the raw page_views rows are deleted.
        Schema::create('daily_visit_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('views')->default(0);        // pages people opened (GET, no bots, no errors)
            $table->unsignedInteger('visitors')->default(0);     // different people who opened them
            $table->unsignedInteger('bot_requests')->default(0); // everything bots and scripts requested
            $table->unsignedInteger('not_found')->default(0);    // 404 responses
            $table->unsignedInteger('server_errors')->default(0); // 5xx responses
            $table->timestamps();
        });

        Schema::create('daily_product_views', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->timestamps();

            $table->unique(['date', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_product_views');
        Schema::dropIfExists('daily_visit_stats');
    }
};
