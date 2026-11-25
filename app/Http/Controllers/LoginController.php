<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // Wrong passwords allowed within LOCK_SECONDS before the login is blocked until that time passes.
    public const MAX_WRONG_PER_EMAIL = 5;
    public const MAX_WRONG_PER_IP = 20;
    public const LOCK_SECONDS = 900; // 15 minutes

    public function index()
    {
        return view('pages.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $email = Str::lower($request->email);

        // Brute-force protection, counting only wrong passwords. Two limits:
        //  - per email: one account can't be guessed, even from many IPs
        //  - per IP: one IP can't try a few passwords on many accounts
        // Checked before any password check (also the banned one below), so a locked visitor learns nothing.
        $limits = [
            'login-email:' . $email => self::MAX_WRONG_PER_EMAIL,
            'login-ip:' . $request->ip() => self::MAX_WRONG_PER_IP,
        ];
        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
                UserLog::recordFailure('login.locked', 'Login blocked for ' . $email . ': too many wrong passwords' . (str_starts_with($key, 'login-ip:') ? ' from this IP' : ' for this email'), User::withTrashed()->where('email', $email)->first());

                return redirect()->back()
                    ->withErrors(['email' => 'Previše pogrešnih pokušaja. Pokušajte ponovo za ' . \App\Support\Sr::plural($minutes, 'minut', 'minuta', 'minuta') . '.'])
                    ->withInput($request->only('email'));
            }
        }

        // Deactivated (banned) users: Auth::attempt never finds them because deleted_at is filled.
        // Tell them why only when the password is right, so nobody can probe which emails are banned.
        $banned = User::onlyTrashed()->where('email', $request->email)->first();

        if ($banned && Hash::check($request->password, $banned->password)) {
            UserLog::recordFailure('login.failed', 'Deactivated account ' . $email . ' tried to log in', $banned);

            return redirect()->back()
                ->withErrors(['email' => 'Ovaj nalog je deaktiviran. Kontaktirajte nas ako mislite da je u pitanju greška.'])
                ->withInput($request->only('email'));
        }

        // Auth::attempt checks the email + hashed password and logs the user in.
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            foreach ($limits as $key => $max) {
                RateLimiter::hit($key, self::LOCK_SECONDS);
            }

            $account = User::withTrashed()->where('email', $email)->first();
            UserLog::recordFailure('login.failed', ($account ? 'Wrong password for ' : 'Unknown email ') . $email, $account);

            return redirect()->back()
                ->withErrors(['email' => 'Pogrešan email ili lozinka.'])
                ->withInput($request->only('email'));
        }

        // Right password: this account's count starts again (the IP count stays, so logging into
        // your own account doesn't reset guessing on others).
        RateLimiter::clear('login-email:' . $email);

        // New session id after login (protects against session fixation).
        $request->session()->regenerate();

        UserLog::record('login', 'Logged in');

        // Admins go to the admin panel, customers back to where they were going.
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        UserLog::record('logout', 'Logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
