<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Rules of the shared inbox for one domain: which of our addresses a message
 * belongs to and which addresses may send. A Resend account receives mail
 * for every domain it hosts, so only mail for this domain belongs here.
 */
final readonly class Mailbox
{
    /** @var list<string> */
    public array $senderAddresses;

    /**
     * @param  list<string>  $senderAddresses  Addresses that may send a new email; the first is the default.
     */
    public function __construct(
        public string $domain,
        array $senderAddresses,
        public string $senderName,
    ) {
        $this->senderAddresses = array_values(array_unique(array_map(EmailAddress::address(...), $senderAddresses)));
    }

    public function isOwnAddress(string $address): bool
    {
        return EmailAddress::domain($address) === mb_strtolower($this->domain);
    }

    /**
     * Whether any recipient is on our domain.
     *
     * @param  list<string>  $recipients
     */
    public function hasOwnRecipient(array $recipients): bool
    {
        foreach ($recipients as $recipient) {
            if ($this->isOwnAddress($recipient)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The first recipient on our domain, falling back to the first recipient,
     * then to the default sender.
     *
     * @param  list<string>  $recipients
     */
    public function mailboxFor(array $recipients): string
    {
        $addresses = array_map(EmailAddress::address(...), $recipients);

        foreach ($addresses as $address) {
            if ($this->isOwnAddress($address)) {
                return $address;
            }
        }

        return $addresses[0] ?? $this->senderAddresses[0] ?? 'hello@'.mb_strtolower($this->domain);
    }

    /**
     * From addresses allowed for a message: the configured senders plus, when
     * replying, every address of ours that received mail in the conversation.
     *
     * @param  list<string>  $conversationMailboxes
     * @return list<string>
     */
    public function allowedSenders(array $conversationMailboxes = []): array
    {
        $own = array_filter(
            array_map(EmailAddress::address(...), $conversationMailboxes),
            $this->isOwnAddress(...),
        );

        return array_values(array_unique([...$this->senderAddresses, ...$own]));
    }

    /**
     * "Sender Name <address>" for the From header.
     */
    public function formatSender(string $address): string
    {
        $name = str_replace(['"', '\\'], '', $this->senderName);

        return $name === '' ? $address : "\"{$name}\" <{$address}>";
    }
}
