<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Composing;

use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\Exceptions\DisallowedSender;
use Jessecruz\ResendInbox\Mailbox;
use Jessecruz\ResendInbox\OutgoingEmail;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Builds an email written in the inbox from Markdown.
 *
 * Replies carry In-Reply-To / References so the recipient's client threads
 * them, and our own Message-ID lets their answer find the conversation
 * again. Raw HTML in the Markdown is escaped and unsafe links dropped.
 */
final readonly class ReplyBuilder
{
    private GithubFlavoredMarkdownConverter $markdown;

    public function __construct(private Mailbox $mailbox)
    {
        $this->markdown = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * @param  list<string>  $to
     *
     * @throws DisallowedSender
     */
    public function build(string $from, array $to, string $subject, string $markdown, ?Conversation $conversation = null): ComposedEmail
    {
        $from = EmailAddress::address($from);

        if (! in_array($from, $this->mailbox->allowedSenders($conversation->mailboxes ?? []), true)) {
            throw DisallowedSender::for($from);
        }

        $references = $conversation->messageIds ?? [];
        $inReplyTo = $conversation?->latestMessageId;
        $messageId = self::newMessageId($this->mailbox->domain);

        $headers = array_filter([
            'Message-ID' => "<{$messageId}>",
            'In-Reply-To' => $inReplyTo !== null ? "<{$inReplyTo}>" : null,
            'References' => $references !== []
                ? implode(' ', array_map(static fn (string $id): string => "<{$id}>", $references))
                : null,
        ], static fn (?string $value): bool => $value !== null);

        return new ComposedEmail(
            email: new OutgoingEmail(
                from: $this->mailbox->formatSender($from),
                to: array_map(EmailAddress::address(...), $to),
                subject: $subject,
                html: $this->markdown->convert($markdown)->getContent(),
                text: $markdown,
                headers: $headers,
            ),
            fromAddress: $from,
            messageId: $messageId,
            inReplyTo: $inReplyTo,
            references: $references,
        );
    }

    /**
     * Random v4 UUID on our domain, so replies to it can be told apart from
     * Message-IDs other providers generate.
     */
    public static function newMessageId(string $domain): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)).'@'.mb_strtolower($domain);
    }
}
