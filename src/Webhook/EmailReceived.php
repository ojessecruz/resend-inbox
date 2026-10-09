<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Webhook;

/**
 * An email arrived; its full content is fetched by id from the receiving API.
 */
final readonly class EmailReceived implements WebhookEvent
{
    public function __construct(public string $emailId) {}

    public function type(): string
    {
        return 'email.received';
    }
}
