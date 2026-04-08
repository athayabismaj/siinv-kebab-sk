<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportExportController extends Controller
{
    public function index(Request $request)
    {
        $scope = $this->resolveScope($request);

        $exports = ReportExport::query()
            ->where('requested_by', auth()->id())
            ->where('scope', $scope)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('shared.exports.index', [
            'exports' => $exports,
            'scope' => $scope,
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
