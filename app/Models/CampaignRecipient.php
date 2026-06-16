<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignRecipient extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'tracking_id',
        'status',
        'subject',
        'hyper_personalized_line',
        'final_email_body',
        'email_topic',
        'topic_source',
        'website_summary',
        'news_summary',
        'product_summary',
        'growth_summary',
        'linkedin_summary',
        'drafted_at',
        'sent_at',
        'opened_at',
        'open_count',
        'clicked_at',
        'replied_at',
        'bounced_at',
        'failed_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'drafted_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'replied_at' => 'datetime',
            'bounced_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function emailTrackingLogs(): HasMany
    {
        return $this->hasMany(EmailTrackingLog::class);
    }

    public function leadAutomationDetails(): HasMany
    {
        return $this->hasMany(LeadAutomationDetail::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('campaign', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
