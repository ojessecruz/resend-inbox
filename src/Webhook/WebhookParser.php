<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Webhook;

use InvalidArgumentException;
use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Exceptions\InvalidWebhook;
use Jessecruz\ResendInbox\Headers;
use JsonException;
use Resend\Exceptions\WebhookSignatureVerificationException;
use Resend\WebhookSignature;

/**
 * Verifies a Resend webhook's Svix signature and turns its body into a typed
 * event. Resend redelivers on failure, so whoever handles the event must be
 * idempotent per email id.
 */
final readonly class WebhookParser
{
    public function __construct(
        private string $secret,
        private int $toleranceSeconds = 300,
    ) {
        if ($secret === '') {
            throw new InvalidArgumentException('The Resend webhook signing secret is empty.');
        }
    }

    /**
     * @param  array<array-key, mixed>  $headers  Request headers; names are matched case-insensitively.
     *
     * @throws InvalidWebhook
     */
    public function parse(string $body, array $headers): WebhookEvent
    {
        try {
            WebhookSignature::verify($body, [
                'svix-id' => (string) Headers::get($headers, 'svix-id'),
                'svix-timestamp' => (string) Headers::get($headers, 'svix-timestamp'),
                'svix-signature' => (string) Headers::get($headers, 'svix-signature'),
            ], $this->secret, $this->toleranceSeconds);
        } catch (WebhookSignatureVerificationException $e) {
            throw InvalidWebhook::signature($e->getMessage());
        }

        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw InvalidWebhook::payload($e->getMessage());
        }

        if (! is_array($payload) || ! is_string($payload['type'] ?? null)) {
            throw InvalidWebhook::payload('missing event type.');
        }

        $type = $payload['type'];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $emailId = is_scalar($data['email_id'] ?? null) ? (string) $data['email_id'] : '';

        if ($type === 'email.received' && $emailId !== '') {
            return new EmailReceived($emailId);
        }

        $status = DeliveryStatus::fromWebhookType($type);

        if ($status !== null && $emailId !== '') {
            $bounce = is_array($data['bounce'] ?? null) ? $data['bounce'] : [];

            return new DeliveryStatusChanged(
                emailId: $emailId,
                status: $status,
                messageId: is_scalar($data['message_id'] ?? null) ? (string) $data['message_id'] : null,
                bounceMessage: is_scalar($bounce['message'] ?? null) ? (string) $bounce['message'] : null,
            );
        }

        return new UnhandledEvent($type, $data);
    }
}
