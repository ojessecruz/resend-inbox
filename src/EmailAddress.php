<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

use Symfony\Component\Mime\Address;
use Throwable;

/**
 * Parsing helpers for RFC 5322 address and Message-ID header values.
 */
final class EmailAddress
{
    /**
     * Split "Name <address>" into its parts; the address is lowercased.
     *
     * @return array{address: string, name: string|null}
     */
    public static function parse(string $value): array
    {
        try {
            $address = Address::create(trim($value));

            return [
                'address' => mb_strtolower($address->getAddress()),
                'name' => trim($address->getName()) !== '' ? $address->getName() : null,
            ];
        } catch (Throwable) {
            return ['address' => mb_strtolower(trim($value, " \t\n\r\0\x0B<>")), 'name' => null];
        }
    }

    public static function address(string $value): string
    {
        return self::parse($value)['address'];
    }

    /**
     * Bare addresses from a comma-separated input ("a@x.com, b@y.com").
     *
     * @return list<string>
     */
    public static function list(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $part): string => self::address($part),
            preg_split('/[,;\s]+/', $value) ?: [],
        ))));
    }

    public static function domain(string $address): string
    {
        return mb_strtolower(substr(strrchr(self::address($address), '@') ?: '', 1));
    }

    /**
     * Message-ID without surrounding angle brackets and whitespace.
     */
    public static function messageId(?string $value): ?string
    {
        $bare = trim((string) $value, " \t\n\r\0\x0B<>");

        return $bare === '' ? null : $bare;
    }

    /**
     * All Message-IDs in a References / In-Reply-To header value.
     *
     * @return list<string>
     */
    public static function messageIds(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        preg_match_all('/<([^<>\s]+)>/', $value, $matches);

        $ids = $matches[1] !== [] ? $matches[1] : (preg_split('/\s+/', $value) ?: []);

        return array_values(array_unique(array_filter(array_map(self::messageId(...), $ids))));
    }
}
