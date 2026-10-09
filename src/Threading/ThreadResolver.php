<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Threading;

use DateTimeImmutable;
use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\ReceivedEmail;
use Jessecruz\ResendInbox\Subject;

/**
 * Finds the conversation an inbound email belongs to.
 *
 * Headers win: any In-Reply-To / References id matching a stored message.
 * When a client dropped those headers (or the provider rewrote our
 * Message-ID), it falls back to the same normalized subject with the same
 * correspondent, never subject alone, so two customers' "Re: Pricing" stay
 * apart.
 */
final readonly class ThreadResolver
{
    /** How far back the subject fallback looks for a conversation. */
    public const int SUBJECT_MATCH_DAYS = 90;

    public function __construct(
        private MessageLookup $lookup,
        private int $subjectMatchDays = self::SUBJECT_MATCH_DAYS,
    ) {}

    /**
     * @param  list<string>  $referencedMessageIds
     */
    public function resolve(array $referencedMessageIds, string $subject, string $correspondent, ?DateTimeImmutable $now = null): int|string|null
    {
        if ($referencedMessageIds !== []) {
            $threadId = $this->lookup->threadOfLatestMessage($referencedMessageIds);

            if ($threadId !== null) {
                return $threadId;
            }
        }

        $normalizedSubject = Subject::normalize($subject);

        if ($normalizedSubject === '') {
            return null;
        }

        $since = ($now ?? new DateTimeImmutable)->modify("-{$this->subjectMatchDays} days");

        foreach ($this->lookup->recentThreadsWith(EmailAddress::address($correspondent), $since) as $candidate) {
            if (Subject::normalize($candidate->subject) === $normalizedSubject) {
                return $candidate->id;
            }
        }

        return null;
    }

    public function resolveFor(ReceivedEmail $email, ?DateTimeImmutable $now = null): int|string|null
    {
        return $this->resolve($email->referencedMessageIds(), $email->subject, $email->fromAddress, $now);
    }
}
