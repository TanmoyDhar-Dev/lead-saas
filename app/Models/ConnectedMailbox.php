<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ConnectedMailbox extends Model
{
    protected $fillable = [
        'user_id',
        'email_address',
        'provider',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getFreshAccessToken(self $mailbox): string
    {
        if ($mailbox->token_expires_at->greaterThan(now()->addMinutes(5))) {
            return $mailbox->access_token;
        }

        $tenant = config('services.azure.tenant');
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'client_id' => config('services.azure.client_id'),
            'client_secret' => config('services.azure.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $mailbox->refresh_token,
            'scope' => 'offline_access Mail.Send User.Read',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to refresh Microsoft access token: '.$response->body());
        }

        $data = $response->json();
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        $mailbox->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $mailbox->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
        ]);

        return $mailbox->access_token;
    }
}
