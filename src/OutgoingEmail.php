<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Email sent through the Resend API.
 */
final readonly class OutgoingEmail
{
    /**
     * @param  list<string>  $to
     * @param  array<string, string>  $headers
     * @param  list<string>  $replyTo
     */
    public function __construct(
        public string $from,
        public array $to,
        public string $subject,
        public string $html,
        public string $text,
        public array $headers = [],
        public array $replyTo = [],
    ) {}
}
