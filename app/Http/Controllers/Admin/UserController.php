<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // withTrashed(): deactivated (banned) users are listed too, so they can be activated again.
        $users = User::withTrashed()
            ->with('role')
            ->withCount('orders')
            ->when($request->status === 'active', fn ($q) => $q->whereNull('deleted_at'))
            ->when($request->status === 'inactive', fn ($q) => $q->whereNotNull('deleted_at'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('email', 'like', '%' . $request->q . '%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role_id' => 'required|exists:roles,id,deleted_at,NULL']);

        // An admin can't remove their own admin access by mistake.
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You can not change your own role.');
        }

        $user->role_id = $request->role_id;
        $user->save();

        UserLog::record('user.role', 'Changed role of ' . $user->email . ' to ' . $user->fresh()->role->name, $user);

        return back()->with('success', 'Role changed.');
    }

    // Deactivate (ban): fills deleted_at. The user can't log in any more and is logged out
    // on their next request, because the login only finds users without deleted_at.
    public function deactivate(User $user)
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You can not deactivate your own account.');
        }

        $user->delete(); // soft delete = sets deleted_at

        // Also end the sessions they have open right now (sessions are stored in the database).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        UserLog::record('user.deactivated', 'Deactivated user ' . $user->email, $user);

        return back()->with('success', $user->email . ' is deactivated and can no longer log in.');
    }

    // Activate again: empties deleted_at.
    public function activate(User $user)
    {
        $user->restore();

        UserLog::record('user.activated', 'Activated user ' . $user->email, $user);

        return back()->with('success', $user->email . ' is active again.');
    }
}
