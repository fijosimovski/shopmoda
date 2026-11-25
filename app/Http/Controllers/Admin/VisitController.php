<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    // Status filter: label => [from, to] (inclusive).
    public const STATUS_GROUPS = [
        '2xx' => [200, 299],
        '3xx' => [300, 399],
        '4xx' => [400, 499],
        '5xx' => [500, 599],
    ];

    // Rows per page in "All requests" (first = default).
    public const PER_PAGE = [25, 50, 100];

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:45',
            'device' => 'nullable|in:' . implode(',', PageView::DEVICES),
            'status' => 'nullable|in:' . implode(',', array_keys(self::STATUS_GROUPS)) . ',404',
            'method' => 'nullable|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'date' => 'nullable|date_format:Y-m-d',
            'user' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|in:' . implode(',', self::PER_PAGE),
        ]);

        $views = PageView::with('user')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('path', 'like', '%' . $request->input('q') . '%')
                ->orWhere('query', 'like', '%' . $request->input('q') . '%')
                ->orWhere('user_agent', 'like', '%' . $request->input('q') . '%')))
            ->when($request->filled('ip'), fn ($q) => $q->where('ip_address', $request->input('ip')))
            ->when($request->filled('device'), fn ($q) => $q->where('device', $request->input('device')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->input('method')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->withTrashed()->where('email', 'like', '%' . $request->input('user') . '%')))
            ->when($request->input('status') === '404', fn ($q) => $q->where('status', 404))
            ->when(isset(self::STATUS_GROUPS[$request->input('status')]), fn ($q) => $q->whereBetween('status', self::STATUS_GROUPS[$request->input('status')]))
            ->latest('id')
            ->paginate((int) $request->input('per_page', self::PER_PAGE[0]))
            ->onEachSide(1)
            ->withQueryString();

        return view('pages.admin.visits.index', [
            'views' => $views,
            'last24h' => $this->last24h(),
            'topIps' => $this->topIps(),
            'missingPages' => $this->missingPages(),
            'statusGroups' => array_keys(self::STATUS_GROUPS),
            'perPageOptions' => self::PER_PAGE,
            'keepDays' => PageView::KEEP_DAYS,
        ]);
    }

    // Quick numbers for the last 24 hours.
    private function last24h(): array
    {
        $rows = PageView::where('created_at', '>=', now()->subDay());
        $human = (clone $rows)->humanViews()->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')->first();

        return [
            'requests' => (clone $rows)->count(),
            'views' => (int) $human->views,
            'visitors' => (int) $human->visitors,
            'bots' => (clone $rows)->where('device', 'bot')->count(),
            'client_errors' => (clone $rows)->whereBetween('status', [400, 499])->count(),
            'server_errors' => (clone $rows)->where('status', '>=', 500)->count(),
        ];
    }

    // IPs with the most requests in the last 24 hours, with how many of them failed.
    // Lots of 4xx from one IP usually means someone is probing the site or guessing logins.
    private function topIps()
    {
        return PageView::where('created_at', '>=', now()->subDay())
            ->select(
                'ip_address',
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(status BETWEEN 400 AND 499) as client_errors'),
                DB::raw("SUM(method = 'POST') as posts"),
                DB::raw('MAX(user_id) as user_id'),
                DB::raw("SUM(device = 'bot') as bot_requests"),
                DB::raw('MAX(created_at) as last_seen'),
            )
            ->groupBy('ip_address')
            ->orderByDesc('requests')
            ->take(10)
            ->get();
    }

    // Pages that don't exist that were asked for most in the last 7 days: broken links, or scanners.
    private function missingPages()
    {
        return PageView::where('created_at', '>=', now()->subDays(7))
            ->where('status', 404)
            ->select('path', DB::raw('COUNT(*) as hits'), DB::raw('COUNT(DISTINCT ip_address) as ips'))
            ->groupBy('path')
            ->orderByDesc('hits')
            ->take(10)
            ->get();
    }
}
