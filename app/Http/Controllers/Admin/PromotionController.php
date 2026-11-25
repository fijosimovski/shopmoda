<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\Promotion;
use App\Models\UserLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::with('targets')
            ->withCount('orderDiscounts')
            ->orderByDesc('ends_at')
            ->get()
            // running first, then upcoming, then the rest
            ->sortBy(fn ($p) => array_search($p->status(), ['running', 'upcoming', 'ended', 'off']))
            ->values();

        return view('pages.admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('pages.admin.promotions.form', $this->formData(new Promotion([
            'type' => Promotion::TYPE_PERCENT,
            'priority' => 0,
            'is_active' => true,
            'starts_at' => now()->startOfHour(),
            'ends_at' => now()->addWeek()->endOfDay(),
        ])));
    }

    public function store(PromotionRequest $request)
    {
        $promotion = new Promotion($this->fields($request));
        $this->saveImage($promotion, $request);
        $promotion->save();
        $promotion->syncTargets(Promotion::targetsFromInput($request->validated()));

        UserLog::record('promotion.created', 'Created promotion "' . $promotion->name . '"', $promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion created.');
    }

    public function edit(Promotion $promotion)
    {
        $promotion->load('targets');

        return view('pages.admin.promotions.form', $this->formData($promotion));
    }

    public function update(PromotionRequest $request, Promotion $promotion)
    {
        $promotion->fill($this->fields($request));
        $this->saveImage($promotion, $request);
        $promotion->save();
        $promotion->syncTargets(Promotion::targetsFromInput($request->validated()));

        UserLog::record('promotion.updated', 'Updated promotion "' . $promotion->name . '"', $promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion saved.');
    }

    // Soft delete: old orders keep their discount snapshot (order_discounts) either way.
    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        UserLog::record('promotion.deleted', 'Deleted promotion "' . $promotion->name . '"', $promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion deleted.');
    }

    private function fields(PromotionRequest $request): array
    {
        $data = $request->safe()->only([
            'name', 'slug', 'description', 'type', 'value', 'nth', 'min_cart_total',
            'applies_to_all', 'priority', 'is_stackable', 'starts_at', 'ends_at', 'is_active',
        ]);

        // Fields that don't belong to the chosen type are emptied.
        $data['nth'] = $data['type'] === Promotion::TYPE_NTH_ITEM ? $data['nth'] : null;
        $data['min_cart_total'] = $data['type'] === Promotion::TYPE_CART_THRESHOLD ? $data['min_cart_total'] : null;

        return $data;
    }

    private function formData(Promotion $promotion): array
    {
        return [
            'promotion' => $promotion,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'modelGroups' => ProductModel::with('brand')->orderBy('name')->get()->sortBy('brand.name')->groupBy('brand.name'),
            'productGroups' => Product::with('brand')->orderBy('name')->get()->sortBy('brand.name')->groupBy('brand.name'),
        ];
    }

    // Stores a newly uploaded banner and removes the old uploaded file (seeded URLs are left alone).
    private function saveImage(Promotion $promotion, PromotionRequest $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }

        if ($promotion->image && !Str::startsWith($promotion->image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($promotion->image);
        }

        // Saved in storage/app/public/promotions (visible after "php artisan storage:link").
        $promotion->image = $request->file('image')->store('promotions', 'public');
    }
}
