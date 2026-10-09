<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Subject line helpers for matching conversations.
 */
final class Subject
{
    /**
     * Subject without reply/forward prefixes (English, Portuguese, German),
     * lowercased, so "RE: Fwd: Pricing" and "pricing" compare equal.
     */
    public static function normalize(string $subject): string
    {
        $stripped = preg_replace('/^(\s*(re|fw|fwd|res|enc|aw|wg)\s*(\[\d+\])?\s*:\s*)+/iu', '', $subject) ?? $subject;

        return mb_strtolower(trim($stripped));
    }
}
