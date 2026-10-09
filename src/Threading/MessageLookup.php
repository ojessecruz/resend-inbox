<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Threading;

use DateTimeImmutable;

/**
 * Storage queries the thread resolver needs, implemented by each framework
 * integration on top of its own persistence.
 */
interface MessageLookup
{
    /**
     * Conversation of the most recent stored message whose Message-ID is in
     * the list, or null when none is stored.
     *
     * @param  non-empty-list<string>  $messageIds
     */
    public function threadOfLatestMessage(array $messageIds): int|string|null;

    /**
     * Conversations with activity since the given moment in which the
     * correspondent took part, either sending to us (inbound from) or
     * receiving from us (outbound to), newest activity first.
     *
     * @return iterable<ThreadCandidate>
     */
    public function recentThreadsWith(string $correspondent, DateTimeImmutable $since): iterable;
}
