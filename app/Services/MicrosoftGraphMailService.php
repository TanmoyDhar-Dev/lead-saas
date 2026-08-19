<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphMailService
{
    /**
     * Create a draft via POST /me/messages, capture Graph message id, then send.
     * Avoids POST /me/sendMail which returns 202 with no body (no message id).
     *
     * Sequence:
     * 1) POST /me/messages → extract `id` (+ internetMessageId)
     * 2) POST /me/messages/{id}/send
     *
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     cc?: list<string>,
     *     attachments?: list<array{name: string, contentType: string, contentBytes: string}>
     * }  $message
     * @return array{
     *     successful: bool,
     *     status: int|null,
     *     body: mixed,
     *     error: string|null,
     *     internet_message_id: string|null,
     *     graph_message_id: string|null
     * }
     */
    public function send(string $accessToken, array $message): array
    {
        $created = $this->createDraft($accessToken, $message);
        if (! $created['successful']) {
            return [
                'successful' => false,
                'status' => $created['status'],
                'body' => $created['body'],
                'error' => $created['error'] ?? 'Failed to create Graph draft message.',
                'internet_message_id' => null,
                'graph_message_id' => null,
            ];
        }

        $graphMessageId = is_array($created['body']) ? ($created['body']['id'] ?? null) : null;
        $internetMessageId = is_array($created['body']) ? ($created['body']['internetMessageId'] ?? null) : null;

        if (! is_string($graphMessageId) || $graphMessageId === '') {
            return [
                'successful' => false,
                'status' => $created['status'],
                'body' => $created['body'],
                'error' => 'Graph create message succeeded but returned no message id.',
                'internet_message_id' => null,
                'graph_message_id' => null,
            ];
        }

        $sendResult = $this->request(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($graphMessageId).'/send',
            []
        );

        if (! $sendResult['successful']) {
            return [
                'successful' => false,
                'status' => $sendResult['status'],
                'body' => $created['body'],
                'error' => $sendResult['error'] ?? 'Failed to send Graph draft message.',
                // Draft id is still useful for debugging / cleanup.
                'internet_message_id' => is_string($internetMessageId) ? $internetMessageId : null,
                'graph_message_id' => $graphMessageId,
            ];
        }

        // /send moves the draft to Sent Items and assigns a new Graph id.
        // Bulk reply must use that Sent Items id, not the now-invalid draft id.
        $sentMessageId = $this->resolveSentItemsMessageId(
            $accessToken,
            is_string($internetMessageId) ? $internetMessageId : null
        );

        return [
            'successful' => true,
            'status' => $sendResult['status'],
            'body' => $created['body'],
            'error' => null,
            'internet_message_id' => is_string($internetMessageId) ? $internetMessageId : null,
            'graph_message_id' => $sentMessageId ?? $graphMessageId,
        ];
    }

    /**
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     cc?: list<string>,
     *     attachments?: list<array{name: string, contentType: string, contentBytes: string}>
     * }  $message
     * @return array{
     *     successful: bool,
     *     status: int|null,
     *     body: mixed,
     *     error: string|null,
     *     internet_message_id: string|null,
     *     graph_message_id: string|null
     * }
     */
    public function createDraft(string $accessToken, array $message): array
    {
        $result = $this->request(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages',
            $this->buildMessage($message),
        );

        $graphMessageId = is_array($result['body']) ? ($result['body']['id'] ?? null) : null;
        $internetMessageId = is_array($result['body']) ? ($result['body']['internetMessageId'] ?? null) : null;

        return [
            'successful' => $result['successful'],
            'status' => $result['status'],
            'body' => $result['body'],
            'error' => $result['error'],
            'internet_message_id' => is_string($internetMessageId) ? $internetMessageId : null,
            'graph_message_id' => is_string($graphMessageId) ? $graphMessageId : null,
        ];
    }

    /**
     * Create a threaded reply draft from an existing Graph message, set body, and send.
     *
     * @return array{
     *     successful: bool,
     *     status: int|null,
     *     body: mixed,
     *     error: string|null,
     *     internet_message_id: string|null,
     *     graph_message_id: string|null,
     *     subject: string|null
     * }
     */
    public function replyToMessage(string $accessToken, string $graphMessageId, string $htmlBody): array
    {
        $created = $this->request(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($graphMessageId).'/createReply',
            []
        );

        if (! $created['successful']) {
            return [
                'successful' => false,
                'status' => $created['status'],
                'body' => $created['body'],
                'error' => $created['error'] ?? 'Failed to create reply draft.',
                'internet_message_id' => null,
                'graph_message_id' => null,
                'subject' => null,
            ];
        }

        $draftId = is_array($created['body']) ? ($created['body']['id'] ?? null) : null;
        $internetMessageId = is_array($created['body']) ? ($created['body']['internetMessageId'] ?? null) : null;
        $subject = is_array($created['body']) ? ($created['body']['subject'] ?? null) : null;

        if (! is_string($draftId) || $draftId === '') {
            return [
                'successful' => false,
                'status' => $created['status'],
                'body' => $created['body'],
                'error' => 'Graph createReply succeeded but returned no draft id.',
                'internet_message_id' => null,
                'graph_message_id' => null,
                'subject' => null,
            ];
        }

        $patched = $this->patch(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId),
            [
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
            ]
        );

        if (! $patched['successful']) {
            return [
                'successful' => false,
                'status' => $patched['status'],
                'body' => $patched['body'],
                'error' => $patched['error'] ?? 'Failed to update reply draft body.',
                'internet_message_id' => is_string($internetMessageId) ? $internetMessageId : null,
                'graph_message_id' => $draftId,
                'subject' => is_string($subject) ? $subject : null,
            ];
        }

        // Re-fetch after patch so we keep the latest internetMessageId / subject.
        $refreshed = $this->get(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId).'?$select=id,internetMessageId,subject'
        );
        if ($refreshed['successful'] && is_array($refreshed['body'])) {
            $internetMessageId = $refreshed['body']['internetMessageId'] ?? $internetMessageId;
            $subject = $refreshed['body']['subject'] ?? $subject;
        }

        $sendResult = $this->request(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId).'/send',
            []
        );

        $sentMessageId = $sendResult['successful']
            ? $this->resolveSentItemsMessageId(
                $accessToken,
                is_string($internetMessageId) ? $internetMessageId : null
            )
            : null;

        return [
            'successful' => $sendResult['successful'],
            'status' => $sendResult['status'],
            'body' => $created['body'],
            'error' => $sendResult['error'],
            'internet_message_id' => is_string($internetMessageId) ? $internetMessageId : null,
            'graph_message_id' => $sentMessageId ?? $draftId,
            'subject' => is_string($subject) ? $subject : null,
        ];
    }

    /**
     * After /send, Graph assigns a new id in Sent Items. Look it up by internetMessageId.
     */
    public function resolveSentItemsMessageId(string $accessToken, ?string $internetMessageId, int $attempts = 5): ?string
    {
        $normalized = $this->normalizeInternetMessageId((string) $internetMessageId);
        if ($normalized === null) {
            return null;
        }

        for ($attempt = 1; $attempt <= max(1, $attempts); $attempt++) {
            $found = $this->findMessageIdByInternetMessageId($accessToken, $normalized);
            if ($found !== null) {
                return $found;
            }

            if ($attempt < $attempts) {
                usleep(400000);
            }
        }

        return null;
    }

    /**
     * Resolve a Graph message id from an RFC 2822 internetMessageId (with or without brackets).
     */
    public function findMessageIdByInternetMessageId(string $accessToken, string $internetMessageId): ?string
    {
        $normalized = $this->normalizeInternetMessageId($internetMessageId);
        if ($normalized === null) {
            return null;
        }

        $escaped = str_replace("'", "''", $normalized);
        $filter = rawurlencode("internetMessageId eq '{$escaped}'");

        foreach ([
            'https://graph.microsoft.com/v1.0/me/mailFolders/sentitems/messages?$filter='.$filter.'&$select=id&$top=1',
            'https://graph.microsoft.com/v1.0/me/messages?$filter='.$filter.'&$select=id&$top=1',
        ] as $url) {
            $result = $this->get($accessToken, $url);
            if (! $result['successful'] || ! is_array($result['body'])) {
                continue;
            }

            $first = $result['body']['value'][0]['id'] ?? null;
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return null;
    }

    private function normalizeInternetMessageId(string $internetMessageId): ?string
    {
        $normalized = trim($internetMessageId);
        if ($normalized === '') {
            return null;
        }

        if (! str_starts_with($normalized, '<')) {
            $normalized = '<'.$normalized;
        }
        if (! str_ends_with($normalized, '>')) {
            $normalized .= '>';
        }

        return $normalized;
    }

    /**
     * @param  array{
     *     subject: string,
     *     html: string,
     *     to: string,
     *     cc?: list<string>,
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

        $cc = array_values(array_filter(
            is_array($message['cc'] ?? null) ? $message['cc'] : [],
            fn ($email) => is_string($email) && trim($email) !== ''
        ));

        if ($cc !== []) {
            $payload['ccRecipients'] = array_map(
                fn (string $email) => [
                    'emailAddress' => [
                        'address' => trim($email),
                    ],
                ],
                $cc
            );
        }

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
            $pending = Http::withoutVerifying()
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(60);

            $response = $payload === []
                ? $pending->post($url)
                : $pending->asJson()->post($url, $payload);

            return $this->normalizeHttpResult($response);
        } catch (\Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    private function patch(string $accessToken, string $url, array $payload): array
    {
        try {
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(60)
                ->asJson()
                ->patch($url, $payload);

            return $this->normalizeHttpResult($response);
        } catch (\Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    private function get(string $accessToken, string $url): array
    {
        try {
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(60)
                ->get($url);

            return $this->normalizeHttpResult($response);
        } catch (\Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     * @return array{successful: bool, status: int|null, body: mixed, error: string|null}
     */
    private function normalizeHttpResult($response): array
    {
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
    }
}
