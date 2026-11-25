<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PageView;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Services\VisitStats;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    // Period filter: number of days => label. 0 = all time.
    public const PERIODS = [
        7 => 'Last 7 days',
        30 => 'Last 30 days',
        90 => 'Last 90 days',
        365 => 'Last 12 months',
        0 => 'All time',
    ];

    public function index(Request $request, VisitStats $visitStats)
    {
        $days = array_key_exists((int) $request->days, self::PERIODS) && $request->filled('days') ? (int) $request->days : 30;
        $from = $days ? now()->subDays($days - 1)->startOfDay() : null;

        // Today's visit totals are counted hourly by visits:aggregate; count them now so the page is up to date.
        $visitStats->aggregate(today());
        $timeline = $this->timeline($from, $days);

        // Same length period right before this one, for the "vs previous period" numbers.
        $prevFrom = $days ? $from->copy()->subDays($days) : null;

        $kpis = $this->kpis($from, null);
        $prevKpis = $days ? $this->kpis($prevFrom, $from) : null;

        return view('pages.admin.analytics', [
            'periods' => self::PERIODS,
            'days' => $days,
            'from' => $from,
            'kpis' => $kpis,
            'prevKpis' => $prevKpis,
            'statuses' => $this->statuses($from, Order::SALE_STATUSES),
            'other' => $this->otherOrders($from, $days),
            'timeline' => $timeline,
            'visits' => $this->visits($from, $timeline),
            'weekdays' => $this->weekdays($from),
            'categories' => $this->itemsGroupedBy($from, 'categories.name', fn ($q) => $q->join('categories', 'categories.id', '=', 'products.category_id')),
            'brands' => $this->itemsGroupedBy($from, 'brands.name', fn ($q) => $q->join('brands', 'brands.id', '=', 'products.brand_id')),
            'genders' => $this->itemsGroupedBy($from, 'products.gender'),
            'sizes' => $this->sizes($from),
            'topProducts' => $this->topProducts($from),
            'topCustomers' => $this->topCustomers($from),
            'topCities' => $this->topCities($from),
            'inventory' => $this->inventory(),
        ]);
    }

    // ---------- Queries ----------

    // Orders created in [from, to). Null = no limit on that side.
    private function orders($from, $to = null)
    {
        return Order::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to));
    }

    // Orders that count as sales: processing, shipped, completed (Order::SALE_STATUSES).
    // Unconfirmed, new, cancelled and expired orders have their own section, see otherOrders().
    private function sales($from, $to = null)
    {
        return $this->orders($from, $to)->sales();
    }

    // Sold order items joined with their order (sales only) and product.
    private function soldItems($from)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereNull('orders.deleted_at')
            ->whereIn('orders.status', Order::SALE_STATUSES)
            ->when($from, fn ($q) => $q->where('orders.created_at', '>=', $from));
    }

    private function kpis($from, $to): array
    {
        $salesCount = $this->sales($from, $to)->count();
        $revenue = (float) $this->sales($from, $to)->sum('total');
        $guestOrders = $this->sales($from, $to)->whereNull('user_id')->count();

        $itemsSold = (int) OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->whereIn('status', Order::SALE_STATUSES)
                ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<', $to));
        })->sum('quantity');

        // Customers (by email, so guests count too) and how many of them ordered more than once.
        $perCustomer = $this->sales($from, $to)->select('email', DB::raw('COUNT(*) as cnt'))->groupBy('email')->pluck('cnt');

        $newCustomers = User::whereHas('role', fn ($q) => $q->where('name', 'customer'))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->count();

        return [
            'revenue' => $revenue,
            'orders' => $salesCount,
            'avg_order' => $salesCount ? $revenue / $salesCount : 0,
            'items_sold' => $itemsSold,
            'guest_share' => $salesCount ? $guestOrders / $salesCount * 100 : 0,
            'buyers' => $perCustomer->count(),
            'repeat_rate' => $perCustomer->count() ? $perCustomer->filter(fn ($c) => $c > 1)->count() / $perCustomer->count() * 100 : 0,
            'new_customers' => $newCustomers,
        ];
    }

    // Count and value of orders per status. $statuses = Order::SALE_STATUSES or Order::NON_SALE_STATUSES.
    private function statuses($from, array $statuses): array
    {
        $rows = $this->orders($from)
            ->whereIn('status', $statuses)
            ->select('status', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $all = $rows->sum('orders');

        return collect($statuses)->map(fn ($status) => [
            'status' => $status,
            'orders' => (int) ($rows[$status]->orders ?? 0),
            'total' => (float) ($rows[$status]->total ?? 0),
            'share' => $all ? ($rows[$status]->orders ?? 0) / $all * 100 : 0,
        ])->all();
    }

    // Revenue and orders per day (short periods) or per month (12 months / all time). Empty days are filled with 0.
    private function timeline($from, int $days): array
    {
        $byMonth = $days === 0 || $days > 90;
        $format = $byMonth ? '%Y-%m' : '%Y-%m-%d';

        $rows = $this->sales($from)
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as bucket"), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $start = $from ?? ($rows->isNotEmpty() ? Carbon::parse($rows->keys()->first() . ($byMonth ? '-01' : '')) : now());
        $period = CarbonPeriod::create($byMonth ? $start->copy()->startOfMonth() : $start, $byMonth ? '1 month' : '1 day', now());

        $keys = $labels = $revenue = $orders = [];
        foreach ($period as $date) {
            $key = $date->format($byMonth ? 'Y-m' : 'Y-m-d');
            $keys[] = $key;
            $labels[] = $date->format($byMonth ? 'M Y' : 'd.m.');
            $revenue[] = round((float) ($rows[$key]->revenue ?? 0), 2);
            $orders[] = (int) ($rows[$key]->orders ?? 0);
        }

        // keys = the buckets (2026-11-23 or 2026-11), so other charts can use the same x-axis.
        return ['keys' => $keys, 'labels' => $labels, 'revenue' => $revenue, 'orders' => $orders, 'byMonth' => $byMonth];
    }

    // Orders per day of the week, Monday first.
    private function weekdays($from): array
    {
        // MySQL WEEKDAY(): 0 = Monday ... 6 = Sunday
        $rows = $this->sales($from)
            ->select(DB::raw('WEEKDAY(created_at) as day'), DB::raw('COUNT(*) as orders'))
            ->groupBy('day')
            ->pluck('orders', 'day');

        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return [
            'labels' => $names,
            'orders' => collect(range(0, 6))->map(fn ($d) => (int) ($rows[$d] ?? 0))->all(),
        ];
    }

    // Units and revenue of sold items grouped by one column (category, brand, gender...).
    private function itemsGroupedBy($from, string $column, ?callable $join = null)
    {
        $query = $this->soldItems($from);
        if ($join) {
            $join($query);
        }

        $rows = $query
            ->select("$column as label", DB::raw('SUM(order_items.quantity) as units'), DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue'))
            ->groupBy($column)
            ->orderByDesc('revenue')
            ->get();

        $total = $rows->sum('revenue');

        return $rows->map(fn ($r) => [
            'label' => ucfirst($r->label),
            'units' => (int) $r->units,
            'revenue' => (float) $r->revenue,
            'share' => $total ? $r->revenue / $total * 100 : 0,
        ]);
    }

    private function sizes($from)
    {
        return $this->soldItems($from)
            ->select('order_items.size', DB::raw('SUM(order_items.quantity) as units'))
            ->groupBy('order_items.size')
            ->orderBy('order_items.size')
            ->get()
            ->map(fn ($r) => ['size' => (float) $r->size, 'units' => (int) $r->units]);
    }

    private function topProducts($from)
    {
        return $this->soldItems($from)
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as units'), DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();
    }

    // Grouped by email, so guest buyers are included.
    private function topCustomers($from)
    {
        return $this->sales($from)
            ->select('email', DB::raw('MAX(name) as name'), DB::raw('MAX(user_id) as user_id'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as spent'))
            ->groupBy('email')
            ->orderByDesc('spent')
            ->take(10)
            ->get();
    }

    private function topCities($from)
    {
        return $this->sales($from)
            ->select('city', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->groupBy('city')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();
    }

    /**
     * Orders that are not sales: unconfirmed, new (confirmed, not handled yet), cancelled and expired.
     * They get their own section, so they don't change revenue or any other sales number.
     */
    private function otherOrders($from, int $days): array
    {
        $placed = $this->orders($from)->count();
        // Share of all placed orders (statuses() gives the share within these four only).
        $byStatus = collect($this->statuses($from, Order::NON_SALE_STATUSES))
            ->map(fn ($row) => ['share' => $placed ? $row['orders'] / $placed * 100 : 0] + $row)
            ->keyBy('status');

        // Share of placed orders the customer confirmed with the email code. Orders still waiting for
        // the code are left out, they can go either way. (Older orders have no confirmed_at, but any status
        // after "unconfirmed", except expired, means they were confirmed.)
        $decided = $placed - $byStatus[Order::STATUS_UNCONFIRMED]['orders'];
        $confirmed = $this->orders($from)
            ->where(fn ($q) => $q->whereNotNull('confirmed_at')
                ->orWhereIn('status', [Order::STATUS_NEW, ...Order::SALE_STATUSES]))
            ->count();

        // Same x-axis as the sales charts, one series per status.
        $timeline = $this->timeline($from, $days);
        $format = $timeline['byMonth'] ? '%Y-%m' : '%Y-%m-%d';
        $rows = $this->orders($from)
            ->whereIn('status', Order::NON_SALE_STATUSES)
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as bucket"), 'status', DB::raw('COUNT(*) as orders'))
            ->groupBy('bucket', 'status')
            ->get();
        $series = collect(Order::NON_SALE_STATUSES)->mapWithKeys(fn ($status) => [
            $status => array_map(fn ($key) => (int) $rows->where('bucket', $key)->where('status', $status)->sum('orders'), $timeline['keys']),
        ]);

        // Sneakers customers wanted but didn't end up buying (cancelled or expired orders).
        $lostProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereNull('orders.deleted_at')
            ->whereIn('orders.status', Order::RELEASED_STATUSES)
            ->when($from, fn ($q) => $q->where('orders.created_at', '>=', $from))
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as units'), DB::raw('SUM(order_items.quantity * order_items.unit_price) as value'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->take(8)
            ->get();

        $count = fn ($status) => $byStatus[$status]['orders'];
        $value = fn ($status) => $byStatus[$status]['total'];

        return [
            'statuses' => $byStatus->values()->all(),
            'placed' => $placed,
            'confirm_rate' => $decided > 0 ? min(100, $confirmed / $decided * 100) : 0,
            'cancel_rate' => $placed ? $count(Order::STATUS_CANCELLED) / $placed * 100 : 0,
            'expire_rate' => $placed ? $count(Order::STATUS_EXPIRED) / $placed * 100 : 0,
            'lost_value' => $value(Order::STATUS_CANCELLED) + $value(Order::STATUS_EXPIRED),
            'pending_value' => $value(Order::STATUS_UNCONFIRMED) + $value(Order::STATUS_NEW),
            'chart' => ['labels' => $timeline['labels'], 'series' => $series],
            'lost_products' => $lostProducts,
        ];
    }

    /**
     * Visitors. Totals, the chart and the products come from the daily tables (kept forever);
     * devices and referrers need the raw page_views, so they only go back PageView::KEEP_DAYS days.
     * A "visit" = one visitor on one day, so the same person on 3 days is 3 visits.
     */
    private function visits($from, array $timeline): array
    {
        $daily = DB::table('daily_visit_stats')->when($from, fn ($q) => $q->where('date', '>=', $from->toDateString()));

        $totals = (clone $daily)->selectRaw('SUM(views) as views, SUM(visitors) as visits, SUM(bot_requests) as bots')->first();
        $views = (int) $totals->views;
        $visits = (int) $totals->visits;
        // Orders from before tracking started have no visits to compare with, so they are left out.
        // Exact start time while the first day's raw rows still exist, else the first counted day.
        $firstDay = DB::table('daily_visit_stats')->min('date');
        $firstRow = PageView::min('created_at');
        $trackedSince = $firstRow && Carbon::parse($firstRow)->toDateString() === $firstDay ? Carbon::parse($firstRow) : ($firstDay ? Carbon::parse($firstDay) : null);
        $ordersFrom = $trackedSince ? $trackedSince->max($from ?? $trackedSince) : null;
        $placed = $ordersFrom ? $this->orders($ordersFrom)->count() : 0;

        // Same x-axis as the sales charts.
        $format = $timeline['byMonth'] ? '%Y-%m' : '%Y-%m-%d';
        $rows = (clone $daily)
            ->select(DB::raw("DATE_FORMAT(date, '$format') as bucket"), DB::raw('SUM(views) as views'), DB::raw('SUM(visitors) as visits'))
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        // Most viewed products, with how many pieces of them were sold in the same period.
        $products = DB::table('daily_product_views')
            ->join('products', 'products.id', '=', 'daily_product_views.product_id')
            ->when($from, fn ($q) => $q->where('daily_product_views.date', '>=', $from->toDateString()))
            ->select('products.id', 'products.name', DB::raw('SUM(daily_product_views.views) as views'), DB::raw('SUM(daily_product_views.visitors) as visits'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('views')
            ->take(10)
            ->get();
        $sold = $this->soldItems($from)
            ->whereIn('order_items.product_id', $products->pluck('id'))
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as units'))
            ->pluck('units', 'product_id');

        // Raw rows: only the last KEEP_DAYS days exist.
        $rawFrom = now()->subDays(PageView::KEEP_DAYS)->startOfDay();
        $rawFrom = $from && $from->gt($rawFrom) ? $from : $rawFrom;
        $raw = fn () => PageView::humanViews()->where('created_at', '>=', $rawFrom);

        $devices = $raw()->select('device', DB::raw('COUNT(*) as views'))->groupBy('device')->orderByDesc('views')->pluck('views', 'device');
        $deviceTotal = $devices->sum();

        // Host of the page the visitor came from (https://www.google.com/search?q=... -> www.google.com). Own site = not counted.
        $host = "SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '/', -1)";
        $referrers = $raw()
            ->whereNotNull('referrer')
            ->whereRaw("$host <> ?", [request()->getHttpHost()])
            ->select(DB::raw("$host as host"), DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_hash) as visitors'))
            ->groupBy('host')
            ->orderByDesc('views')
            ->take(8)
            ->get();

        return [
            'views' => $views,
            'visits' => $visits,
            'views_per_visit' => $visits ? $views / $visits : 0,
            'conversion' => $visits ? $placed / $visits * 100 : 0,
            'bots' => (int) $totals->bots,
            'chart' => [
                'labels' => $timeline['labels'],
                'views' => array_map(fn ($key) => (int) ($rows[$key]->views ?? 0), $timeline['keys']),
                'visits' => array_map(fn ($key) => (int) ($rows[$key]->visits ?? 0), $timeline['keys']),
            ],
            'products' => $products->map(fn ($p) => (object) [
                'id' => $p->id,
                'name' => $p->name,
                'views' => (int) $p->views,
                'visits' => (int) $p->visits,
                'sold' => (int) ($sold[$p->id] ?? 0),
            ]),
            'devices' => $devices->map(fn ($v, $device) => ['label' => ucfirst($device), 'views' => (int) $v, 'share' => $deviceTotal ? $v / $deviceTotal * 100 : 0])->values(),
            'referrers' => $referrers,
            'raw_from' => $rawFrom,
        ];
    }

    // Current state of the shop (not affected by the period filter).
    private function inventory(): array
    {
        $activeProducts = Product::active()->with('currentPrice')->get();
        $stockByProduct = ProductSize::whereIn('product_id', $activeProducts->pluck('id'))
            ->select('product_id', DB::raw('SUM(stock) as stock'))
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $everSold = OrderItem::whereHas('order', fn ($q) => $q->whereIn('status', Order::SALE_STATUSES))
            ->distinct()
            ->pluck('product_id');

        return [
            'products' => Product::count(),
            'active' => $activeProducts->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'featured' => $activeProducts->where('is_featured', true)->count(),
            'discounted' => $activeProducts->where('discount_percent', '>', 0)->count(),
            'stock_units' => (int) $stockByProduct->sum(),
            'stock_value' => $activeProducts->sum(fn ($p) => $p->finalPrice() * ($stockByProduct[$p->id] ?? 0)),
            'out_of_stock_sizes' => ProductSize::where('stock', 0)->whereHas('product', fn ($q) => $q->where('is_active', true))->count(),
            'no_stock_products' => $activeProducts->filter(fn ($p) => ($stockByProduct[$p->id] ?? 0) == 0)->count(),
            'never_sold' => $activeProducts->whereNotIn('id', $everSold)->count(),
            'customers' => User::whereHas('role', fn ($q) => $q->where('name', 'customer'))->count(),
        ];
    }
}
