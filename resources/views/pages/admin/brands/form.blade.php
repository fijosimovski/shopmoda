@extends('layouts.admin')

@section('pageTitle') {{ $brand->exists ? 'Edit brand' : 'New brand' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.brands.index') }}" class="small text-decoration-none">&larr; Brands</a>
    <h1 class="h3 mt-2 mb-4">{{ $brand->exists ? 'Edit: ' . $brand->name : 'New brand' }}</h1>

    @include('partials.errors')

    <form method="POST" enctype="multipart/form-data" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}"
          class="card card-body border-0 shadow-sm admin-form-narrow" data-validate novalidate>
        @csrf
        @if($brand->exists) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $brand->name) }}" data-rules="required|min:2|max:100|label">
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug (URL)</label>
            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" placeholder="made from the name" data-rules="max:120|slug">
        </div>
        <div class="mb-3">
            <label class="form-label" for="image">Image</label>
            @if($brand->imageUrl())
                <img src="{{ $brand->imageUrl() }}" alt="{{ $brand->name }}" class="d-block rounded border mb-2 admin-image-preview">
            @endif
            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" data-rules="{{ $brand->exists ? '' : 'required|' }}mimes:jpg,jpeg,png,webp|max_size:4096">
            <div class="form-text">Shown in the brands slider on the homepage. JPG, PNG or WEBP, max 4 MB. Wide images (2:1) look best.</div>
        </div>
        <button class="btn btn-dark">{{ $brand->exists ? 'Save' : 'Create' }}</button>
    </form>
@endsection
