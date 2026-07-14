<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

class BackupRetentionService
{
    public function __construct(
        private readonly BackupFilesystem $filesystem,
        private readonly BackupManifestService $manifests,
    ) {
    }

    /** @return array{valid:int,candidates:array<int, string>,deleted:array<int, string>} */
    public function prune(bool $delete = false): array
    {
        $artifactsRoot = $this->filesystem->path(trim((string) config('backup.directory'), '/').'/artifacts');
        if (! is_dir($artifactsRoot)) {
            return ['valid' => 0, 'candidates' => [], 'deleted' => []];
        }

        $artifacts = collect(File::directories($artifactsRoot))
            ->filter(fn (string $directory) => ! is_link($directory))
            ->map(fn (string $directory) => $this->validArtifact($directory))
            ->filter()
            ->sortByDesc(fn (array $artifact) => $artifact['created_at'])
            ->values();

        $keep = $this->retainedDirectories($artifacts->all());
        $candidates = $artifacts->pluck('directory')->reject(fn (string $directory) => in_array($directory, $keep, true))->values()->all();
        $deleted = [];

        if ($delete) {
            foreach ($candidates as $directory) {
                $this->filesystem->deleteDirectory($directory);
                $deleted[] = $directory;
            }
        }

        return ['valid' => $artifacts->count(), 'candidates' => $candidates, 'deleted' => $deleted];
    }

    /** @return null|array{directory:string,created_at:CarbonImmutable} */
    private function validArtifact(string $directory): ?array
    {
        $dumps = File::files($directory);
        foreach ($dumps as $dump) {
            $path = $dump->getPathname();
            if (str_ends_with($path, '.manifest.json') || is_link($path)) {
                continue;
            }

            try {
                $manifest = $this->manifests->verify($path);

                return [
                    'directory' => $directory,
                    'created_at' => CarbonImmutable::parse((string) ($manifest['created_at'] ?? '')),
                ];
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /** @param array<int, array{directory:string,created_at:CarbonImmutable}> $artifacts @return array<int, string> */
    private function retainedDirectories(array $artifacts): array
    {
        $retention = (array) config('backup.retention');
        $keep = [];

        foreach (array_slice($artifacts, 0, max(0, (int) ($retention['daily'] ?? 0))) as $artifact) {
            $keep[] = $artifact['directory'];
        }

        $weekly = 0;
        $weeklyPeriods = [];
        foreach ($artifacts as $artifact) {
            $period = $artifact['created_at']->format('o-W');
            if ($weekly < (int) ($retention['weekly'] ?? 0) && ! isset($weeklyPeriods[$period])) {
                $keep[] = $artifact['directory'];
                $weeklyPeriods[$period] = true;
                $weekly++;
            }
        }

        $monthly = 0;
        $monthlyPeriods = [];
        foreach ($artifacts as $artifact) {
            $period = $artifact['created_at']->format('Y-m');
            if ($monthly < (int) ($retention['monthly'] ?? 0) && ! isset($monthlyPeriods[$period])) {
                $keep[] = $artifact['directory'];
                $monthlyPeriods[$period] = true;
                $monthly++;
            }
        }

        foreach (array_slice($artifacts, 0, max(1, (int) ($retention['minimum_valid'] ?? 1))) as $artifact) {
            $keep[] = $artifact['directory'];
        }

        return array_values(array_unique($keep));
    }
}
