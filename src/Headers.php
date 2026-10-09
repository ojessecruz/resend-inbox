<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Case-insensitive access to a raw email header map as Resend returns it.
 */
final class Headers
{
    /**
     * Header value by case-insensitive name; repeated headers are joined
     * with a space.
     *
     * @param  array<array-key, mixed>  $headers
     */
    public static function get(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) !== 0) {
                continue;
            }

            if (is_array($value)) {
                return implode(' ', array_map(static fn (mixed $part): string => is_scalar($part) ? (string) $part : '', $value));
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }
}
