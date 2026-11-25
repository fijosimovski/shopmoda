@extends('layouts.admin')

@section('pageTitle') Announcements @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Announcements</h1>
            <p class="small text-secondary mb-0">Drops, restocks and promos in a slim bar under the navigation (a slideshow when several run at once).</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New announcement</a>
    </div>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th></th><th>Title</th><th>Type</th><th>Links to</th><th>Shown on</th><th>When</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($announcements as $announcement)
                <tr @class(['admin-row-new' => $announcement->created_at?->gt(now()->subDay())])>
                    <td>
                        @if($announcement->imageUrl())
                            <img src="{{ $announcement->imageUrl() }}" class="rounded admin-thumb" alt="{{ $announcement->title }}">
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $announcement->title }}</div>
                        @if($announcement->body)
                            <div class="small text-secondary">{{ Str::limit($announcement->body, 70) }}</div>
                        @endif
                    </td>
                    <td><span class="badge announce-type-{{ $announcement->type }}">{{ $announcement->label() }}</span></td>
                    <td class="small">
                        @if($announcement->url())
                            <a href="{{ $announcement->url() }}" target="_blank">{{ $announcement->linkable->name }} <i class="bi bi-box-arrow-up-right"></i></a>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td class="small">{{ \App\Models\Announcement::SHOW_ON[$announcement->show_on] ?? $announcement->show_on }}</td>
                    <td class="small text-nowrap">{{ $announcement->starts_at->format('d.m.Y. H:i') }}<br>{{ $announcement->ends_at->format('d.m.Y. H:i') }}</td>
                    <td>@include('partials.discount-status', ['status' => $announcement->status()])</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($announcement->title) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary p-3">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
