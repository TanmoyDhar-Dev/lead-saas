<?php

namespace App\Support;

class MicrosoftOAuth
{
    public static function redirectUri(): string
    {
        $configured = config('services.azure.redirect');
        $appUrl = rtrim((string) config('app.url'), '/');

        $uri = $configured ?: $appUrl.'/auth/microsoft/callback';

        if (! str_contains($uri, '/auth/microsoft/callback')) {
            $uri = rtrim($uri, '/').'/auth/microsoft/callback';
        }

        return $uri;
    }

    public static function tenant(): string
    {
        return (string) config('services.azure.tenant', 'common');
    }

    public static function authorizeUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.azure.client_id'),
            'response_type' => 'code',
            'redirect_uri' => self::redirectUri(),
            'scope' => implode(' ', ['offline_access', 'Mail.Send', 'User.Read']),
            'state' => $state,
            'response_mode' => 'query',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://login.microsoftonline.com/'.self::tenant().'/oauth2/v2.0/authorize?'.$query;
    }
}
