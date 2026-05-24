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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
