<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTrackingLog extends Model
{
    protected $fillable = [
        'campaign_recipient_id',
        'event_type',
        'clicked_url',
        'ip_address',
        'user_agent',
    ];

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class);
    }
}
