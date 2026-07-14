<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportedLead extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'import_batch_id',
        'organization_name',
        'contact_name',
        'address',
        'original_filename',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ImportedLeadEmail::class)->orderByDesc('is_primary');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ImportedLeadPhone::class)->orderByDesc('is_primary');
    }

    public function primaryEmail(): ?string
    {
        return $this->emails->firstWhere('is_primary', true)?->email
            ?? $this->emails->first()?->email;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }
}
