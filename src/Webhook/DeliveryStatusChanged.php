<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Webhook;

use Jessecruz\ResendInbox\DeliveryStatus;

/**
 * Resend reported progress on a sent email. The Message-ID it reports wins
 * over ours, in case the provider rewrote it, so replies still thread.
 */
final readonly class DeliveryStatusChanged implements WebhookEvent
{
    public function __construct(
        public string $emailId,
        public DeliveryStatus $status,
        public ?string $messageId = null,
        public ?string $bounceMessage = null,
    ) {}

    public function type(): string
    {
        return 'email.'.$this->status->value;
    }
}
