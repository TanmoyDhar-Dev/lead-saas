<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'sender_identity_id',
        'name',
        'delivery_mode',
        'search_window',
        'email_main_body',
        'email_signature',
        'daily_limit',
        'scheduled_at',
        'status',
        'n8n_response',
        'error_message',
        'sent_to_n8n_at',
    ];

    protected $casts = [
        'n8n_response' => 'array',
        'scheduled_at' => 'datetime',
        'sent_to_n8n_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function senderIdentity()
    {
        return $this->belongsTo(SenderIdentity::class);
    }

    public function campaignRecipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function leadAutomationDetails()
    {
        return $this->hasMany(LeadAutomationDetail::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
