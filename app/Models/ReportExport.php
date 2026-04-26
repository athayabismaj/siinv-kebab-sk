<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'scope',
        'type',
        'filters',
        'status',
        'file_path',
        'file_name',
        'error_message',
        'scheduled_for',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getTable()
    {
        $table = parent::getTable();

        $defaultConnection = (string) config('database.default', '');
        $driver = (string) config("database.connections.{$defaultConnection}.driver", '');

        if ($driver !== 'pgsql' || str_contains($table, '.')) {
            return $table;
        }

        $searchPath = (string) config("database.connections.{$defaultConnection}.search_path", 'public');
        $schema = trim(explode(',', $searchPath)[0] ?? '');

        if ($schema === '') {
            return $table;
        }

        return $schema . '.' . $table;
    }
}

