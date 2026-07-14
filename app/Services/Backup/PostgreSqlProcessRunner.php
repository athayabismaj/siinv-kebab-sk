<?php

namespace App\Services\Backup;

use Closure;
use Illuminate\Support\Facades\Process;

class PostgreSqlProcessRunner
{
    /** @var array<int, array<int, string>> */
    private array $commands = [];

    /** @param null|Closure(array<int, string>, array<string, string>, int): mixed $handler */
    public function __construct(private readonly ?Closure $handler = null)
    {
    }

    /** @param array<int, string> $command @param array<string, string> $environment */
    public function run(array $command, array $environment = [], ?int $timeout = null): mixed
    {
        $this->commands[] = $command;

        if ($this->handler !== null) {
            return ($this->handler)($command, $environment, $timeout ?? (int) config('backup.timeout'));
        }

        return Process::timeout($timeout ?? (int) config('backup.timeout'))
            ->env($environment)
            ->run($command);
    }

    /** @return array<int, array<int, string>> */
    public function commands(): array
    {
        return $this->commands;
    }
}
