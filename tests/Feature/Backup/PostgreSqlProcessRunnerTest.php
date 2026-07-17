<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\PostgreSqlProcessRunner;
use Tests\TestCase;

class PostgreSqlProcessRunnerTest extends TestCase
{
    public function test_runner_preserves_required_windows_runtime_variables_for_child_processes(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->assertTrue(true);

            return;
        }

        $result = (new PostgreSqlProcessRunner())->run([
            PHP_BINARY,
            '-r',
            'if (getenv("SystemRoot") === false || getenv("ComSpec") === false) { fwrite(STDERR, "Windows runtime environment is incomplete"); exit(1); } echo "runtime accepted";',
        ], ['PGPASSWORD' => 'process-only-secret']);

        $this->assertTrue($result->successful());
        $this->assertSame('runtime accepted', $result->output());
        $this->assertStringNotContainsString('process-only-secret', $result->output());
        $this->assertStringNotContainsString('process-only-secret', $result->errorOutput());
    }

    public function test_runner_passes_pgpassword_through_the_process_environment_without_putting_it_in_output(): void
    {
        $secret = 'process-only-secret';
        $result = (new PostgreSqlProcessRunner())->run([
            PHP_BINARY,
            '-r',
            'if (getenv("PGPASSWORD") !== "process-only-secret") { fwrite(STDERR, "PGPASSWORD is unavailable"); exit(1); } echo "environment accepted";',
        ], ['PGPASSWORD' => $secret]);

        $this->assertTrue($result->successful());
        $this->assertSame('environment accepted', $result->output());
        $this->assertStringNotContainsString($secret, $result->output());
        $this->assertStringNotContainsString($secret, $result->errorOutput());
    }
}
