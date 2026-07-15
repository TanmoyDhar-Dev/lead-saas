<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphMailService
{
    /**
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     attachments?: list<array{name: string, contentType: string, contentBytes: string}>
     * }  $message
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    public function send(string $accessToken, array $message): array
    {
        $payload = [
            'message' => $this->buildMessage($message),
            'saveToSentItems' => 'true',
        ];

        return $this->request($accessToken, 'https://graph.microsoft.com/v1.0/me/sendMail', $payload);
    }

    /**
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     attachments?: list<array{name: string, contentType: string, contentBytes: string}>
     * }  $message
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    public function createDraft(string $accessToken, array $message): array
    {
        return $this->request(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages',
            $this->buildMessage($message),
        );
    }

    /**
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     attachments?: list<array{name: string, contentType: string, contentBytes: string}>
     * }  $message
     * @return array<string, mixed>
     */
    private function buildMessage(array $message): array
    {
        $payload = [
            'subject' => $message['subject'],
            'body' => [
                'contentType' => 'HTML',
                'content' => $message['html'],
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $message['to'],
                    ],
                ],
            ],
        ];

        $attachments = $message['attachments'] ?? [];
        if (is_array($attachments) && $attachments !== []) {
            $payload['attachments'] = array_map(function (array $attachment) {
                return [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => $attachment['name'],
                    'contentType' => $attachment['contentType'],
                    'contentBytes' => $attachment['contentBytes'],
                ];
            }, $attachments);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    private function request(string $accessToken, string $url, array $payload): array
    {
        try {
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'successful' => true,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'error' => null,
                ];
            }

            $errorBody = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->body();

            return [
                'successful' => false,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'error' => is_string($errorBody) ? $errorBody : 'Microsoft Graph request failed.',
            ];
        } catch (\Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
