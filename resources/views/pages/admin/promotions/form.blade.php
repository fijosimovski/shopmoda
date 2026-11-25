@extends('layouts.admin')

@section('pageTitle') {{ $promotion->exists ? 'Edit promotion' : 'New promotion' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.promotions.index') }}" class="small text-decoration-none">&larr; Promotions</a>
    <h1 class="h3 mt-2 mb-4">{{ $promotion->exists ? 'Edit: ' . $promotion->name : 'New promotion' }}</h1>

    @include('partials.errors')

    <form method="POST" enctype="multipart/form-data" action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}"
          data-validate data-type-form novalidate>
        @csrf
        @if($promotion->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-xl-7">
                <!-- Page and banner -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">Promotion page</h2>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $promotion->name) }}" placeholder="e.g. Black Friday" data-rules="required|min:3|max:100">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="slug">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $promotion->slug) }}" placeholder="made from the name" data-rules="max:120|slug">
                            <div class="form-text">Page: /promo/<em>slug</em></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description (optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="2" data-rules="max:500">{{ old('description', $promotion->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="image">Banner (optional)</label>
                            @if($promotion->imageUrl())
                                <img src="{{ $promotion->imageUrl() }}" alt="{{ $promotion->name }}" class="d-block rounded border mb-2 admin-image-preview">
                            @endif
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" data-rules="mimes:jpg,jpeg,png,webp|max_size:4096">
                            <div class="form-text">Homepage cover and promotion page background. Wide images (e.g. 1920&times;720) look best. Max 4 MB.</div>
                        </div>
                    </div>
                </div>

                @include('pages.admin.partials.targets', ['item' => $promotion])
            </div>

            <div class="col-xl-5">
                <!-- Rule -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">Discount</h2>
                    <div class="mb-3">
                        <label class="form-label" for="type">Type</label>
                        <select class="form-select" id="type" name="type" data-type-select>
                            @foreach(\App\Models\Promotion::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $promotion->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" data-show-for="percent fixed">Changes the price everywhere in the shop (old price crossed out).</div>
                        <div class="form-text" data-show-for="nth_item cart_threshold">Calculated in the cart; products only get a badge.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="value">
                                <span data-show-for="percent nth_item">Discount (%)</span>
                                <span data-show-for="fixed cart_threshold">Amount off (RSD)</span>
                            </label>
                            <input type="number" class="form-control" id="value" name="value" step="0.01" value="{{ old('value', $promotion->value !== null ? (float) $promotion->value : '') }}" data-label="Discount" data-rules="required|numeric|min:1">
                        </div>
                        <div class="col-6" data-show-for="nth_item">
                            <label class="form-label" for="nth">Every n-th pair</label>
                            <input type="number" class="form-control" id="nth" name="nth" value="{{ old('nth', $promotion->nth ?? 2) }}" data-rules="integer|min:2|max:10">
                            <div class="form-text">2 = every 2nd (cheaper) pair</div>
                        </div>
                        <div class="col-6" data-show-for="cart_threshold">
                            <label class="form-label" for="min_cart_total">Spend at least (RSD)</label>
                            <input type="number" class="form-control" id="min_cart_total" name="min_cart_total" step="0.01" value="{{ old('min_cart_total', $promotion->min_cart_total !== null ? (float) $promotion->min_cart_total : '') }}" data-label="Spend at least" data-rules="numeric|min:1">
                        </div>
                    </div>
                </div>

                <!-- When -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">When</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="starts_at">Starts</label>
                            <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\TH:i')) }}" data-rules="required">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="ends_at">Ends</label>
                            <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\TH:i')) }}" data-rules="required">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $promotion->is_active))>
                        <label class="form-check-label" for="is_active">Active (switch off to hide it without deleting)</label>
                    </div>
                </div>

                <!-- When several match -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">When several promotions match</h2>
                    <div class="mb-3">
                        <label class="form-label" for="priority">Priority</label>
                        <input type="number" class="form-control admin-input-narrow" id="priority" name="priority" value="{{ old('priority', $promotion->priority) }}" data-rules="required|integer|min:0|max:1000">
                        <div class="form-text">Higher wins. Also the order of the homepage cover slides.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_stackable" name="is_stackable" value="1" @checked(old('is_stackable', $promotion->is_stackable))>
                        <label class="form-check-label" for="is_stackable">Stackable</label>
                    </div>
                    <div class="form-text" data-show-for="percent fixed">On: applied on top of the product's own sale discount. Off: the customer gets the better of the two.</div>
                    <div class="form-text" data-show-for="nth_item cart_threshold">On: combines with other stackable cart promotions. Off: only the highest-priority one applies.</div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="members_only" name="members_only" value="1" @checked(old('members_only', $promotion->members_only))>
                        <label class="form-check-label" for="members_only">Registered customers only</label>
                    </div>
                    <div class="form-text">On: only logged-in customers get the discount. Guests still see the banner and promo page, with a prompt to log in or register.</div>
                </div>

                <button class="btn btn-dark w-100">{{ $promotion->exists ? 'Save' : 'Create' }}</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-discount-form.js') }}"></script>
@endpush
