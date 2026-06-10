<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'lead_search_id',
        'full_name',
        'job_title',
        'position',
        'address',
        'bio',
        'linkedin_url',
        'personal_email',
        'company_email',
        'industry',
        'company_name',
        'company_website',
        'company_linkedin',
        'company_city',
        'company_country',
        'company_address',
        'company_state',
        'company_domain',
        'company_description',
        'company_annual_revenue',
        'company_total_funding',
        'company_technology',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leadSearch()
    {
        return $this->belongsTo(LeadSearch::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'lead_user')->withTimestamps();
    }

    public function leadUsers()
    {
        return $this->hasMany(LeadUser::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->sharedUsers()->where('users.id', $user->id)->exists();
    }

    public function isLinkedToSearch(LeadSearch $leadSearch): bool
    {
        return $this->leadUsers()
            ->where('lead_search_id', $leadSearch->id)
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('user_id', $user->id)
                ->orWhereHas('sharedUsers', fn (Builder $shared) => $shared->where('users.id', $user->id));
        });
    }

    public function leadAutomationDetail()
    {
        return $this->hasOne(LeadAutomationDetail::class);
    }

    public function campaignRecipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
