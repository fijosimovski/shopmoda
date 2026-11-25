<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductModelRequest;
use App\Models\Brand;
use App\Models\ProductModel;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Admin > Models: the parents of products (e.g. "Air Jordan 1").
class ProductModelController extends Controller
{
    public function index(Request $request)
    {
        $models = ProductModel::with('brand')
            ->withCount('products')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.models.index', compact('models'));
    }

    public function create()
    {
        return view('pages.admin.models.form', [
            'model' => new ProductModel(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(ProductModelRequest $request)
    {
        $model = new ProductModel($request->safe()->only(['brand_id', 'name', 'slug']));
        $this->saveImage($model, $request);
        $model->save();

        UserLog::record('model.created', 'Created model "' . $model->name . '"', $model);

        return redirect()->route('admin.models.index')->with('success', 'Model created.');
    }

    public function edit(ProductModel $model)
    {
        return view('pages.admin.models.form', [
            'model' => $model,
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(ProductModelRequest $request, ProductModel $model)
    {
        DB::transaction(function () use ($model, $request) {
            $model->fill($request->safe()->only(['brand_id', 'name', 'slug']));
            $this->saveImage($model, $request);
            $model->save();

            // Products always have the same brand as their model.
            $model->products()->withTrashed()->update(['brand_id' => $model->brand_id]);
        });

        UserLog::record('model.updated', 'Updated model "' . $model->name . '"', $model);

        return redirect()->route('admin.models.index')->with('success', 'Model saved.');
    }

    public function destroy(ProductModel $model)
    {
        // Not allowed while products still use it.
        if ($model->products()->exists()) {
            return back()->with('error', 'This model still has products. Move or delete them first.');
        }

        $model->delete();

        UserLog::record('model.deleted', 'Deleted model "' . $model->name . '"', $model);

        return redirect()->route('admin.models.index')->with('success', 'Model deleted.');
    }

    // Stores a newly uploaded image and removes the old uploaded file (seeded URLs are left alone).
    private function saveImage(ProductModel $model, ProductModelRequest $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }

        if ($model->image && !Str::startsWith($model->image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($model->image);
        }

        // Saved in storage/app/public/models (visible after "php artisan storage:link").
        $model->image = $request->file('image')->store('models', 'public');
    }
}
