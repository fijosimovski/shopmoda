<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * "Forgot password":
 *   1. create() / sendCode() - the customer enters their email, a 6-digit code is mailed (only its hash is stored
 *                              in password_reset_tokens, like a password).
 *   2. edit() / update()     - they enter the code + a new password; the code then stops working and every
 *                              other session of that account is logged out.
 * The answer is the same whether the email has an account or not, so nobody can find out who is registered.
 */
class PasswordResetController extends Controller
{
    public const CODE_VALID_MINUTES = 15;
    // A new code can't be requested sooner than this (stops mail flooding).
    public const RESEND_SECONDS = 60;
    // Wrong codes allowed before the code is thrown away and a new one must be requested.
    public const MAX_WRONG_CODES = 5;

    private const SESSION_EMAIL = 'password_reset_email';

    public function create()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|string|email|max:100']);
        $email = Str::lower(trim($request->email));

        // Only active (not deactivated) accounts get a code.
        $user = User::where('email', $email)->first();
        $last = DB::table('password_reset_tokens')->where('email', $email)->first();
        $tooSoon = $last && now()->diffInSeconds($last->created_at, true) < self::RESEND_SECONDS;

        if ($user && !$tooSoon) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );
            RateLimiter::clear($this->attemptsKey($email));

            try {
                Mail::to($email)->send(new PasswordResetCodeMail($user, $code));
                UserLog::record('password.reset_requested', 'Password reset code sent to ' . $email, $user);
            } catch (\Throwable $e) {
                UserLog::recordFailure('password.reset_mail', 'Password reset code mail sending failed for ' . $email, $user);
                report($e);
            }
        } elseif (!$user) {
            UserLog::recordFailure('password.reset_unknown', 'Password reset asked for an email without an active account: ' . $email);
        }

        session([self::SESSION_EMAIL => $email]);

        return redirect()->route('password.reset')
            ->with('success', 'Ako postoji nalog za ' . $email . ', poslali smo mu šestocifreni kod. Kod važi ' . self::CODE_VALID_MINUTES . ' minuta.');
    }

    public function edit()
    {
        return view('pages.auth.reset-password', ['email' => session(self::SESSION_EMAIL)]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:100',
            'code' => 'required|string|digits:6',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'password_confirmation' => 'required|string',
        ], [
            'code.digits' => 'Kod ima 6 cifara.',
            'password_confirmation.required' => 'Ponovite lozinku.',
        ]);

        $email = Str::lower(trim($request->email));
        $key = $this->attemptsKey($email);
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        $fail = fn (string $message) => back()->withInput($request->only('email'))->withErrors(['code' => $message]);

        if (!$row || now()->diffInMinutes($row->created_at, true) >= self::CODE_VALID_MINUTES) {
            return $fail('Kod je istekao ili nije poslat. Zatražite novi kod.');
        }

        if (!Hash::check($request->code, $row->token)) {
            // After MAX_WRONG_CODES the code is deleted, so 6 digits can't be guessed.
            if (RateLimiter::hit($key, self::CODE_VALID_MINUTES * 60) >= self::MAX_WRONG_CODES) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                RateLimiter::clear($key);
                UserLog::recordFailure('password.reset_locked', 'Too many wrong password reset codes for ' . $email);

                return $fail('Previše pogrešnih kodova. Zatražite novi kod.');
            }

            UserLog::recordFailure('password.reset_failed', 'Wrong password reset code for ' . $email);

            return $fail('Pogrešan kod. Proverite email i pokušajte ponovo.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return $fail('Kod je istekao ili nije poslat. Zatražite novi kod.');
        }

        $user->password = $request->password; // hashed by the model's "hashed" cast
        $user->remember_token = Str::random(60); // "remember me" cookies stop working
        $user->save();

        // The code can't be used twice, and anyone logged in with the old password is logged out.
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        RateLimiter::clear($key);
        RateLimiter::clear('login-email:' . $email); // a lock from wrong logins ends with the new password
        session()->forget(self::SESSION_EMAIL);

        UserLog::record('password.reset', 'Password changed with an email code for ' . $email, $user);

        return redirect()->route('login')->with('success', 'Lozinka je promenjena. Sada se možete prijaviti novom lozinkom.');
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset:' . $email;
    }
}
