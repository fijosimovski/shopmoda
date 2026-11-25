@extends('layouts.admin')

@section('pageTitle') {{ $model->exists ? 'Edit model' : 'New model' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.models.index') }}" class="small text-decoration-none">&larr; Models</a>
    <h1 class="h3 mt-2 mb-4">{{ $model->exists ? 'Edit: ' . $model->name : 'New model' }}</h1>

    @include('partials.errors')

    <form method="POST" enctype="multipart/form-data" action="{{ $model->exists ? route('admin.models.update', $model) : route('admin.models.store') }}"
          class="card card-body border-0 shadow-sm admin-form-narrow" data-validate novalidate>
        @csrf
        @if($model->exists) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label" for="brand_id">Brand</label>
            <select class="form-select" id="brand_id" name="brand_id" data-rules="required">
                <option value="">Choose...</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(old('brand_id', $model->brand_id) == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            @if($model->exists)
                <div class="form-text">Changing the brand also changes it on all products of this model.</div>
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $model->name) }}" placeholder="e.g. Air Jordan 1" data-rules="required|max:100|product_name">
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug (URL)</label>
            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $model->slug) }}" placeholder="made from the brand and name" data-rules="max:170|slug">
        </div>
        <div class="mb-3">
            <label class="form-label" for="image">Image</label>
            @if($model->imageUrl())
                <img src="{{ $model->imageUrl() }}" alt="{{ $model->name }}" class="d-block rounded border mb-2 admin-image-preview">
            @endif
            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" data-rules="{{ $model->exists ? '' : 'required|' }}mimes:jpg,jpeg,png,webp|max_size:4096">
            <div class="form-text">Shown in the models section on the homepage. JPG, PNG or WEBP, max 4 MB. Square images look best.</div>
        </div>
        <button class="btn btn-dark">{{ $model->exists ? 'Save' : 'Create' }}</button>
    </form>
@endsection
