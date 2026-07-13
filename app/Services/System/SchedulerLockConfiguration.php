<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Schema;

class SchedulerLockConfiguration
{
    public function store(): string
    {
        $configuredStore = config('scheduler.lock_store');

        return is_string($configuredStore) && $configuredStore !== ''
            ? $configuredStore
            : (string) config('cache.default');
    }

    public function hasKnownStore(): bool
    {
        return array_key_exists($this->store(), (array) config('cache.stores', []));
    }

    public function usesSharedAtomicStore(): bool
    {
        return $this->hasKnownStore()
            && in_array($this->store(), (array) config('scheduler.shared_atomic_stores', []), true);
    }

    public function shouldUseOneServer(): bool
    {
        return (bool) config('scheduler.multi_server', false)
            && $this->usesSharedAtomicStore();
    }

    public function databaseTablesAvailable(): ?bool
    {
        if ($this->store() !== 'database' || ! $this->hasKnownStore()) {
            return null;
        }

        $store = (array) config('cache.stores.database', []);
        $schema = Schema::connection($store['connection'] ?? null);
        $cacheTable = (string) ($store['table'] ?? 'cache');
        $lockTable = (string) ($store['lock_table'] ?? 'cache_locks');

        return $schema->hasTable($cacheTable) && $schema->hasTable($lockTable);
    }

    public function multiServerReadiness(): string
    {
        if (! (bool) config('scheduler.multi_server', false)) {
            return 'not-requested';
        }

        if (! $this->hasKnownStore() || ! $this->usesSharedAtomicStore()) {
            return 'blocked';
        }

        if ($this->store() === 'database') {
            return $this->databaseTablesAvailable() ? 'ready' : 'blocked';
        }

        return 'unverified';
    }
}
