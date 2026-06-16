<?php

namespace App\Models;

use App\Models\Lead;
use App\Models\LeadUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeadSearch extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'target_location',
        'industry',
        'position',
        'volume',
        'status',
        'quota_charged',
        'quota_refunded_at',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'quota_charged' => 'boolean',
        'quota_refunded_at' => 'datetime',
        'volume' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'lead_search_id');
    }

    public function leadAccessors()
    {
        return $this->hasMany(LeadUser::class, 'lead_search_id');
    }

    public function scopedLeads(): Builder
    {
        return Lead::query()->whereIn('id', function ($query) {
            $query->select('lead_id')
                ->from('lead_user')
                ->where('lead_search_id', $this->id);
        });
    }

    public function detachLeadsForSearch(): void
    {
        $leadIds = $this->leadAccessors()->pluck('lead_id');

        $this->leadAccessors()->delete();

        foreach ($leadIds as $leadId) {
            $stillShared = LeadUser::where('lead_id', $leadId)->exists();

            if (! $stillShared) {
                Lead::where('id', $leadId)->delete();
            }
        }
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
