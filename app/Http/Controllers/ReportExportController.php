<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportExportController extends Controller
{
    public function index(Request $request)
    {
        $scope = $this->resolveScope($request);

        $runtimeError = null;
        if (! Schema::hasTable('report_exports')) {
            $runtimeError = 'Riwayat ekspor belum dapat dimuat. Jalankan migrasi export terlebih dahulu.';
            $exports = new LengthAwarePaginator(
                new Collection(),
                0,
                15,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );

            return view('shared.exports.index', [
                'exports' => $exports,
                'scope' => $scope,
                'runtimeError' => $runtimeError,
            ]);
        }

        try {
            $exports = ReportExport::query()
                ->where('requested_by', auth()->id())
                ->where('scope', $scope)
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        } catch (Throwable $e) {
            Log::error('Failed to load export history', [
                'scope' => $scope,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            $runtimeError = 'Riwayat ekspor belum dapat dimuat. Jalankan migrasi export terlebih dahulu.';
            $exports = new LengthAwarePaginator(
                new Collection(),
                0,
                15,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        }

        return view('shared.exports.index', [
            'exports' => $exports,
            'scope' => $scope,
            'runtimeError' => $runtimeError,
        ]);
    }

    public function download(Request $request, ReportExport $reportExport)
    {
        abort_unless($reportExport->requested_by === auth()->id(), 403);

        $scope = $this->resolveScope($request);
        abort_unless($reportExport->scope === $scope, 403);
        abort_unless($reportExport->status === 'completed' && ! empty($reportExport->file_path), 404);
        abort_unless(Storage::disk('local')->exists($reportExport->file_path), 404);

        return Storage::disk('local')->download(
            $reportExport->file_path,
            $reportExport->file_name ?? ('report-export-' . $reportExport->id . '.csv')
        );
    }

    private function resolveScope(Request $request): string
    {
        if ($request->routeIs('admin.*')) {
            return 'admin';
        }

        if ($request->routeIs('owner.*')) {
            return 'owner';
        }

        abort(404);
    }
}
