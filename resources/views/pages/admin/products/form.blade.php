@extends('layouts.admin')

{{-- One form for both "create" and "edit". $product->exists is false for a new product. --}}
@section('pageTitle') {{ $product->exists ? 'Edit ' . $product->name : 'New product' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.products.index') }}" class="small text-decoration-none">&larr; Products</a>
    <h1 class="h3 mt-2 mb-4">{{ $product->exists ? 'Edit: ' . $product->name : 'New product' }}</h1>

    @include('partials.errors')

    <form method="POST" enctype="multipart/form-data" data-validate novalidate
          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" data-rules="required|min:2|max:150|product_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="slug">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="made from the name" data-rules="max:170|slug">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="model_id">Model</label>
                            {{-- The brand is taken from the model. --}}
                            <select class="form-select" id="model_id" name="model_id" data-rules="required">
                                <option value="">Choose...</option>
                                @foreach($brands as $brand)
                                    <optgroup label="{{ $brand->name }}">
                                        @foreach($brand->models as $model)
                                            <option value="{{ $model->id }}" @selected(old('model_id', $product->model_id) == $model->id)>{{ $model->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="form-text"><a href="{{ route('admin.models.create') }}" target="_blank">Add a new model</a></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select" id="category_id" name="category_id" data-rules="required">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="gender">For</label>
                            <select class="form-select" id="gender" name="gender" data-rules="required">
                                @foreach(\App\Models\Product::GENDERS as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender', $product->gender) === $gender)>{{ ucfirst($gender) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" data-rules="max:5000">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Sizes and stock -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Sizes and stock</span>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="addSize"><i class="bi bi-plus"></i> Add size</button>
                    </div>
                    <div class="card-body">
                        @php
                            // After a failed save, show what was typed; otherwise the saved sizes.
                            $sizeRows = old('sizes', $product->sizes->map(fn ($s) => ['size' => $s->size, 'stock' => $s->stock])->all());
                            if (empty($sizeRows)) {
                                $sizeRows = [['size' => '', 'stock' => 0]];
                            }
                        @endphp
                        <table class="table table-sm align-middle mb-0" style="max-width: 420px">
                            <thead><tr><th>Size (EU)</th><th>Stock</th><th></th></tr></thead>
                            <tbody id="sizeRows">
                            @foreach(array_values($sizeRows) as $i => $row)
                                <tr>
                                    <td><input type="number" step="0.5" class="form-control form-control-sm" name="sizes[{{ $i }}][size]" value="{{ $row['size'] }}" data-label="Size" data-rules="required|numeric|min:15|max:55|multiple_of:0.5|distinct:size"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="sizes[{{ $i }}][stock]" value="{{ $row['stock'] }}" data-label="Stock" data-rules="required|integer|min:0|max:99999"></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger remove-size"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Images -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Images</div>
                    <div class="card-body">
                        @if($product->exists && $product->images->isNotEmpty())
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                @foreach($product->images as $image)
                                    <div class="text-center">
                                        <img src="{{ $image->url() }}" width="110" height="110" class="rounded border {{ $image->is_main ? 'border-warning border-3' : '' }}" style="object-fit: cover" alt="">
                                        <div class="mt-1">
                                            @if($image->is_main)
                                                <span class="badge bg-warning text-dark">Main</span>
                                            @else
                                                {{-- These buttons submit small separate forms (see bottom of the page) --}}
                                                <button type="submit" form="mainImage{{ $image->id }}" class="btn btn-sm btn-link p-0">Make main</button>
                                            @endif
                                            <button type="submit" form="deleteImage{{ $image->id }}" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                    onclick="return confirm('Remove this image?')">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label class="form-label" for="images">Upload images</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple data-label="Images" data-rules="mimes:jpg,jpeg,png,webp|max_size:4096|max_files:10">
                        <div class="form-text">JPG, PNG or WEBP, max 4 MB each. The first image becomes the main one if there isn't one yet.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="price">Price (RSD)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', $product->exists ? $product->price() : '') }}" data-rules="required|numeric|decimal:2|min:1|max:9999999">
                            <div class="form-text">Changing it keeps the old price in the history.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="discount_percent">Discount (%)</label>
                            <input type="number" class="form-control" id="discount_percent" name="discount_percent" value="{{ old('discount_percent', $product->discount_percent ?? 0) }}" data-rules="required|integer|min:0|max:90">
                        </div>
                        {{-- Hidden 0 + checkbox 1: an unticked checkbox still sends "0" --}}
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
                            <label class="form-check-label" for="is_active">Visible in the shop</label>
                        </div>
                        <input type="hidden" name="is_featured" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))>
                            <label class="form-check-label" for="is_featured">Featured on the homepage</label>
                        </div>
                    </div>
                </div>

                <button class="btn btn-dark w-100 mb-4">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>

                @if($product->exists && $product->prices->isNotEmpty())
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Price history</div>
                        <ul class="list-group list-group-flush small">
                            @foreach($product->prices as $price)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-secondary">{{ $price->created_at->format('d.m.Y.') }}</span>
                                    <span class="{{ $loop->first ? 'fw-bold' : '' }}">@money($price->price) @if($loop->first)(current)@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </form>

    {{-- Separate forms for the image buttons (HTML doesn't allow a form inside another form). --}}
    @if($product->exists)
        @foreach($product->images as $image)
            <form id="mainImage{{ $image->id }}" method="POST" action="{{ route('admin.products.images.main', [$product, $image]) }}">
                @csrf @method('PATCH')
            </form>
            <form id="deleteImage{{ $image->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif
@endsection

@push('scripts')
    <script>
        // "Add size" adds a new empty row, the X button removes a row.
        const rows = document.getElementById('sizeRows');
        let nextIndex = rows.children.length;

        document.getElementById('addSize').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="number" step="0.5" class="form-control form-control-sm" name="sizes[${nextIndex}][size]" data-label="Size" data-rules="required|numeric|min:15|max:55|multiple_of:0.5|distinct:size"></td>
                <td><input type="number" class="form-control form-control-sm" name="sizes[${nextIndex}][stock]" value="0" data-label="Stock" data-rules="required|integer|min:0|max:99999"></td>
                <td><button type="button" class="btn btn-sm btn-link text-danger remove-size"><i class="bi bi-x-lg"></i></button></td>`;
            rows.appendChild(row);
            nextIndex++;
        });

        rows.addEventListener('click', function (e) {
            if (e.target.closest('.remove-size') && rows.children.length > 1) {
                e.target.closest('tr').remove();
            }
        });
    </script>
@endpush
