<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class OwnerBranchContextController extends Controller
{
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
        ]);

        $branchId = (int) $validated['branch_id'];

        // Owner can access all active branches
        $allBranches = BranchScope::options();
        $validIds = $allBranches->pluck('id')->map(fn ($id) => (int) $id)->all();

        // branch_id = 0 means "Semua Cabang"
        if ($branchId === 0) {
            session(['owner_active_branch_id' => 0]);
            return back()->with('success', 'Menampilkan semua cabang.');
        }

        if (! in_array($branchId, $validIds, true)) {
            return back()->with('error', 'Cabang tidak tersedia.');
        }

        session(['owner_active_branch_id' => $branchId]);

        return back()->with('success', 'Cabang aktif berhasil diganti.');
    }
}
