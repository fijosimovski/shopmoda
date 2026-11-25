<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Order;
use App\Models\UserLog;

class AccountController extends Controller
{
    public function orders()
    {
        $orders = auth()->user()->orders()->withCount('items')->latest()->paginate(10);

        return view('pages.account.orders', compact('orders'));
    }

    public function showOrder(Order $order)
    {
        // Users can only see their own orders.
        abort_unless((int) $order->user_id === (int) auth()->id(), 404);

        $order->load(['items.product.mainImage', 'discounts']);

        return view('pages.account.order', compact('order'));
    }

    public function editProfile()
    {
        return view('pages.account.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(ProfileRequest $request)
    {
        $user = $request->user();

        // The email can't be changed here (it's the login and where orders are sent); it isn't read from the form at all.
        $user->name = $request->name;

        if ($request->filled('password')) {
            $user->password = $request->password; // hashed by the model cast
        }

        $user->save();

        UserLog::record('profile.updated', 'Updated profile', $user);

        return redirect()->route('account.profile')->with('success', 'Profil je sačuvan.');
    }
}
