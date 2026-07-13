<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardQueryService;
use App\Support\BranchScope;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardQueryService $queryService,
    ) {
    }

    public function index()
    {
        $branchId = BranchScope::scopedBranchIdFor(auth()->user());
        $branchOptions = BranchScope::optionsFor(auth()->user());
        $selectedBranch = $branchId ? $branchOptions->firstWhere('id', $branchId) : null;
        $dashboard = $this->queryService->build(now(), $branchId);

        return view('admin.panel_admin', [
            ...$dashboard,
            'branchId' => $branchId,
            'branchOptions' => $branchOptions,
            'selectedBranch' => $selectedBranch,
            'branchScopeLabel' => $selectedBranch->name ?? 'Semua Cabang',
            'branchScopeDescription' => $selectedBranch
                ? 'Data operasional mengikuti cabang aktif yang dipilih.'
                : 'Data operasional menampilkan gabungan cabang yang dapat diakses.',
            'activeBranchCount' => $branchOptions->count(),
        ]);
    }
}
