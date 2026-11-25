@extends('layouts.admin')

@section('pageTitle') Users @endsection

@section('pageContent')
    <h1 class="h3 mb-3">Users</h1>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-sm-auto">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email...">
        </div>
        <div class="col-sm-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All users</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive (banned)</option>
            </select>
        </div>
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Orders</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                @php($isMe = (int) $user->id === (int) auth()->id())
                <tr @class(['table-secondary' => $user->trashed(), 'admin-row-new' => $user->created_at?->gt(now()->subDay())])>
                    <td class="fw-semibold">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td class="small">{{ $user->created_at->format('d.m.Y.') }}</td>
                    <td>{{ $user->orders_count }}</td>
                    <td>
                        @if($isMe)
                            <span class="badge bg-dark">{{ $user->role->name }} (you)</span>
                        @elseif($user->trashed())
                            <span class="text-secondary">{{ $user->role->name }}</span>
                        @else
                            <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                @csrf
                                @method('PATCH')
                                <select name="role_id" class="form-select form-select-sm admin-role-select" onchange="this.form.submit()">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected((int) $user->role_id === (int) $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </td>
                    <td>
                        @if($user->trashed())
                            <span class="badge bg-danger" title="Since {{ $user->deleted_at->format('d.m.Y. H:i') }}">Inactive</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($user->trashed())
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-person-check"></i> Activate</button>
                            </form>
                        @elseif(!$isMe)
                            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                  onsubmit="return confirm('Deactivate {{ addslashes($user->email) }}? They will be logged out and can not log in any more.')">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-slash"></i> Deactivate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
@endsection
