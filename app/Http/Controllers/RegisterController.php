<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function index()
    {
        return view('pages.auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = new User($request->validated()); // password is hashed by the model cast
        // Every new account is a customer. role_id is set here, never from the form.
        $user->role_id = Role::where('name', Role::CUSTOMER)->firstOrFail()->id;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        UserLog::record('register', 'Registered', $user);

        return redirect()->route('home')->with('success', 'Dobro došli u ShopModa, ' . $user->name . '!');
    }
}
