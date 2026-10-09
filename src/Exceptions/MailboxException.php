<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox\Exceptions;

use RuntimeException;

/**
 * A call to the Resend API behind the inbox failed.
 */
final class MailboxException extends RuntimeException {}
