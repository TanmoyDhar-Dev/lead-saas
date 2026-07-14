<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'created_count',
        'skipped_count',
        'error_count',
        'error_report',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'error_report' => 'array',
            'completed_at' => 'datetime',
            'total_rows' => 'integer',
            'created_count' => 'integer',
            'skipped_count' => 'integer',
            'error_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importedLeads(): HasMany
    {
        return $this->hasMany(ImportedLead::class);
    }
}
