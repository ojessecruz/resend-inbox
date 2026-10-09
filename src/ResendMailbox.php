<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

use Jessecruz\ResendInbox\Exceptions\MailboxException;
use Resend\Client;
use Resend\Exceptions\ErrorException;
use Resend\Exceptions\TransporterException;

/**
 * Resend API calls behind the inbox: fetching received emails and their
 * attachments, and sending emails written in the inbox.
 */
final readonly class ResendMailbox
{
    public function __construct(private Client $client) {}

    /**
     * @throws MailboxException
     */
    public function receivedEmail(string $resendId): ReceivedEmail
    {
        try {
            return ReceivedEmail::fromApi($this->client->emails->receiving->get($resendId)->toArray());
        } catch (ErrorException|TransporterException $e) {
            throw new MailboxException("Failed to fetch received email [{$resendId}] from Resend.", previous: $e);
        }
    }

    /**
     * Short-lived download URL of an attachment of a received email.
     *
     * @throws MailboxException
     */
    public function receivedAttachmentUrl(string $resendId, string $attachmentId): string
    {
        try {
            $url = $this->client->emails->receiving->attachments->get($resendId, $attachmentId)->toArray()['download_url'] ?? null;
        } catch (ErrorException|TransporterException $e) {
            throw new MailboxException("Failed to fetch attachment [{$attachmentId}] from Resend.", previous: $e);
        }

        if (! is_string($url) || $url === '') {
            throw new MailboxException("Resend returned no download URL for attachment [{$attachmentId}].");
        }

        return $url;
    }

    /**
     * Send the email and return its Resend id.
     *
     * @throws MailboxException
     */
    public function send(OutgoingEmail $email): string
    {
        try {
            $sent = $this->client->emails->send(array_filter([
                'from' => $email->from,
                'to' => $email->to,
                'subject' => $email->subject,
                'html' => $email->html,
                'text' => $email->text,
                'headers' => $email->headers,
                'reply_to' => $email->replyTo,
            ], static fn (mixed $value): bool => $value !== [] && $value !== ''));
        } catch (ErrorException|TransporterException $e) {
            throw new MailboxException('Failed to send the email through Resend.', previous: $e);
        }

        $id = $sent->toArray()['id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new MailboxException('Resend accepted the email but returned no id.');
        }

        return $id;
    }
}
