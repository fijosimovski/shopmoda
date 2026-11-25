<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Models\UserLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount(['products', 'models'])->orderBy('name')->get();

        return view('pages.admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('pages.admin.brands.form', ['brand' => new Brand()]);
    }

    public function store(BrandRequest $request)
    {
        $brand = new Brand($request->safe()->only(['name', 'slug']));
        $this->saveImage($brand, $request);
        $brand->save();

        UserLog::record('brand.created', 'Created brand "' . $brand->name . '"', $brand);

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('pages.admin.brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $brand->fill($request->safe()->only(['name', 'slug']));
        $this->saveImage($brand, $request);
        $brand->save();

        UserLog::record('brand.updated', 'Updated brand "' . $brand->name . '"', $brand);

        return redirect()->route('admin.brands.index')->with('success', 'Brand saved.');
    }

    public function destroy(Brand $brand)
    {
        // Not allowed while products or models still use it.
        if ($brand->products()->exists() || $brand->models()->exists()) {
            return back()->with('error', 'This brand still has models or products. Move or delete them first.');
        }

        $brand->delete();

        UserLog::record('brand.deleted', 'Deleted brand "' . $brand->name . '"', $brand);

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }

    // Stores a newly uploaded image and removes the old uploaded file (seeded URLs are left alone).
    private function saveImage(Brand $brand, BrandRequest $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }

        if ($brand->image && !Str::startsWith($brand->image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($brand->image);
        }

        // Saved in storage/app/public/brands (visible after "php artisan storage:link").
        $brand->image = $request->file('image')->store('brands', 'public');
    }
}
