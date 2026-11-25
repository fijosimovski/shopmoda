@extends('layouts.admin')

@section('pageTitle') User logs @endsection

@section('pageContent')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">User logs</h1>
            <div class="small text-secondary">
                What people did in the shop, and important things that failed (in red). Deleted after {{ \App\Models\UserLog::KEEP_MONTHS }} months.
            </div>
        </div>
        <a href="{{ route('admin.logs.index', ['result' => 'failed']) }}" class="btn btn-sm {{ $failuresToday ? 'btn-outline-danger' : 'btn-outline-secondary' }}">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ $failuresToday }} {{ Str::plural('failure', $failuresToday) }} in the last 24 hours
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Email, description or exact IP...">
        </div>
        <div class="col-6 col-md-auto">
            <select name="result" class="form-select" onchange="this.form.submit()" aria-label="Result">
                <option value="">All results</option>
                <option value="failed" @selected(request('result') === 'failed')>Failures only</option>
                <option value="ok" @selected(request('result') === 'ok')>Successes only</option>
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <select name="action" class="form-select" onchange="this.form.submit()" aria-label="Action">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-dark">Filter</button></div>
        @if(request()->hasAny(['q', 'result', 'action']))
            <div class="col-auto"><a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">Clear</a></div>
        @endif
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr @class(['table-danger' => $log->is_failure])>
                    <td class="small text-nowrap">{{ $log->created_at->format('d.m.Y. H:i') }}</td>
                    <td class="small">{{ $log->user?->email ?? 'guest' }}</td>
                    <td class="text-nowrap">
                        @if($log->is_failure)<i class="bi bi-x-circle-fill text-danger me-1" title="Failed"></i>@endif<code>{{ $log->action }}</code>
                    </td>
                    <td class="small">
                        {{ $log->description }}
                        @if($log->subject_type === \App\Models\Order::class && $log->subject_id)
                            <a href="{{ route('admin.orders.show', $log->subject_id) }}" class="ms-1">open</a>
                        @endif
                    </td>
                    <td class="small">
                        {{-- Everything this IP requested, on the Visits page --}}
                        <a href="{{ route('admin.visits.index', ['ip' => $log->ip_address]) }}" class="font-monospace text-decoration-none" title="Show this IP's requests">{{ $log->ip_address }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-secondary p-3">No logs.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
