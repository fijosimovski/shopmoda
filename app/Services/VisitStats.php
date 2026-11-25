<?php

namespace App\Services;

use App\Models\PageView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Counts the raw page_views of one day into the daily tables (daily_visit_stats, daily_product_views).
 * Running it again for the same day just overwrites that day, so it is safe to run often.
 * Called by the `visits:aggregate` command (every hour) and by the admin Analytics page for today.
 */
class VisitStats
{
    public function aggregate(Carbon $day): void
    {
        $from = $day->copy()->startOfDay();
        $to = $from->copy()->addDay();
        $date = $from->toDateString();

        $rows = PageView::whereBetween('created_at', [$from, $to->copy()->subSecond()]);

        // Nothing saved for that day (e.g. older than KEEP_DAYS and already deleted): keep the old totals.
        if (!(clone $rows)->exists()) {
            return;
        }

        $views = (clone $rows)->humanViews()
            ->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->first();

        DB::table('daily_visit_stats')->upsert([[
            'date' => $date,
            'views' => (int) $views->views,
            'visitors' => (int) $views->visitors,
            'bot_requests' => (clone $rows)->where('device', 'bot')->count(),
            'not_found' => (clone $rows)->where('status', 404)->count(),
            'server_errors' => (clone $rows)->where('status', '>=', 500)->count(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['date'], ['views', 'visitors', 'bot_requests', 'not_found', 'server_errors', 'updated_at']);

        $products = (clone $rows)->humanViews()
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->get();

        DB::transaction(function () use ($date, $products) {
            DB::table('daily_product_views')->where('date', $date)->delete();
            DB::table('daily_product_views')->insert($products->map(fn ($p) => [
                'date' => $date,
                'product_id' => $p->product_id,
                'views' => (int) $p->views,
                'visitors' => (int) $p->visitors,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });
    }
}
