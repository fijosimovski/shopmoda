<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserLog;
use App\Services\OrderConfirmation;
use Illuminate\Http\Request;

// The page from the link in the "confirm your order" email.
// Both routes use the "signed" middleware, so the order id in the link can't be changed.
class OrderConfirmationController extends Controller
{
    public function __construct(private OrderConfirmation $confirmation)
    {
    }

    public function show(Order $order)
    {
        return view('pages.checkout.confirm', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        $request->validate(
            ['code' => 'required|digits:6'],
            ['code.digits' => 'Kod ima 6 cifara, nalazi se u emailu.']
        );

        if (!$order->isUnconfirmed()) {
            return redirect()->to($request->fullUrl());
        }

        if (!$this->confirmation->confirm($order, $request->code)) {
            UserLog::recordFailure('order.confirm_failed', 'Wrong confirmation code for order ' . $order->number, $order);

            return redirect()->to($request->fullUrl())
                ->withErrors(['code' => 'Kod nije ispravan. Proverite email i pokušajte ponovo.']);
        }

        // Same signed URL (GET), which now shows that the order is confirmed.
        return redirect()->to($request->fullUrl())->with('success', 'Porudžbina je potvrđena. Poslali smo vam email sa detaljima.');
    }
}
