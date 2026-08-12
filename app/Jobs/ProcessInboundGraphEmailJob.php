<?php

namespace App\Jobs;

use App\Models\ConnectedMailbox;
use App\Models\GraphSubscription;
use App\Models\ImportedOutreachRecipient;
use App\Models\InboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessInboundGraphEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $notification
     */
    public function __construct(
        public array $notification,
    ) {}

    public function handle(): void
    {
        try {
            $resource = trim((string) ($this->notification['resource'] ?? ''), '/');
            if ($resource === '') {
                Log::info('Ignoring Graph notification without resource.', [
                    'subscriptionId' => $this->notification['subscriptionId'] ?? null,
                ]);

                return;
            }

            $subscription = $this->resolveSubscription();
            if ($subscription === null) {
                Log::warning('No GraphSubscription found for inbound notification.', [
                    'subscriptionId' => $this->notification['subscriptionId'] ?? null,
                    'clientState' => $this->notification['clientState'] ?? null,
                ]);

                return;
            }

            $mailbox = ConnectedMailbox::query()
                ->where('user_id', $subscription->user_id)
                ->where('provider', 'microsoft')
                ->first();

            if ($mailbox === null) {
                Log::warning('No Microsoft mailbox connected for Graph subscription user.', [
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->subscription_id,
                ]);

                return;
            }

            $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
            $message = $this->fetchGraphMessage($accessToken, $resource);

            if ($message === null) {
                return;
            }

            $graphMessageId = (string) ($message['id'] ?? '');
            if ($graphMessageId === '') {
                Log::warning('Graph message payload missing id.', ['resource' => $resource]);

                return;
            }

            if (InboundMessage::query()->where('graph_message_id', $graphMessageId)->exists()) {
                Log::info('Inbound Graph message already stored.', [
                    'graph_message_id' => $graphMessageId,
                ]);

                return;
            }

            $replyIds = $this->extractReplyMessageIds($message['internetMessageHeaders'] ?? []);
            if ($replyIds === []) {
                Log::info('Ignoring inbound Graph message without In-Reply-To/References.', [
                    'graph_message_id' => $graphMessageId,
                ]);

                return;
            }

            $recipient = $this->matchOutreachRecipient($replyIds);
            if ($recipient === null) {
                Log::info('Inbound Graph message is not a reply to a known outreach recipient.', [
                    'graph_message_id' => $graphMessageId,
                    'reply_ids' => $replyIds,
                ]);

                return;
            }

            $fromEmail = $this->extractFromEmail($message['from'] ?? null);
            $subject = isset($message['subject']) ? (string) $message['subject'] : null;
            [$bodyText, $bodyHtml] = $this->extractBodies($message['body'] ?? null);

            InboundMessage::create([
                'imported_outreach_recipient_id' => $recipient->id,
                'graph_message_id' => $graphMessageId,
                'from_email' => $fromEmail !== '' ? $fromEmail : 'unknown@unknown',
                'subject' => $subject,
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
                'received_at' => $this->resolveReceivedAt($message),
            ]);

            Log::info('Stored inbound Graph reply.', [
                'graph_message_id' => $graphMessageId,
                'imported_outreach_recipient_id' => $recipient->id,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed processing inbound Graph email notification.', [
                'subscriptionId' => $this->notification['subscriptionId'] ?? null,
                'resource' => $this->notification['resource'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveSubscription(): ?GraphSubscription
    {
        $subscriptionId = (string) ($this->notification['subscriptionId'] ?? '');
        $clientState = (string) ($this->notification['clientState'] ?? '');

        $query = GraphSubscription::query();

        if ($subscriptionId !== '') {
            $query->where('subscription_id', $subscriptionId);
        } elseif ($clientState !== '') {
            $query->where('client_state', $clientState);
        } else {
            return null;
        }

        if ($subscriptionId !== '' && $clientState !== '') {
            $query->where('client_state', $clientState);
        }

        return $query->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchGraphMessage(string $accessToken, string $resource): ?array
    {
        $url = 'https://graph.microsoft.com/v1.0/'.$resource;

        try {
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(30)
                ->get($url, [
                    '$select' => 'id,internetMessageHeaders,from,subject,body,receivedDateTime',
                ]);
        } catch (Throwable $e) {
            Log::error('Graph message HTTP request failed.', [
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($response->status() === 404) {
            Log::info('Graph message not found (may have been deleted).', [
                'resource' => $resource,
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::error('Graph message fetch failed.', [
                'resource' => $resource,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Graph message fetch failed with status '.$response->status());
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  mixed  $headers
     * @return list<string>
     */
    private function extractReplyMessageIds(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        $inReplyTo = null;
        $references = null;

        foreach ($headers as $header) {
            if (! is_array($header)) {
                continue;
            }

            $name = strtolower(trim((string) ($header['name'] ?? '')));
            $value = trim((string) ($header['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            if ($name === 'in-reply-to') {
                $inReplyTo = $value;
            }

            if ($name === 'references') {
                $references = $value;
            }
        }

        $ids = [];

        if (is_string($inReplyTo) && $inReplyTo !== '') {
            foreach ($this->splitMessageIdHeader($inReplyTo) as $id) {
                $ids[] = $id;
            }
        }

        if (is_string($references) && $references !== '') {
            foreach ($this->splitMessageIdHeader($references) as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private function splitMessageIdHeader(string $headerValue): array
    {
        preg_match_all('/<[^>]+>|[^\s<>]+@[^\s<>]+/', $headerValue, $matches);

        $ids = [];
        foreach ($matches[0] ?? [] as $match) {
            $trimmed = trim((string) $match);
            if ($trimmed !== '') {
                $ids[] = $trimmed;
            }
        }

        return $ids;
    }

    /**
     * @param  list<string>  $replyIds
     */
    private function matchOutreachRecipient(array $replyIds): ?ImportedOutreachRecipient
    {
        foreach ($replyIds as $replyId) {
            $normalized = $this->normalizeMessageId($replyId);
            if ($normalized === '') {
                continue;
            }

            $variants = array_values(array_unique([
                $replyId,
                $normalized,
                '<'.$normalized.'>',
            ]));

            $recipient = ImportedOutreachRecipient::query()
                ->whereNotNull('message_id')
                ->where(function ($query) use ($variants, $normalized) {
                    $query->whereIn('message_id', $variants)
                        ->orWhereRaw(
                            "lower(trim(both '<>' from message_id)) = ?",
                            [$normalized]
                        );
                })
                ->first();

            if ($recipient !== null) {
                return $recipient;
            }
        }

        return null;
    }

    private function normalizeMessageId(string $messageId): string
    {
        return strtolower(trim($messageId, " \t\n\r\0\x0B<>"));
    }

    private function extractFromEmail(mixed $from): string
    {
        if (! is_array($from)) {
            return '';
        }

        $address = $from['emailAddress']['address'] ?? null;

        return is_string($address) ? trim($address) : '';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function extractBodies(mixed $body): array
    {
        if (! is_array($body)) {
            return [null, null];
        }

        $content = isset($body['content']) ? (string) $body['content'] : '';
        $contentType = strtolower((string) ($body['contentType'] ?? 'text'));

        if ($content === '') {
            return [null, null];
        }

        if ($contentType === 'html') {
            return [strip_tags($content), $content];
        }

        return [$content, null];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function resolveReceivedAt(array $message): Carbon
    {
        $received = $message['receivedDateTime'] ?? null;

        if (is_string($received) && $received !== '') {
            try {
                // Graph returns UTC; convert to app timezone so DB datetime matches local outreach times.
                return Carbon::parse($received)->timezone((string) config('app.timezone'));
            } catch (Throwable) {
                // fall through
            }
        }

        return now();
    }
}
