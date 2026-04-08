<?php

namespace App\Services;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\System\OperationWindowService;

class ReportExportDispatchService
{
    public function __construct(
        private readonly OperationWindowService $operationWindow
    ) {
    }

    public function dispatch(User $user, string $scope, string $type, array $filters): ReportExport
    {
        $shouldDefer = (bool) config('operations.defer_heavy_exports_during_ops', true)
            && $this->operationWindow->isOperationalNow();

        $scheduledFor = $shouldDefer
            ? $this->operationWindow->nextOffPeakRunAt()
            : null;

        $export = ReportExport::query()->create([
            'requested_by' => $user->id,
            'scope' => $scope,
            'type' => $type,
            'filters' => $filters,
            'status' => 'queued',
            'scheduled_for' => $scheduledFor,
        ]);

        $job = GenerateReportExportJob::dispatch($export->id);
        if ($scheduledFor) {
            $job->delay($scheduledFor);
        }

        return $export;
    }
}
