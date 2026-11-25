<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'new_orders' => Order::where('status', Order::STATUS_NEW)->count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            // Same rule as Analytics: only processing, shipped and completed orders are sales.
            'revenue_month' => Order::sales()->where('created_at', '>=', now()->startOfMonth())->sum('total'),
            'products' => Product::count(),
            'customers' => User::whereHas('role', fn ($q) => $q->where('name', 'customer'))->count(),
        ];

        $latestOrders = Order::latest()->take(8)->get();

        $bestSellers = Product::bestSellers()->with('brand')->take(5)->get();

        // Sizes that are almost sold out (only for products still in the shop).
        $lowStock = ProductSize::with('product')
            ->where('stock', '<=', 2)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->orderBy('stock')
            ->take(10)
            ->get();

        return view('pages.admin.dashboard', compact('stats', 'latestOrders', 'bestSellers', 'lowStock'));
    }
}
