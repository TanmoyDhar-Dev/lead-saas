<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAutomationDetail extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'lead_id',
        'campaign_id',
        'campaign_recipient_id',
        'email_sent',
        'email_topic',
        'email_body',
        'email_attachments',
        'search_window',
        'website_summary',
        'news_summary',
        'product_summary',
        'growth_summary',
        'linkedin_summary',
        'topic_source',
        'sender_name',
        'sender_role',
        'sender_company',
        'sender_region',
        'sender_industry',
        'sender_linkedin',
        'sender_website',
        'sender_eo_chapter',
        'search_last_run_at',
    ];

    protected $casts = [
        'search_last_run_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignRecipient()
    {
        return $this->belongsTo(CampaignRecipient::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where(function ($q) use ($user) {
            $q->whereHas('lead', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            })->orWhereHas('campaign', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            });
        });
    }
}
