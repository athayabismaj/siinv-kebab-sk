<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRetentionService;
use Illuminate\Console\Command;

class BackupPruneCommand extends Command
{
    protected $signature = 'backup:prune {--delete : Delete validated retention candidates after the dry-run review}';

    protected $description = 'Review or prune only verified backup artifacts in private storage.';

    public function handle(BackupRetentionService $retention): int
    {
        $result = $retention->prune((bool) $this->option('delete'));
        $this->line(sprintf('valid=%d candidates=%d deleted=%d', $result['valid'], count($result['candidates']), count($result['deleted'])));

        foreach ($result['candidates'] as $candidate) {
            $this->line('candidate='.basename($candidate));
        }

        return self::SUCCESS;
    }
}
