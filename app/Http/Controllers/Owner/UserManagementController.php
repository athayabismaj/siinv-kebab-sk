<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'kasir']);
            })
            ->paginate(10);

        return view('owner.user_management.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::whereIn('name', ['admin', 'kasir'])->get();

        return view('owner.user_management.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles = Role::whereIn('name', ['admin', 'kasir'])->get();

        return view('owner.user_management.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'role_id' => $request->role_id,
        ];

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete(); // Soft delete

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dinonaktifkan.');
    }
}