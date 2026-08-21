<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\QrisConfig;
use App\Services\QrisConfigManager;
use App\Support\BranchScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class QrisConfigController extends Controller
{
    public function __construct(private readonly QrisConfigManager $configManager) {}

    public function index(Request $request): View
    {
        $this->ensureManager($request);
        $isOwner = $this->isOwnerRoute($request);
        $branches = $isOwner
            ? BranchScope::options()
            : BranchScope::optionsFor($request->user());

        $selectedBranchId = $isOwner
            ? BranchScope::requestBranchId((int) $request->integer('branch_id'))
            : BranchScope::scopedBranchIdFor($request->user());
        $selectedBranchId ??= (int) ($branches->first()->id ?? 0);

        if ($selectedBranchId <= 0 || ! $branches->contains('id', $selectedBranchId)) {
            abort(403);
        }

        $selectedBranch = Branch::query()->whereKey($selectedBranchId)->firstOrFail();
        $history = QrisConfig::query()
            ->where('branch_id', $selectedBranchId)
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->latest('id')
            ->limit(25)
            ->get([
                'id', 'branch_id', 'merchant_name', 'merchant_display_name', 'merchant_city', 'is_active',
                'activated_at', 'deactivated_at', 'created_by', 'updated_by',
                'created_at', 'updated_at',
            ]);

        return view('qris_configs.index', [
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'activeConfig' => $history->firstWhere('is_active', true),
            'history' => $history,
            'routePrefix' => $isOwner ? 'owner' : 'admin',
            'isOwner' => $isOwner,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager($request);
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'qris_payload' => ['required', 'string', 'max:5000'],
        ], [
            'qris_payload.required' => 'Pilih gambar QRIS atau masukkan payload QRIS terlebih dahulu.',
            'qris_payload.max' => 'Payload QRIS terlalu panjang.',
        ]);

        $branch = $this->managedBranch($request, (int) ($validated['branch_id'] ?? 0));

        try {
            $config = $this->configManager->replace($branch, $validated['qris_payload'], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['qris_payload' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->routeName($request, 'qris.index'), $this->routeParameters($request, $branch))
            ->with('success', 'QRIS '.($config->merchant_display_name ?: $config->merchant_name)." berhasil diaktifkan untuk {$branch->name}.");
    }

    public function activate(Request $request, QrisConfig $qrisConfig): RedirectResponse
    {
        $this->ensureManager($request);
        $branch = $this->managedBranch($request, (int) $qrisConfig->branch_id);
        if ((int) $qrisConfig->branch_id !== (int) $branch->id) {
            abort(403);
        }

        try {
            $this->configManager->activate($qrisConfig, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['qris' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->routeName($request, 'qris.index'), $this->routeParameters($request, $branch))
            ->with('success', 'QRIS dari histori berhasil diaktifkan kembali.');
    }

    public function deactivate(Request $request, QrisConfig $qrisConfig): RedirectResponse
    {
        $this->ensureManager($request);
        $branch = $this->managedBranch($request, (int) $qrisConfig->branch_id);
        if ((int) $qrisConfig->branch_id !== (int) $branch->id) {
            abort(403);
        }
        $this->configManager->deactivate($qrisConfig, $request->user());

        return redirect()
            ->route($this->routeName($request, 'qris.index'), $this->routeParameters($request, $branch))
            ->with('success', 'QRIS cabang berhasil dinonaktifkan.');
    }

    private function ensureManager(Request $request): void
    {
        $role = strtolower(trim((string) optional($request->user()?->role)->name));
        if (! in_array($role, ['owner', 'admin', 'developer'], true)) {
            abort(403);
        }
    }

    private function managedBranch(Request $request, int $requestedBranchId): Branch
    {
        $isOwner = $this->isOwnerRoute($request);
        $branchId = $isOwner
            ? BranchScope::requestBranchId($requestedBranchId)
            : BranchScope::scopedBranchIdFor($request->user());

        if (! $branchId) {
            abort(403);
        }

        if (! $isOwner && ! in_array($branchId, BranchScope::assignedBranchIds($request->user()), true)) {
            abort(403);
        }

        if (! $isOwner && $requestedBranchId > 0 && $requestedBranchId !== $branchId) {
            abort(403);
        }

        if ($isOwner && $requestedBranchId > 0 && $branchId !== $requestedBranchId) {
            abort(403);
        }

        return Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
    }

    private function isOwnerRoute(Request $request): bool
    {
        return $request->routeIs('owner.*');
    }

    private function routeName(Request $request, string $suffix): string
    {
        return ($this->isOwnerRoute($request) ? 'owner.' : 'admin.').$suffix;
    }

    private function routeParameters(Request $request, Branch $branch): array
    {
        return $this->isOwnerRoute($request) ? ['branch_id' => $branch->id] : [];
    }
}
