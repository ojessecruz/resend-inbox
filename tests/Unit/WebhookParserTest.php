<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\DeliveryStatus;
use Jessecruz\ResendInbox\Exceptions\InvalidWebhook;
use Jessecruz\ResendInbox\Webhook\DeliveryStatusChanged;
use Jessecruz\ResendInbox\Webhook\EmailReceived;
use Jessecruz\ResendInbox\Webhook\UnhandledEvent;
use Jessecruz\ResendInbox\Webhook\WebhookParser;

beforeEach(function () {
    $this->parser = new WebhookParser('whsec_'.base64_encode('resend-test-secret'));
});

/**
 * Svix headers Resend would send for the body.
 *
 * @return array<string, string>
 */
function signedHeaders(string $body, ?int $timestamp = null, string $secret = 'resend-test-secret'): array
{
    $id = 'msg_'.bin2hex(random_bytes(4));
    $timestamp ??= time();
    $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $secret, true));

    return [
        'Svix-Id' => $id,
        'Svix-Timestamp' => (string) $timestamp,
        'Svix-Signature' => "v1,{$signature}",
    ];
}

it('parses an inbound email notification', function () {
    $body = json_encode(['type' => 'email.received', 'data' => ['email_id' => 'rcv_1']], JSON_THROW_ON_ERROR);

    $event = $this->parser->parse($body, signedHeaders($body));

    expect($event)->toBeInstanceOf(EmailReceived::class)
        ->and($event->emailId)->toBe('rcv_1')
        ->and($event->type())->toBe('email.received');
});

it('parses delivery progress with the reported message id and bounce reason', function () {
    $body = json_encode(['type' => 'email.bounced', 'data' => [
        'email_id' => 'snd_1',
        'message_id' => '<rewritten@resend.dev>',
        'bounce' => ['message' => 'Mailbox does not exist'],
    ]], JSON_THROW_ON_ERROR);

    $event = $this->parser->parse($body, signedHeaders($body));

    expect($event)->toBeInstanceOf(DeliveryStatusChanged::class)
        ->and($event->emailId)->toBe('snd_1')
        ->and($event->status)->toBe(DeliveryStatus::Bounced)
        ->and($event->messageId)->toBe('<rewritten@resend.dev>')
        ->and($event->bounceMessage)->toBe('Mailbox does not exist')
        ->and($event->type())->toBe('email.bounced');
});

it('passes other verified events through untouched', function () {
    $body = json_encode(['type' => 'contact.updated', 'data' => ['email' => 'ada@example.com', 'unsubscribed' => true]], JSON_THROW_ON_ERROR);

    $event = $this->parser->parse($body, signedHeaders($body));

    expect($event)->toBeInstanceOf(UnhandledEvent::class)
        ->and($event->type())->toBe('contact.updated')
        ->and($event->data)->toBe(['email' => 'ada@example.com', 'unsubscribed' => true]);
});

it('rejects a forged, stale or missing signature', function (Closure $headers) {
    $body = json_encode(['type' => 'email.received', 'data' => ['email_id' => 'rcv_1']], JSON_THROW_ON_ERROR);

    $this->parser->parse($body, $headers($body));
})->with([
    'forged' => [fn (string $body): array => signedHeaders($body, secret: 'another-secret')],
    'stale' => [fn (string $body): array => signedHeaders($body, timestamp: time() - 3600)],
    'missing' => [fn (string $body): array => []],
])->throws(InvalidWebhook::class, 'signature');

it('rejects a signed body that is not an event', function (string $body) {
    $this->parser->parse($body, signedHeaders($body));
})->with([
    'not json' => '{oops',
    'no type' => '{"data":{}}',
])->throws(InvalidWebhook::class, 'payload');

it('refuses to run without a signing secret', function () {
    new WebhookParser('');
})->throws(InvalidArgumentException::class);
