@extends('layouts.admin')

@section('pageTitle') Brands @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Brands</h1>
        <a href="{{ route('admin.brands.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New brand</a>
    </div>

    <div class="bg-white border rounded" style="max-width: 720px">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th></th><th>Name</th><th>Slug</th><th>Models</th><th>Products</th><th></th></tr></thead>
            <tbody>
            @forelse($brands as $brand)
                <tr @class(['admin-row-new' => $brand->created_at?->gt(now()->subDay())])>
                    <td>
                        @if($brand->imageUrl())
                            <img src="{{ $brand->imageUrl() }}" class="rounded admin-thumb-wide" alt="{{ $brand->name }}">
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $brand->name }}</td>
                    <td class="text-secondary">{{ $brand->slug }}</td>
                    <td>{{ $brand->models_count }}</td>
                    <td>{{ $brand->products_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($brand->name) }}?')">
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
@endsection
