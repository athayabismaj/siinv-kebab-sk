<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\DashboardQueryService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardQueryService $queryService
    ) {}

    public function index()
    {
        return view('owner.panel_owner', $this->queryService->buildDashboardData());
    }
}
