<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectedMailbox extends Model
{
    use HasFactory;

    public const PROVIDER_GOOGLE_MAIL = 'google-mail';
    public const PROVIDER_OUTLOOK = 'outlook';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'user_id',
        'email_address',
        'provider',
        'maton_connection_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
