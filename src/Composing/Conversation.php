<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Composing;

/**
 * What a reply needs to know about the conversation it answers.
 */
final readonly class Conversation
{
    /**
     * @param  list<string>  $messageIds  Message-IDs of the stored messages, oldest first.
     * @param  list<string>  $mailboxes  Our addresses that received mail in the conversation.
     */
    public function __construct(
        public array $messageIds,
        public ?string $latestMessageId,
        public array $mailboxes = [],
    ) {}
}
