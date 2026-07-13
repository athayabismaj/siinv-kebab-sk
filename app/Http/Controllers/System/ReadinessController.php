<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\System\SystemHealthService;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(SystemHealthService $healthService): JsonResponse
    {
        $report = $healthService->report();

        return response()->json(['status' => $report->status], $report->httpStatus());
    }
}
