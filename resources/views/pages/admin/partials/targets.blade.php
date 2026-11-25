{{-- "Which products?" card of the promotion and coupon forms. Needs $item (Promotion or Coupon), $brands, $categories, $modelGroups, $productGroups. --}}
@php
    // Picked targets: from old() after a failed submit, otherwise from the database.
    $picked = fn (string $class, string $key) => array_map('intval', old($key, $item->exists ? $item->targetIds($class) : []));
@endphp
<div class="card card-body border-0 shadow-sm mb-4">
    <h2 class="h6 fw-bold mb-3">Which products?</h2>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="applies_to_all" name="applies_to_all" value="1" data-whole-shop
            @checked(old('applies_to_all', $item->applies_to_all))>
        <label class="form-check-label" for="applies_to_all">Whole shop</label>
    </div>

    <div class="row g-3" data-targets>
        <p class="small text-secondary mb-0">A product is included when it matches <strong>any</strong> of these.</p>
        <fieldset class="col-md-6">
            <legend class="form-label fs-6">Brands</legend>
            <div class="admin-check-list border rounded px-3 py-2">
                @foreach($brands as $brand)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="brand-{{ $brand->id }}" name="brands[]" value="{{ $brand->id }}"
                            @checked(in_array($brand->id, $picked(\App\Models\Brand::class, 'brands')))>
                        <label class="form-check-label" for="brand-{{ $brand->id }}">{{ $brand->name }}</label>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="col-md-6">
            <legend class="form-label fs-6">Categories</legend>
            <div class="admin-check-list border rounded px-3 py-2">
                @foreach($categories as $category)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="category-{{ $category->id }}" name="categories[]" value="{{ $category->id }}"
                            @checked(in_array($category->id, $picked(\App\Models\Category::class, 'categories')))>
                        <label class="form-check-label" for="category-{{ $category->id }}">{{ $category->name }}</label>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="col-md-6">
            <legend class="form-label fs-6">Models</legend>
            <div class="admin-check-list border rounded px-3 pb-2">
                @foreach($modelGroups as $brandName => $models)
                    <div class="admin-check-group small fw-semibold text-secondary pt-2 pb-1">{{ $brandName }}</div>
                    @foreach($models as $model)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="model-{{ $model->id }}" name="models[]" value="{{ $model->id }}"
                                @checked(in_array($model->id, $picked(\App\Models\ProductModel::class, 'models')))>
                            <label class="form-check-label" for="model-{{ $model->id }}">{{ $model->name }}</label>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </fieldset>
        <fieldset class="col-md-6">
            <legend class="form-label fs-6">Single products</legend>
            <div class="admin-check-list border rounded px-3 pb-2">
                @foreach($productGroups as $brandName => $products)
                    <div class="admin-check-group small fw-semibold text-secondary pt-2 pb-1">{{ $brandName }}</div>
                    @foreach($products as $product)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="product-{{ $product->id }}" name="products[]" value="{{ $product->id }}"
                                @checked(in_array($product->id, $picked(\App\Models\Product::class, 'products')))>
                            <label class="form-check-label" for="product-{{ $product->id }}">{{ $product->name }}</label>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </fieldset>
    </div>
</div>
