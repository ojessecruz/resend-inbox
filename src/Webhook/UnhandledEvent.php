<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Webhook;

/**
 * A verified event the inbox does not act on (contacts, domains, opens),
 * passed through for the integration to use or ignore.
 */
final readonly class UnhandledEvent implements WebhookEvent
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(
        private string $type,
        public array $data,
    ) {}

    public function type(): string
    {
        return $this->type;
    }
}
