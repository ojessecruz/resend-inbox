<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Exceptions;

use InvalidArgumentException;

/**
 * The From address is neither a configured sender nor one of our addresses
 * that received mail in the conversation.
 */
final class DisallowedSender extends InvalidArgumentException
{
    public static function for(string $address): self
    {
        return new self("[{$address}] is not an allowed sender address.");
    }
}
