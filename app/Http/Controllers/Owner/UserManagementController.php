<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    private const MANAGED_ROLE_NAMES = ['admin', 'kasir'];

    public function index()
    {
        $users = User::query()
            ->with('role')
            ->whereHas('role', fn ($q) => $q->whereIn('name', self::MANAGED_ROLE_NAMES))
            ->paginate(10);

        return view('owner.user_management.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->managedRoles();

        return view('owner.user_management.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules());
        $roleId = (int) $validated['role_id'];

        $this->ensureRoleAssignable($roleId, 'Tidak diizinkan membuat role owner.');

        User::create($this->buildPayload($validated, true));

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $this->ensureUserManageable($user);

        $roles = $this->managedRoles();

        return view('owner.user_management.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureUserManageable($user);

        $validated = $request->validate($this->updateRules($user));
        $roleId = (int) $validated['role_id'];

        $this->ensureRoleAssignable($roleId, 'Tidak diizinkan mengubah menjadi owner.');

        $user->update($this->buildPayload($validated, false));

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function showResetForm(User $user)
    {
        $this->ensureUserManageable($user);

        return view('owner.user_management.forget_password', compact('user'));
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->ensureUserManageable($user);

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make((string) $request->input('password')),
        ]);

        return redirect()->route('owner.users.index')
            ->with('success', 'Password berhasil direset.');
    }

    public function destroy(User $user)
    {
        $this->ensureUserManageable($user);

        $user->delete();

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dinonaktifkan.');
    }

    public function archive()
    {
        $users = User::onlyTrashed()
            ->whereHas('role', fn ($q) => $q->whereIn('name', self::MANAGED_ROLE_NAMES))
            ->with('role')
            ->paginate(10);

        return view('owner.archives.user', compact('users'));
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->ensureUserManageable($user);

        $user->restore();

        return redirect()->route('owner.users.archive')
            ->with('success', 'User berhasil diaktifkan kembali.');
    }

    private function storeRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ];
    }

    private function updateRules(User $user): array
    {
        return [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|min:6',
        ];
    }

    private function buildPayload(array $validated, bool $requirePassword): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role_id' => (int) $validated['role_id'],
        ];

        $hasPassword = array_key_exists('password', $validated)
            && $validated['password'] !== null
            && $validated['password'] !== '';

        if ($requirePassword || $hasPassword) {
            $payload['password'] = Hash::make((string) ($validated['password'] ?? ''));
        }

        return $payload;
    }

    private function ensureRoleAssignable(int $roleId, string $message): void
    {
        $role = Role::findOrFail($roleId);

        if (! in_array((string) $role->name, self::MANAGED_ROLE_NAMES, true)) {
            abort(403, $message);
        }
    }

    private function ensureUserManageable(User $user): void
    {
        $roleName = (string) optional($user->role)->name;

        if (! in_array($roleName, self::MANAGED_ROLE_NAMES, true)) {
            abort(403);
        }
    }

    private function managedRoles()
    {
        return Role::query()
            ->whereIn('name', self::MANAGED_ROLE_NAMES)
            ->get();
    }
}
