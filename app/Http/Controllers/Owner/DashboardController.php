<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\DashboardQueryService;
use App\Support\BranchScope;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardQueryService $queryService
    ) {}

    public function index()
    {
        $branchOptions = BranchScope::options();
        $branchId = BranchScope::ownerBranchId();

        return view('owner.panel_owner', $this->queryService->buildDashboardData($branchId, $branchOptions));
    }
}
