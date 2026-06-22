<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    const SECURITY_ACTIVE_PAID = 'active_paid';
    const SECURITY_INACTIVE_REVOKED = 'inactive_revoked';
    const SECURITY_PAST_DUE = 'past_due';

    const SECURITY_LABELS = [
        'active_paid' => 'Active (Paid)',
        'inactive_revoked' => 'Inactive (Revoke Access)',
        'past_due' => 'Past Due (Payment Failed)',
    ];

    protected $fillable = [
        'user_id',
        'search_limit',
        'lead_limit',
        'searches_used',
        'expiry_date',
        'security_status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSecurityLabelAttribute(): string
    {
        return self::SECURITY_LABELS[$this->security_status] ?? 'Unknown';
    }

    public function isAccessAllowed(): bool
    {
        return $this->security_status === self::SECURITY_ACTIVE_PAID;
    }
}

