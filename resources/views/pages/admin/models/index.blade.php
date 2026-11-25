@extends('layouts.admin')

@section('pageTitle') Models @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Models</h1>
        <a href="{{ route('admin.models.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New model</a>
    </div>

    <p class="text-secondary small">A model groups products, e.g. "Air Jordan 1" groups all its colorways. Models are shown on the homepage.</p>

    <form method="GET" class="mb-3 admin-search">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name...">
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th></th><th>Name</th><th>Brand</th><th>Slug</th><th>Products</th><th></th></tr></thead>
            <tbody>
            @forelse($models as $model)
                <tr @class(['admin-row-new' => $model->created_at?->gt(now()->subDay())])>
                    <td>
                        @if($model->imageUrl())
                            <img src="{{ $model->imageUrl() }}" class="rounded admin-thumb" alt="{{ $model->name }}">
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $model->name }}</td>
                    <td>{{ $model->brand->name }}</td>
                    <td class="text-secondary">{{ $model->slug }}</td>
                    <td>
                        <a href="{{ route('products.index', ['model' => $model->slug]) }}" target="_blank" class="text-decoration-none">{{ $model->products_count }}</a>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.models.edit', $model) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.models.destroy', $model) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($model->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary p-3">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $models->links() }}</div>
@endsection
