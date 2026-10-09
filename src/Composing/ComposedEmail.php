<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Composing;

use Jessecruz\ResendInbox\OutgoingEmail;

/**
 * An email ready to send, with the threading data to store alongside it.
 */
final readonly class ComposedEmail
{
    /**
     * @param  list<string>  $references
     */
    public function __construct(
        public OutgoingEmail $email,
        public string $fromAddress,
        public string $messageId,
        public ?string $inReplyTo,
        public array $references,
    ) {}
}
