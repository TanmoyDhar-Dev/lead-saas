<?php

namespace App\Jobs;

use App\Models\ConnectedMailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendOutreachEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $userId,
        public string $toEmail,
        public string $subject,
        public string $bodyHtml,
        public string $provider,
    ) {
    }

    public function handle(): void
    {
        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $this->userId)
            ->where('provider', $this->provider)
            ->where('status', ConnectedMailbox::STATUS_ACTIVE)
            ->first();

        if (! $mailbox) {
            throw new RuntimeException('No active mailbox found for this user and provider.');
        }

        $apiKey = config('services.maton.api_key');
        if (! $apiKey) {
            throw new RuntimeException('Maton API key is not configured.');
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Maton-Connection' => $mailbox->maton_connection_id,
            'Content-Type' => 'application/json',
        ];

        try {
            if ($this->provider === ConnectedMailbox::PROVIDER_OUTLOOK) {
                $this->sendViaOutlook($headers);

                return;
            }

            if ($this->provider === ConnectedMailbox::PROVIDER_GOOGLE_MAIL) {
                $this->sendViaGmail($headers);

                return;
            }

            throw new RuntimeException('Unsupported provider: ' . $this->provider);
        } catch (Throwable $e) {
            Log::error('SendOutreachEmailJob failed.', [
                'user_id' => $this->userId,
                'to_email' => $this->toEmail,
                'provider' => $this->provider,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function sendViaOutlook(array $headers): void
    {
        $payload = [
            'message' => [
                'subject' => $this->subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $this->bodyHtml,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $this->toEmail,
                        ],
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->asJson()
            ->post('https://gateway.maton.ai/outlook/v1.0/me/sendMail', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Outlook sendMail failed: ' . $response->body());
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function sendViaGmail(array $headers): void
    {
        $rawMime = $this->buildGmailRawMime();

        $payload = [
            'raw' => $this->base64UrlEncode($rawMime),
        ];

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->asJson()
            ->post('https://gateway.maton.ai/google-mail/gmail/v1/users/me/messages/send', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Gmail messages.send failed: ' . $response->body());
        }
    }

    private function buildGmailRawMime(): string
    {
        return implode("\r\n", [
            'To: ' . $this->toEmail,
            'Subject: ' . $this->subject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            '',
            $this->bodyHtml,
        ]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
