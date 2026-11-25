@extends('layouts.admin')

@section('pageTitle') Products @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New product</a>
    </div>

    <form method="GET" class="mb-3" style="max-width: 320px">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name...">
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr><th></th><th>Name</th><th>Model / Category</th><th>Price</th><th>Discount</th><th>Stock</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr @class(['admin-row-new' => $product->created_at?->gt(now()->subDay())])>
                    <td><img src="{{ $product->mainImage?->url() }}" width="48" height="48" class="rounded" style="object-fit: cover" alt=""></td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a>
                        @if($product->is_featured)<i class="bi bi-star-fill text-warning" title="Featured"></i>@endif
                    </td>
                    <td class="small">{{ $product->brand->name }} {{ $product->productModel?->name }}<br><span class="text-secondary">{{ $product->category->name }}</span></td>
                    <td>@money($product->price())</td>
                    <td>{{ $product->discount_percent ? $product->discount_percent . '%' : '-' }}</td>
                    <td class="{{ (int) $product->total_stock === 0 ? 'text-danger fw-bold' : '' }}">{{ (int) $product->total_stock }}</td>
                    <td>
                        @if($product->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Hidden</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary p-3">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
@endsection
