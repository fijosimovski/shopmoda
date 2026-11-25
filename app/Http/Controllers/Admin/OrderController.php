<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OutOfStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Models\UserLog;
use App\Services\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private OrderCancellation $cancellation)
    {
    }

    public function index(Request $request)
    {
        $orders = Order::withCount('items')
            ->when(in_array($request->status, Order::STATUSES), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('id', $request->q)
                        ->orWhere('number', strtoupper(trim($request->q)))
                        ->orWhere('name', 'like', '%' . $request->q . '%')
                        ->orWhere('email', 'like', '%' . $request->q . '%')
                        ->orWhere('ip_address', $request->q);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product.mainImage', 'user', 'discounts']);

        return view('pages.admin.orders.show', compact('order'));
    }

    public function updateStatus(OrderStatusRequest $request, Order $order)
    {
        $old = $order->status;
        $new = $request->status;

        if ($old === $new) {
            return back();
        }

        try {
            DB::transaction(function () use ($order, $old, $new) {
                $wasReleased = Order::isReleasedStatus($old);
                $isReleased = Order::isReleasedStatus($new);

                // Cancelled / expired: the sneakers go back in stock and the coupon use is released.
                // (cancelled <-> expired changes nothing, both are already released)
                if (!$wasReleased && $isReleased) {
                    $this->cancellation->cancel($order);
                }

                // Reopening takes them out of stock again (if there is enough) and counts the coupon again.
                if ($wasReleased && !$isReleased) {
                    $this->cancellation->reopen($order);
                }

                $order->update(['status' => $new]);
            });
        } catch (OutOfStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        UserLog::record('order.status', 'Order ' . $order->number . ': ' . $old . ' -> ' . $new, $order);

        return back()->with('success', 'Status changed to "' . $new . '".');
    }
}
