<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedLeadEmail extends Model
{
    protected $fillable = [
        'imported_lead_id',
        'email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function importedLead(): BelongsTo
    {
        return $this->belongsTo(ImportedLead::class);
    }
}
