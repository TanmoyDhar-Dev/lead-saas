<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'status',
    'lead_search_limit', 'lead_export_limit', 'lead_storage_limit',
    'email_send_limit', 'notes', 'created_by',
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
        ];
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function userPlan()
    {
        return $this->hasOne(UserPlan::class);
    }

    public function leadSearches()
    {
        return $this->hasMany(LeadSearch::class);
    }

    public function connectedMailboxes()
    {
        return $this->hasMany(ConnectedMailbox::class);
    }

    public function billingHistories()
    {
        return $this->hasMany(BillingHistory::class)->orderByDesc('created_at');
    }

    public function microsoftMailbox()
    {
        return $this->hasOne(ConnectedMailbox::class)->where('provider', 'microsoft');
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

    public function hasReachedLeadStorageLimit(): bool
    {
        if (is_null($this->lead_storage_limit)) {
            return false;
        }

        return Lead::visibleTo($this)->count() >= $this->lead_storage_limit;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    public function getSubscriptionStatusAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'System Access';
        }
        if ($this->userPlan) {
            return $this->userPlan->security_label;
        }
        return 'No Plan Configured';
    }

    public function getAccessUntilAttribute()
    {
        return $this->userPlan ? $this->userPlan->expiry_date : null;
    }

    public function getQueryLimitAttribute(): int
    {
        return $this->userPlan ? (int) $this->userPlan->search_limit : 0;
    }

    public function getProfileLimitAttribute(): int
    {
        return $this->userPlan ? (int) $this->userPlan->lead_limit : 0;
    }

    public function getProfileUsageAttribute(): int
    {
        return $this->userPlan ? (int) $this->userPlan->searches_used : 0;
    }

    public function getResultsCountAttribute(): int
    {
        return $this->leads()->count();
    }
}

