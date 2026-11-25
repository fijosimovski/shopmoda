@extends('layouts.admin')

@section('pageTitle') Message @endsection

@section('pageContent')
    <a href="{{ route('admin.messages.index') }}" class="small text-decoration-none">&larr; Messages</a>
    <h1 class="h3 mt-2 mb-1">{{ $message->subject }}</h1>
    <p class="text-secondary mb-4">
        From <strong class="text-dark">{{ $message->name }}</strong> &lt;{{ $message->email }}&gt;
        &middot; {{ $message->created_at->format('d.m.Y. H:i') }}
        @if($message->ip_address) &middot; IP {{ $message->ip_address }} @endif
        @if($message->user?->trashed()) &middot; <span class="badge bg-danger">User deactivated</span> @endif
    </p>

    <div class="card card-body border-0 shadow-sm mb-4 admin-message-body">{{ $message->message }}</div>

    <div class="d-flex gap-2">
        <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . $message->subject) }}" class="btn btn-dark"><i class="bi bi-reply me-1"></i> Reply by email</a>
        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Delete this message?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> Delete</button>
        </form>
    </div>
@endsection
