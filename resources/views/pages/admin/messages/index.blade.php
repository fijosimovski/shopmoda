@extends('layouts.admin')

@section('pageTitle') Messages @endsection

@section('pageContent')
    <h1 class="h3 mb-0">Messages</h1>
    <p class="small text-secondary mb-3">From the contact page. Each one is also emailed to the contact email (Settings); reply from your mail client.</p>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-sm-auto">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, email, subject...">
        </div>
        <div class="col-sm-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All messages</option>
                <option value="unread" @selected(request('status') === 'unread')>Unread</option>
            </select>
        </div>
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
            <tbody>
            @forelse($messages as $message)
                <tr @class(['fw-semibold' => !$message->isRead(), 'admin-row-new' => $message->created_at?->gt(now()->subDay())])>
                    <td>
                        @if(!$message->isRead())<span class="badge text-bg-warning me-1">New</span>@endif
                        {{ $message->name }}
                        <div class="small text-secondary fw-normal">{{ $message->email }}</div>
                    </td>
                    <td>
                        <a href="{{ route('admin.messages.show', $message) }}" class="text-decoration-none">{{ $message->subject }}</a>
                        <div class="small text-secondary fw-normal">{{ Str::limit($message->message, 90) }}</div>
                    </td>
                    <td class="small text-nowrap fw-normal">{{ $message->created_at->format('d.m.Y. H:i') }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye"></i></a>
                        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" class="d-inline" onsubmit="return confirm('Delete this message?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-secondary p-3">No messages.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $messages->links() }}</div>
@endsection
