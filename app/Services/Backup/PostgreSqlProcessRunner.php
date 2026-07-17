<?php

namespace App\Services\Backup;

use Closure;
use Illuminate\Support\Facades\Process;

class PostgreSqlProcessRunner
{
    /** @var array<int, string> */
    private const WINDOWS_RUNTIME_VARIABLES = [
        'SystemRoot',
        'WINDIR',
        'ComSpec',
        'PATH',
        'PATHEXT',
        'TEMP',
        'TMP',
        'USERPROFILE',
        'LOCALAPPDATA',
        'APPDATA',
        'ProgramData',
    ];

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
        $environment = $this->runtimeEnvironment($environment);

        if ($this->handler !== null) {
            return ($this->handler)($command, $environment, $timeout ?? (int) config('backup.timeout'));
        }

        return Process::timeout($timeout ?? (int) config('backup.timeout'))
            ->env($environment)
            ->run($command);
    }

    /** @param array<string, string> $environment @return array<string, string> */
    private function runtimeEnvironment(array $environment): array
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return $environment;
        }

        $runtimeEnvironment = [];

        foreach (self::WINDOWS_RUNTIME_VARIABLES as $variable) {
            $value = getenv($variable);

            if (is_string($value) && $value !== '') {
                $runtimeEnvironment[$variable] = $value;
            }
        }

        // Symfony's web SAPI environment can omit Windows runtime variables.
        return array_replace($runtimeEnvironment, $environment);
    }

    /** @return array<int, array<int, string>> */
    public function commands(): array
    {
        return $this->commands;
    }
}
