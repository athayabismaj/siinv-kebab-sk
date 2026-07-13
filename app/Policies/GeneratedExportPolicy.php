<?php

namespace App\Policies;

use App\Models\GeneratedExport;
use App\Models\User;

class GeneratedExportPolicy
{
    public function view(User $user, GeneratedExport $generatedExport): bool
    {
        return (int) $generatedExport->requested_by === (int) $user->id;
    }
}
