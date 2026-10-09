<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\ReceivedEmail;

/**
 * Inbound email as the receiving API returns it, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function receivedEmail(array $overrides = []): ReceivedEmail
{
    return ReceivedEmail::fromApi(array_merge([
        'id' => 'rcv_1',
        'from' => 'Maria Silva <Maria@Acme.com>',
        'to' => ['contato@elenya.app'],
        'cc' => [],
        'reply_to' => [],
        'received_for' => ['contato@elenya.app'],
        'subject' => 'Pricing for 50 engineers',
        'html' => '<p>Hello</p>',
        'text' => 'Hello',
        'message_id' => '<abc-123@mail.acme.com>',
        'headers' => [],
        'attachments' => [['id' => 'att_1', 'filename' => 'rfp.pdf', 'content_type' => 'application/pdf', 'size' => 2048]],
        'created_at' => '2026-09-14T12:00:00Z',
    ], $overrides));
}
