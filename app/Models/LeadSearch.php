<?php

namespace App\Models;

use App\Models\Lead;
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
        'country',
        'city',
        'industry',
        'position',
        'volume',
        'main_search_query',
        'status',
        'n8n_response',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'n8n_response'  => 'array',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'volume'        => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'lead_search_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }

    /**
     * Leads that likely belong to this search but were never tagged with lead_search_id.
     * Used only by bounded batch tools (never run unbounded on a web request).
     */
    public function orphanLeadsQuery(): Builder
    {
        $startTime = $this->started_at ?? $this->created_at;

        $query = Lead::query()
            ->whereNull('lead_search_id')
            ->where('user_id', $this->user_id)
            ->where('country_by_search_param', 'ilike', '%'.$this->country.'%')
            ->where('city_by_search_param', 'ilike', '%'.$this->city.'%')
            ->where('created_at', '>=', $startTime->copy()->subMinutes(10))
            ->where('created_at', '<=', $startTime->copy()->addMinutes(60));

        if (! empty($this->industry)) {
            $query->where('industry_by_search_param', 'ilike', '%'.$this->industry.'%');
        }

        if (! empty($this->position)) {
            $query->where('position_by_search_param', 'ilike', '%'.$this->position.'%');
        }

        return $query;
    }

    /**
     * Attach up to $maxRows orphan leads to this search (single bounded UPDATE).
     */
    public function attachOrphanLeads(int $maxRows = 250): int
    {
        $maxRows = max(1, min($maxRows, 5000));

        $ids = $this->orphanLeadsQuery()
            ->orderBy('created_at')
            ->limit($maxRows)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return Lead::whereIn('id', $ids)->update([
            'lead_search_id' => $this->id,
            'source' => DB::raw("COALESCE(source, 'n8n_search')"),
            'updated_at' => now(),
        ]);
    }
}
