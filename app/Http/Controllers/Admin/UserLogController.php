<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLog;
use Illuminate\Http\Request;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');

        $logs = UserLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->input('result') === 'failed', fn ($q) => $q->where('is_failure', true))
            ->when($request->input('result') === 'ok', fn ($q) => $q->where('is_failure', false))
            // User email, description (failed logins have the tried email there) or exact IP.
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where('email', 'like', '%' . $search . '%'))
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('ip_address', $search)))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // All different actions, for the filter dropdown.
        $actions = UserLog::select('action')->distinct()->orderBy('action')->pluck('action');

        $failuresToday = UserLog::where('is_failure', true)->where('created_at', '>=', now()->subDay())->count();

        return view('pages.admin.logs.index', compact('logs', 'actions', 'failuresToday'));
    }
}
