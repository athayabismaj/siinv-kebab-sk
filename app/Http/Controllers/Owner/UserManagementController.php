<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Role;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    private const MANAGED_ROLE_NAMES = ['admin', 'kasir'];

    public function index()
    {
        $usesBranches = BranchScope::supportsUserBranches();
        $usesBranchAssignments = BranchScope::supportsUserBranchAssignments();

        $with = ['role'];
        if ($usesBranches) {
            $with[] = 'branch:id,name,code';
        }
        if ($usesBranchAssignments) {
            $with[] = 'assignedBranches:id,name,code';
        }

        $users = User::query()
            ->with($with)
            ->whereHas('role', fn ($q) => $this->whereManagedRole($q))
            ->paginate(10);

        return view('owner.user_management.index', compact('users', 'usesBranches', 'usesBranchAssignments'));
    }

    public function create()
    {
        $roles = $this->managedRoles();
        $branches = BranchScope::options();
        $usesBranches = BranchScope::supportsUserBranches();
        $usesBranchAssignments = BranchScope::supportsUserBranchAssignments();
        $selectedBranchIds = collect(old('branch_ids', []))->map(fn ($id) => (int) $id)->all();

        return view('owner.user_management.create', compact('roles', 'branches', 'usesBranches', 'usesBranchAssignments', 'selectedBranchIds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules($request), $this->validationMessages());
        $roleId = (int) $validated['role_id'];

        $this->ensureRoleAssignable($roleId, 'Tidak diizinkan membuat role owner.');

        DB::transaction(function () use ($validated) {
            $user = User::create($this->buildPayload($validated, true));
            $this->syncBranchAssignments($user, $validated);
        });

        return redirect()->route('owner.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $this->ensureUserManageable($user);

        $roles = $this->managedRoles();
        $branches = BranchScope::options();
        $usesBranches = BranchScope::supportsUserBranches();
        $usesBranchAssignments = BranchScope::supportsUserBranchAssignments();
        $selectedBranchIds = $this->selectedBranchIdsFor($user);

        return view('owner.user_management.edit', compact('user', 'roles', 'branches', 'usesBranches', 'usesBranchAssignments', 'selectedBranchIds'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureUserManageable($user);

        $validated = $request->validate($this->updateRules($request, $user), $this->validationMessages());
        $roleId = (int) $validated['role_id'];

        $this->ensureRoleAssignable($roleId, 'Tidak diizinkan mengubah menjadi owner.');

        $passwordChanged = array_key_exists('password', $validated)
            && $validated['password'] !== null
            && $validated['password'] !== '';

        DB::transaction(function () use ($user, $validated) {
            $user->update($this->buildPayload($validated, false));
            $this->syncBranchAssignments($user, $validated);
        });

        if ($passwordChanged) {
            ApiToken::where('user_id', $user->id)->delete();
        }

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

        ApiToken::where('user_id', $user->id)->delete();

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
            ->whereHas('role', fn ($q) => $this->whereManagedRole($q))
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

    private function storeRules(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ];

        $this->addBranchRules($rules, $request);

        return $rules;
    }

    private function updateRules(Request $request, User $user): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|min:6',
        ];

        $this->addBranchRules($rules, $request);

        return $rules;
    }

    private function addBranchRules(array &$rules, Request $request): void
    {
        if (! BranchScope::supportsUserBranches()) {
            return;
        }

        $roleName = $this->roleNameFromId((int) $request->input('role_id'));

        if ($roleName === 'admin' && BranchScope::supportsUserBranchAssignments()) {
            $rules['branch_id'] = 'nullable|integer|exists:branches,id';
            $rules['branch_ids'] = 'required|array|min:1';
            $rules['branch_ids.*'] = 'integer|exists:branches,id';

            return;
        }

        $rules['branch_id'] = 'required|integer|exists:branches,id';
        $rules['branch_ids'] = 'nullable|array';
        $rules['branch_ids.*'] = 'integer|exists:branches,id';
    }

    private function buildPayload(array $validated, bool $requirePassword): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role_id' => (int) $validated['role_id'],
        ];

        if (BranchScope::supportsUserBranches()) {
            $payload['branch_id'] = (int) (
                $validated['branch_id']
                ?? collect($validated['branch_ids'] ?? [])->first()
                ?? BranchScope::defaultBranchId()
            );
        }

        $hasPassword = array_key_exists('password', $validated)
            && $validated['password'] !== null
            && $validated['password'] !== '';

        if ($requirePassword || $hasPassword) {
            $payload['password'] = Hash::make((string) ($validated['password'] ?? ''));
        }

        return $payload;
    }

    private function syncBranchAssignments(User $user, array $validated): void
    {
        if (! BranchScope::supportsUserBranchAssignments()) {
            return;
        }

        $role = Role::find((int) $validated['role_id']);
        $roleName = $this->normalizeRoleName($role?->name);

        if ($roleName !== 'admin') {
            $user->assignedBranches()->sync([]);

            return;
        }

        $branchIds = collect($validated['branch_ids'] ?? [])
            ->push($validated['branch_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($branchIds)) {
            $branchIds = array_filter([(int) BranchScope::defaultBranchId()]);
        }

        $user->assignedBranches()->sync($branchIds);
    }

    private function selectedBranchIdsFor(User $user): array
    {
        if (! BranchScope::supportsUserBranchAssignments()) {
            return [];
        }

        $branchIds = $user->assignedBranches()
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($user->branch_id) {
            $branchIds[] = (int) $user->branch_id;
        }

        return array_values(array_unique($branchIds));
    }

    private function ensureRoleAssignable(int $roleId, string $message): void
    {
        $role = Role::findOrFail($roleId);

        if (! in_array($this->normalizeRoleName($role->name), self::MANAGED_ROLE_NAMES, true)) {
            abort(403, $message);
        }
    }

    private function ensureUserManageable(User $user): void
    {
        $roleName = $this->normalizeRoleName(optional($user->role)->name);

        if (! in_array($roleName, self::MANAGED_ROLE_NAMES, true)) {
            abort(403);
        }
    }

    private function managedRoles()
    {
        return Role::query()
            ->whereIn(DB::raw('LOWER(TRIM(name))'), self::MANAGED_ROLE_NAMES)
            ->orderByRaw("CASE LOWER(TRIM(name)) WHEN 'admin' THEN 1 WHEN 'kasir' THEN 2 ELSE 3 END")
            ->get();
    }

    private function whereManagedRole($query): void
    {
        $query->whereIn(DB::raw('LOWER(TRIM(name))'), self::MANAGED_ROLE_NAMES);
    }

    private function normalizeRoleName(?string $name): string
    {
        return Str::lower(trim((string) $name));
    }

    private function roleNameFromId(int $roleId): string
    {
        if ($roleId <= 0) {
            return '';
        }

        return $this->normalizeRoleName(Role::query()->whereKey($roleId)->value('name'));
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan. Gunakan username lain atau cek arsip user.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan. Gunakan email lain atau cek arsip user.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak tersedia.',
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang yang dipilih tidak tersedia.',
            'branch_ids.required' => 'Minimal satu cabang admin wajib dipilih.',
            'branch_ids.min' => 'Minimal satu cabang admin wajib dipilih.',
            'branch_ids.array' => 'Akses cabang admin tidak valid.',
            'branch_ids.*.exists' => 'Salah satu cabang admin tidak tersedia.',
        ];
    }
}
