<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\UserLog;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::with('targets')
            ->withCount('redemptions')
            ->withSum('redemptions', 'discount_amount')
            ->latest()
            ->get();

        return view('pages.admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('pages.admin.coupons.form', $this->formData(new Coupon([
            'type' => Coupon::TYPE_PERCENT,
            'applies_to_all' => true,
            'is_active' => true,
        ])));
    }

    public function store(CouponRequest $request)
    {
        $coupon = Coupon::create($this->fields($request));
        $coupon->syncTargets(Coupon::targetsFromInput($request->validated()));

        UserLog::record('coupon.created', 'Created coupon ' . $coupon->code, $coupon);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon ' . $coupon->code . ' created.');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load('targets');

        // Last uses, so the admin can see who used it on which order.
        $redemptions = $coupon->redemptions()->withTrashed()->with('order')->latest()->take(20)->get();

        return view('pages.admin.coupons.form', compact('redemptions') + $this->formData($coupon));
    }

    public function update(CouponRequest $request, Coupon $coupon)
    {
        $coupon->update($this->fields($request));
        $coupon->syncTargets(Coupon::targetsFromInput($request->validated()));

        UserLog::record('coupon.updated', 'Updated coupon ' . $coupon->code, $coupon);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon saved.');
    }

    // Soft delete: orders keep the copied code, and the code can't be reused.
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        UserLog::record('coupon.deleted', 'Deleted coupon ' . $coupon->code, $coupon);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    private function fields(CouponRequest $request): array
    {
        $data = $request->safe()->only([
            'code', 'description', 'type', 'value', 'min_order_total', 'max_discount',
            'usage_limit', 'usage_limit_per_customer', 'first_order_only', 'exclude_sale_items',
            'applies_to_all', 'starts_at', 'ends_at', 'is_active',
        ]);

        // A cap only makes sense for percent coupons.
        if ($data['type'] !== Coupon::TYPE_PERCENT) {
            $data['max_discount'] = null;
        }

        return $data;
    }

    private function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'redemptions' => collect(),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'modelGroups' => ProductModel::with('brand')->orderBy('name')->get()->sortBy('brand.name')->groupBy('brand.name'),
            'productGroups' => Product::with('brand')->orderBy('name')->get()->sortBy('brand.name')->groupBy('brand.name'),
        ];
    }
}
