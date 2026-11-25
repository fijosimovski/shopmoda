<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['brand', 'productModel', 'category', 'currentPrice', 'mainImage'])
            ->withSum('sizes as total_stock', 'stock')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('pages.admin.products.form', [
            'product' => new Product(['is_active' => true, 'discount_percent' => 0]),
            'brands' => $this->brandsWithModels(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = new Product();
        $this->save($product, $request);

        UserLog::record('product.created', 'Created product "' . $product->name . '"', $product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load(['sizes', 'images', 'prices']);

        return view('pages.admin.products.form', [
            'product' => $product,
            'brands' => $this->brandsWithModels(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->save($product, $request);

        UserLog::record('product.updated', 'Updated product "' . $product->name . '"', $product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved.');
    }

    public function destroy(Product $product)
    {
        $product->delete(); // soft delete: old orders still show it

        UserLog::record('product.deleted', 'Deleted product "' . $product->name . '"', $product);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    // Brands with their models, for the grouped "Model" select in the form.
    private function brandsWithModels()
    {
        return Brand::with(['models' => fn ($q) => $q->orderBy('name')])
            ->whereHas('models')
            ->orderBy('name')
            ->get();
    }

    // Shared by store() and update(): product fields, price, sizes and new images.
    private function save(Product $product, ProductRequest $request): void
    {
        DB::transaction(function () use ($product, $request) {
            $product->fill($request->only([
                'model_id', 'category_id', 'name', 'slug', 'description', 'gender', 'discount_percent',
            ]));
            // A product always has the brand of its model.
            $product->brand_id = ProductModel::findOrFail($request->model_id)->brand_id;
            $product->is_featured = $request->boolean('is_featured');
            $product->is_active = $request->boolean('is_active');
            $product->save();

            // Adds a new price row only if the price changed (old one stays as history).
            $product->setPrice((float) $request->price);

            $this->syncSizes($product, $request->sizes);

            $this->storeImages($product, $request->file('images', []));
        });
    }

    // Makes the product's sizes match the form: update existing, add new, remove missing.
    private function syncSizes(Product $product, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            // withTrashed: a size that was removed earlier is brought back instead of
            // creating a duplicate (product_id + size must be unique).
            $size = $product->sizes()->withTrashed()->firstOrNew(['size' => $row['size']]);
            $size->stock = $row['stock'];
            $size->deleted_at = null;
            $size->save();

            $keptIds[] = $size->id;
        }

        $product->sizes()->whereNotIn('id', $keptIds)->delete();
    }

    private function storeImages(Product $product, array $files): void
    {
        $hasMain = $product->images()->where('is_main', true)->exists();
        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($files as $file) {
            // Saved in storage/app/public/products (visible after "php artisan storage:link").
            $path = $file->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'is_main' => !$hasMain, // first image becomes the main one if there is none
                'sort_order' => ++$sortOrder,
            ]);

            $hasMain = true;
        }
    }
}
