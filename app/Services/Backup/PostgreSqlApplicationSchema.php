<?php

namespace App\Services\Backup;

use RuntimeException;

class PostgreSqlApplicationSchema
{
    /** @var array<int, string> */
    private const RESERVED_SCHEMAS = [
        'auth',
        'extensions',
        'graphql',
        'graphql_public',
        'information_schema',
        'pgbouncer',
        'realtime',
        'storage',
        'vault',
    ];

    /** @param array<string, mixed> $connection */
    public function resolve(array $connection, ?string $requestedSchema = null): string
    {
        $configuredSchema = config('backup.application_schema');
        $searchPath = $requestedSchema
            ?? (is_string($configuredSchema) && trim($configuredSchema) !== '' ? $configuredSchema : null)
            ?? ($connection['search_path'] ?? 'public');

        if (is_array($searchPath)) {
            $searchPath = $searchPath[0] ?? '';
        }

        if (! is_string($searchPath)) {
            throw new RuntimeException('The PostgreSQL application schema configuration is invalid.');
        }

        preg_match('/[^\s,"\']+/', $searchPath, $matches);
        $schema = trim((string) ($matches[0] ?? ''), '\'"');

        return $this->assertPortable($schema);
    }

    public function assertPortable(string $schema): string
    {
        $schema = trim($schema);

        if ($schema === ''
            || ! preg_match('/^[a-z_][a-z0-9_]*$/', $schema)
            || str_starts_with($schema, 'pg_')
            || in_array($schema, self::RESERVED_SCHEMAS, true)) {
            throw new RuntimeException('The selected PostgreSQL application schema is not portable.');
        }

        return $schema;
    }
}
