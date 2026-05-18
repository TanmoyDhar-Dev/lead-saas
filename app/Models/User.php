<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\ConnectedMailbox;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'status', 
    'lead_search_limit', 'lead_export_limit', 'lead_storage_limit', 
    'campaign_limit', 'email_send_limit', 'notes', 'created_by'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'lead_search_limit' => 'integer',
            'lead_export_limit' => 'integer',
            'lead_storage_limit' => 'integer',
            'campaign_limit' => 'integer',
            'email_send_limit' => 'integer',
        ];
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function leadSearches()
    {
        return $this->hasMany(LeadSearch::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function emailBodyTemplates()
    {
        return $this->hasMany(EmailBodyTemplate::class);
    }

    public function emailSignatureTemplates()
    {
        return $this->hasMany(EmailSignatureTemplate::class);
    }

    public function senderIdentities()
    {
        return $this->hasMany(SenderIdentity::class);
    }

    public function connectedMailboxes()
    {
        return $this->hasMany(ConnectedMailbox::class);
    }

    public function csvImports()
    {
        return $this->hasMany(CsvImport::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function hasReachedLeadSearchLimit(): bool
    {
        if (is_null($this->lead_search_limit)) return false;
        return $this->leadSearches()->count() >= $this->lead_search_limit;
    }

    public function hasReachedCampaignLimit(): bool
    {
        if (is_null($this->campaign_limit)) return false;
        return $this->campaigns()->count() >= $this->campaign_limit;
    }

    public function hasReachedLeadStorageLimit(): bool
    {
        if (is_null($this->lead_storage_limit)) return false;
        return $this->leads()->count() >= $this->lead_storage_limit;
    }

    public function hasReachedEmailSendLimit(): bool
    {
        if (is_null($this->email_send_limit)) return false;
        return false;
    }
}
