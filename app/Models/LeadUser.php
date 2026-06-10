<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadUser extends Model
{
    protected $table = 'lead_user';

    protected $fillable = [
        'user_id',
        'lead_id',
        'lead_search_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leadSearch(): BelongsTo
    {
        return $this->belongsTo(LeadSearch::class);
    }
}
