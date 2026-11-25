<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\UserLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    // Max 3 messages per 10 minutes, per user.
    public const MAX_MESSAGES = 3;
    public const DECAY_SECONDS = 600;

    public function create()
    {
        return view('pages.main.contact', [
            'user' => auth()->user(),
            'phone' => Setting::getValue(Setting::CONTACT_PHONE),
        ]);
    }

    public function store(ContactRequest $request)
    {
        $user = $request->user();

        // Counted only after validation, so fixing a typo isn't blocked (same as the checkout).
        $limitKey = 'contact:' . $user->id;
        if (RateLimiter::hit($limitKey, self::DECAY_SECONDS) > self::MAX_MESSAGES) {
            $minutes = (int) ceil(RateLimiter::availableIn($limitKey) / 60);
            UserLog::recordFailure('contact.limited', 'Contact limit reached: more than ' . self::MAX_MESSAGES . ' messages in ' . (self::DECAY_SECONDS / 60) . ' minutes');

            return back()->withInput()->with('error', 'Poslali ste nekoliko poruka za kratko vreme. Sačekajte ' . \App\Support\Sr::plural($minutes, 'minut', 'minuta', 'minuta') . ' pre slanja sledeće.');
        }

        $message = ContactMessage::create($request->validated() + [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        UserLog::record('contact.sent', 'Sent a message: "' . $message->subject . '"', $message);

        try {
            Mail::to(Setting::contactEmail())->send(new ContactMessageMail($message));
        } catch (\Throwable $e) {
            // The message is saved in Admin > Messages either way; a mail problem must not show an error.
            UserLog::recordFailure('contact.mail', 'Contact message mail sending failed', $message);
            report($e);
        }

        return redirect()->route('contact')->with('success', 'Hvala, vaša poruka je poslata. Odgovorićemo na ' . $user->email . ' što pre.');
    }
}
