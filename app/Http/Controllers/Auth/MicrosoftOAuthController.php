<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConnectedMailbox;
use App\Support\MicrosoftOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class MicrosoftOAuthController extends Controller
{
    private const SCOPES = ['offline_access', 'Mail.Send', 'User.Read', 'Mail.ReadWrite'];

    public function redirect(Request $request): RedirectResponse
    {
        $isPopup = $request->boolean('popup');

        if ($isPopup) {
            session(['microsoft_oauth_popup' => true]);
            session()->save();
        }

        $redirectUri = MicrosoftOAuth::redirectUri();

        if (blank($redirectUri)) {
            abort(500, 'Microsoft OAuth redirect URI is not configured. Set APP_URL and AZURE_REDIRECT_URI in .env.');
        }

        $state = Str::random(40);
        $request->session()->put('state', $state);

        $response = redirect()->away(MicrosoftOAuth::authorizeUrl($state));

        if ($isPopup) {
            return $response->withCookie(cookie(
                'microsoft_oauth_popup',
                '1',
                10,
                '/',
                null,
                false,
                true,
                false,
                'Lax'
            ));
        }

        return $response;
    }

    public function callback(Request $request): RedirectResponse|Response
    {
        $redirectUri = MicrosoftOAuth::redirectUri();

        try {
            $driver = Socialite::driver('azure');
            
            // Bypass SSL verification on local environment (Laragon/Windows cURL issue)
            if (app()->environment('local')) {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            $azureUser = $driver
                ->redirectUrl($redirectUri)
                ->with(['redirect_uri' => $redirectUri])
                ->scopes(self::SCOPES)
                ->user();
        } catch (Throwable $e) {
            report($e);

            return $this->finishOAuth($request, false, 'Microsoft sign-in was cancelled or failed.');
        }

        if (empty($azureUser->refreshToken)) {
            return $this->finishOAuth(
                $request,
                false,
                'Microsoft did not return a refresh token. Remove the app from your Microsoft account, then connect again and accept all permissions.'
            );
        }

        $expiresIn = (int) ($azureUser->expiresIn ?? 3600);

        ConnectedMailbox::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'provider' => 'microsoft',
            ],
            [
                'email_address' => $azureUser->getEmail() ?? $azureUser->getNickname() ?? 'unknown@outlook.com',
                'access_token' => $azureUser->token,
                'refresh_token' => $azureUser->refreshToken,
                'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
            ]
        );

        return $this->finishOAuth($request, true, 'Outlook connected successfully.');
    }

    private function finishOAuth(Request $request, bool $success, string $message): RedirectResponse|Response
    {
        $isPopup = session()->pull('microsoft_oauth_popup', false)
            || $request->cookie('microsoft_oauth_popup') === '1';

        if ($isPopup) {
            return response()
                ->view('integrations.oauth-callback', [
                    'success' => $success,
                    'message' => $message,
                    'provider' => 'microsoft',
                ])
                ->withCookie(cookie()->forget('microsoft_oauth_popup'));
        }

        if ($success) {
            return redirect()->route('dashboard')->with('success', $message);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}
