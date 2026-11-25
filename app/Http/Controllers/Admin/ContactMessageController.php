<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\UserLog;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = ContactMessage::query()
            ->when($request->status === 'unread', fn ($q) => $q->unread())
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('email', 'like', '%' . $request->q . '%')
                        ->orWhere('subject', 'like', '%' . $request->q . '%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.messages.index', compact('messages'));
    }

    // Opening a message marks it as read.
    public function show(ContactMessage $message)
    {
        if (!$message->isRead()) {
            $message->read_at = now();
            $message->save();
        }

        $message->load('user');

        return view('pages.admin.messages.show', compact('message'));
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        UserLog::record('contact.deleted', 'Deleted message "' . $message->subject . '" from ' . $message->email, $message);

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}
