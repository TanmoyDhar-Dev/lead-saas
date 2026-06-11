<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'email_main_body',
        'email_signature',
        'n8n_response',
        'error_message',
        'sent_to_n8n_at',
    ];

    protected function casts(): array
    {
        return [
            'n8n_response' => 'array',
            'sent_to_n8n_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function senderIdentity(): BelongsTo
    {
        return $this->belongsTo(SenderIdentity::class);
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
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

        return $query->where('user_id', $user->id);
    }
}
