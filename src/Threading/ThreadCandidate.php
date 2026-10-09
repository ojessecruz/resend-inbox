<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Threading;

/**
 * A stored conversation the subject fallback may match.
 */
final readonly class ThreadCandidate
{
    public function __construct(
        public int|string $id,
        public string $subject,
    ) {}
}
