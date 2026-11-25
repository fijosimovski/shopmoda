@extends('layouts.admin')

@section('pageTitle') {{ $category->exists ? 'Edit category' : 'New category' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.categories.index') }}" class="small text-decoration-none">&larr; Categories</a>
    <h1 class="h3 mt-2 mb-4">{{ $category->exists ? 'Edit: ' . $category->name : 'New category' }}</h1>

    @include('partials.errors')

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="card card-body border-0 shadow-sm" style="max-width: 480px" data-validate novalidate>
        @csrf
        @if($category->exists) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" data-rules="required|min:2|max:100|label">
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug (URL)</label>
            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="made from the name" data-rules="max:120|slug">
        </div>
        <button class="btn btn-dark">{{ $category->exists ? 'Save' : 'Create' }}</button>
    </form>
@endsection
