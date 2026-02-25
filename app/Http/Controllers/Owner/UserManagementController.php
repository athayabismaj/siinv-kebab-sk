<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * List user aktif (admin & kasir saja)
     */
    public function index()
    {
        $users = User::with('role')
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'kasir']);
            })
            ->paginate(10);

        return view('owner.user_management.index', compact('users'));
    }

    /**
     * Form tambah user
     */
    public function create()
    {
        $roles = Role::whereIn('name', ['admin', 'kasir'])->get();

        return view('owner.user_management.create', compact('roles'));
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Pastikan tidak bisa membuat owner
        $role = Role::findOrFail($request->role_id);
        if ($role->name === 'owner') {
            abort(403, 'Tidak diizinkan membuat role owner.');
        }

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Form edit
     */
    public function edit(User $user)
    {
        // Tidak boleh edit owner
        if ($user->role->name === 'owner') {
            abort(403);
        }

        $roles = Role::whereIn('name', ['admin', 'kasir'])->get();

        return view('owner.user_management.edit', compact('user', 'roles'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        if ($user->role->name === 'owner') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        if ($role->name === 'owner') {
            abort(403, 'Tidak diizinkan mengubah menjadi owner.');
        }

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }



    public function showResetForm(User $user){
    return view('owner.user_management.forget_password', compact('user'));
    }

    public function resetPassword(Request $request, User $user) {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return redirect()->route('owner.users.index')
            ->with('success', 'Password berhasil direset.');
    }


    /*** Soft delete ***/
    public function destroy(User $user) {
        if ($user->role->name === 'owner') {
            abort(403);
        }

        $user->delete();

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dinonaktifkan.');
    }

    /*** Halaman arsip ***/
    public function archive() {
        $users = User::onlyTrashed()
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'kasir']);
            })
            ->with('role')
            ->paginate(10);

        return view('owner.archives.user', compact('users'));
    }

    /*** Restore user
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->role->name === 'owner') {
            abort(403);
        }

        $user->restore();

        return redirect()->route('owner.users.archive')
            ->with('success', 'User berhasil diaktifkan kembali.');
    }
}