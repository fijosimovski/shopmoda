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
        // One row per request to the site (pages, form posts, 404s, bots...), written by TrackPageViews.
        // Rows older than PageView::KEEP_DAYS are deleted every night (model:prune); the daily totals
        // in daily_visit_stats / daily_product_views are kept forever.
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            // Empty for guests.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable(); // 45 chars fits IPv6
            // sha256 of IP + browser: one visitor, used to count unique visitors.
            $table->char('visitor_hash', 64);
            $table->string('method', 8);
            $table->string('path');
            $table->string('query', 500)->nullable(); // without secrets (signature, token...)
            $table->string('route_name')->nullable(); // null = no such page (404)
            // Filled on product pages, for "most viewed products".
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('status');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device', 10); // desktop, mobile, tablet, bot
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['ip_address', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
