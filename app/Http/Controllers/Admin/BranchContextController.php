<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
        ]);

        if (! BranchScope::switchActiveBranch($request->user(), (int) $validated['branch_id'])) {
            return back()->with('error', 'Cabang tidak tersedia untuk akun admin ini.');
        }

        return back()->with('success', 'Cabang aktif berhasil diganti.');
    }
}
