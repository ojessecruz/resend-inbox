<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Whether a message arrived through Resend inbound or was sent from the inbox.
 */
enum Direction: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
