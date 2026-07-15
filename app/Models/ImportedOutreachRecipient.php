<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedOutreachRecipient extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'imported_outreach_id',
        'imported_lead_id',
        'tracking_id',
        'to_email',
        'subject',
        'final_body',
        'status',
        'failed_reason',
        'drafted_at',
        'sent_at',
        'opened_at',
        'open_count',
    ];

    protected function casts(): array
    {
        return [
            'drafted_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'open_count' => 'integer',
        ];
    }

    public function outreach(): BelongsTo
    {
        return $this->belongsTo(ImportedOutreach::class, 'imported_outreach_id');
    }

    public function importedLead(): BelongsTo
    {
        return $this->belongsTo(ImportedLead::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('outreach', fn ($q) => $q->where('user_id', $user->id));
    }
}
