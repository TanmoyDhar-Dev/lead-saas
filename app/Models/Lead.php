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
        'main_search_query',
        'country_by_search_param',
        'city_by_search_param',
        'person_name',
        'personal__linkdin_url',
        'personal_linkdin_bio',
        'personal_profile_about',
        'personal_address_with_country',
        'position_by_search_param',
        'position_by_apifiapi',
        'personal_email_address',
        'industry_by_search_param',
        'industry_by_apifyapi',
        'company_name',
        'company_address',
        'company_website',
        'company_linkdin_url',
        'email_sent',
        'source',
        'imported_at',
        'lead_search_id',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaignRecipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function leadAutomationDetails()
    {
        return $this->hasMany(LeadAutomationDetail::class);
    }

    public function leadSearch()
    {
        return $this->belongsTo(LeadSearch::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
