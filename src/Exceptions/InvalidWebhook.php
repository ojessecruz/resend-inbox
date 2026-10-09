<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Exceptions;

use RuntimeException;

/**
 * A webhook request that must be rejected: bad signature or malformed body.
 */
final class InvalidWebhook extends RuntimeException
{
    public static function signature(string $reason): self
    {
        return new self("Invalid Resend webhook signature: {$reason}");
    }

    public static function payload(string $reason): self
    {
        return new self("Invalid Resend webhook payload: {$reason}");
    }
}
