@extends('layouts.admin')

@section('pageTitle') Categories @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Categories</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New category</a>
    </div>

    <div class="bg-white border rounded" style="max-width: 720px">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th></th></tr></thead>
            <tbody>
            @forelse($categories as $category)
                <tr @class(['admin-row-new' => $category->created_at?->gt(now()->subDay())])>
                    <td class="fw-semibold">{{ $category->name }}</td>
                    <td class="text-secondary">{{ $category->slug }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-secondary p-3">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
