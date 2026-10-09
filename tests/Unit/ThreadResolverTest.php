<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\Threading\MessageLookup;
use Jessecruz\ResendInbox\Threading\ThreadCandidate;
use Jessecruz\ResendInbox\Threading\ThreadResolver;

/**
 * In-memory storage: messages keyed by Message-ID, threads with the
 * correspondents that took part and their last activity.
 */
final class InMemoryLookup implements MessageLookup
{
    /** @var list<string> */
    public array $queriedCorrespondents = [];

    /**
     * @param  array<string, int>  $messages  Message-ID => thread id, oldest first.
     * @param  list<array{id: int, subject: string, correspondents: list<string>, last: string}>  $threads  Newest first.
     */
    public function __construct(
        private array $messages = [],
        private array $threads = [],
    ) {}

    public function threadOfLatestMessage(array $messageIds): int|string|null
    {
        $found = array_intersect_key($this->messages, array_flip($messageIds));

        return $found === [] ? null : end($found);
    }

    public function recentThreadsWith(string $correspondent, DateTimeImmutable $since): iterable
    {
        $this->queriedCorrespondents[] = $correspondent;

        foreach ($this->threads as $thread) {
            if (in_array($correspondent, $thread['correspondents'], true) && new DateTimeImmutable($thread['last']) >= $since) {
                yield new ThreadCandidate($thread['id'], $thread['subject']);
            }
        }
    }
}

beforeEach(function () {
    $this->now = new DateTimeImmutable('2026-10-09 12:00:00');

    $this->lookup = new InMemoryLookup(
        messages: ['ours-1@elenya.app' => 1, 'ours-2@elenya.app' => 2],
        threads: [
            ['id' => 3, 'subject' => 'Pricing for 50 engineers', 'correspondents' => ['john@other.com'], 'last' => '2026-10-08'],
            ['id' => 1, 'subject' => 'Pricing for 50 engineers', 'correspondents' => ['maria@acme.com'], 'last' => '2026-10-01'],
            ['id' => 4, 'subject' => 'Old question', 'correspondents' => ['maria@acme.com'], 'last' => '2026-05-01'],
        ],
    );

    $this->resolver = new ThreadResolver($this->lookup);
});

it('files a reply into the conversation of the latest message it references', function () {
    expect($this->resolver->resolve(['ours-1@elenya.app', 'ours-2@elenya.app'], 'Something unrelated', 'maria@acme.com', $this->now))->toBe(2)
        ->and($this->lookup->queriedCorrespondents)->toBe([]);
});

it('falls back to the subject with the same correspondent when headers match nothing', function () {
    expect($this->resolver->resolve(['unknown@gmail.com'], 'RE: Pricing for 50 engineers', 'Maria <Maria@Acme.com>', $this->now))->toBe(1)
        ->and($this->lookup->queriedCorrespondents)->toBe(['maria@acme.com']);
});

it('never matches by subject alone', function () {
    expect($this->resolver->resolve([], 'Re: Pricing for 50 engineers', 'eve@evil.com', $this->now))->toBeNull();
});

it('ignores conversations older than the subject match window', function () {
    expect($this->resolver->resolve([], 'Re: Old question', 'maria@acme.com', $this->now))->toBeNull()
        ->and((new ThreadResolver($this->lookup, subjectMatchDays: 365))->resolve([], 'Re: Old question', 'maria@acme.com', $this->now))->toBe(4);
});

it('starts a new conversation for an empty subject', function () {
    expect($this->resolver->resolve([], 'Re:', 'maria@acme.com', $this->now))->toBeNull();
});

it('resolves straight from a received email', function () {
    $email = receivedEmail(['headers' => ['In-Reply-To' => '<ours-1@elenya.app>']]);

    expect($this->resolver->resolveFor($email, $this->now))->toBe(1);
});
