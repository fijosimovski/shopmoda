<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Models\UserLog;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('pages.admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('pages.admin.categories.form', ['category' => new Category()]);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        UserLog::record('category.created', 'Created category "' . $category->name . '"', $category);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('pages.admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        UserLog::record('category.updated', 'Updated category "' . $category->name . '"', $category);

        return redirect()->route('admin.categories.index')->with('success', 'Category saved.');
    }

    public function destroy(Category $category)
    {
        // Not allowed while products still use it.
        if ($category->products()->exists()) {
            return back()->with('error', 'This category still has products. Move or delete them first.');
        }

        $category->delete();

        UserLog::record('category.deleted', 'Deleted category "' . $category->name . '"', $category);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
