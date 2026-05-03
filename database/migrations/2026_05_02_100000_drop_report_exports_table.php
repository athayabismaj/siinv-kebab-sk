<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('report_exports')) {
            Schema::drop('report_exports');
        }
    }

    public function down(): void
    {
        // Table intentionally not recreated here because export queue feature has been retired.
    }
};
