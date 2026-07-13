<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        if (! BranchScope::hasBranchesTable()) {
            return view('owner.branches.index', [
                'branches' => collect(),
                'migrationMissing' => true,
            ]);
        }

        $branches = Branch::query()
            ->withCount([
                'transactions',
                'dailyStockSessions',
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(10);

        $branches->getCollection()->transform(function (Branch $branch) {
            $branch->operational_users_count = $this->operationalUserCount($branch);

            return $branch;
        });

        return view('owner.branches.index', [
            'branches' => $branches,
            'migrationMissing' => false,
            'summary' => [
                'total' => (int) Branch::query()->count(),
                'active' => (int) Branch::query()->where('is_active', true)->count(),
                'inactive' => (int) Branch::query()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create()
    {
        if (! BranchScope::hasBranchesTable()) {
            return redirect()
                ->route('owner.branches.index')
                ->with('error', 'Migration cabang belum dijalankan. Jalankan php artisan migrate terlebih dahulu.');
        }

        return view('owner.branches.create', [
            'branch' => new Branch(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        if (! BranchScope::hasBranchesTable()) {
            return redirect()
                ->route('owner.branches.index')
                ->with('error', 'Migration cabang belum dijalankan. Jalankan php artisan migrate terlebih dahulu.');
        }

        $validated = $this->validateBranch($request);

        Branch::query()->create($this->payload($validated));

        return redirect()
            ->route('owner.branches.index')
            ->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Branch $branch)
    {
        return view('owner.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $this->validateBranch($request, $branch);

        $branch->update($this->payload($validated));

        return redirect()
            ->route('owner.branches.index')
            ->with('success', 'Cabang berhasil diperbarui.');
    }

    public function toggle(Branch $branch)
    {
        if ($branch->is_active && $this->activeBranchCount() <= 1) {
            return back()->with('error', 'Minimal harus ada satu cabang aktif.');
        }

        $branch->update([
            'is_active' => ! $branch->is_active,
        ]);

        return back()->with(
            'success',
            $branch->is_active ? 'Cabang berhasil diaktifkan.' : 'Cabang berhasil dinonaktifkan.'
        );
    }

    private function validateBranch(Request $request, ?Branch $branch = null): array
    {
        if ($request->filled('code')) {
            $request->merge([
                'code' => Str::slug((string) $request->input('code')),
            ]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($branch?->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'code.unique' => 'Kode cabang sudah digunakan.',
        ]);
    }

    private function payload(array $validated): array
    {
        $code = trim((string) ($validated['code'] ?? ''));

        return [
            'name' => trim((string) $validated['name']),
            'code' => $code !== '' ? Str::slug($code) : $this->uniqueCodeFromName((string) $validated['name']),
            'address' => $validated['address'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function uniqueCodeFromName(string $name): string
    {
        $normalizedName = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim();
        $words = preg_split('/\s+/', (string) $normalizedName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $locationWords = array_values(array_filter($words, fn (string $word) => ! in_array($word, [
            'kebab', 'sk', 'cabang', 'branch', 'outlet',
        ], true)));
        $source = end($locationWords) ?: end($words) ?: 'cabang';
        $base = $this->abbreviateBranchCode((string) $source);
        $code = $base;
        $counter = 2;

        while (Branch::query()->where('code', $code)->exists()) {
            $code = $base . $counter;
            $counter++;
        }

        return $code;
    }

    private function abbreviateBranchCode(string $source): string
    {
        $source = Str::lower(Str::ascii($source));

        if (strlen($source) <= 3) {
            return $source ?: 'cabang';
        }

        $code = preg_replace('/[aiueo]/', '', $source) ?: '';

        foreach (array_reverse(str_split($source)) as $character) {
            if (strlen($code) >= 3) {
                break;
            }

            if (! str_contains($code, $character)) {
                $code .= $character;
            }
        }

        return Str::limit($code ?: $source, 12, '');
    }

    private function activeBranchCount(): int
    {
        if (! Schema::hasTable('branches')) {
            return 0;
        }

        return (int) Branch::query()->where('is_active', true)->count();
    }

    private function operationalUserCount(Branch $branch): int
    {
        $query = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn(DB::raw('LOWER(TRIM(roles.name))'), ['admin', 'kasir']);

        if (BranchScope::supportsUserBranchAssignments()) {
            $query->leftJoin('branch_user', 'branch_user.user_id', '=', 'users.id')
                ->where(function ($scope) use ($branch) {
                    $scope->where('users.branch_id', $branch->id)
                        ->orWhere('branch_user.branch_id', $branch->id);
                });
        } else {
            $query->where('users.branch_id', $branch->id);
        }

        return (int) $query->distinct()->count('users.id');
    }
}
