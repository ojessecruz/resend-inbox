<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Webhook;

/**
 * A verified Resend webhook event.
 */
interface WebhookEvent
{
    /**
     * The Resend event type, such as "email.received".
     */
    public function type(): string;
}
